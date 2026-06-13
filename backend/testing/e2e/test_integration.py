"""
End-to-end integration: register → API key → finetune → settings → chat → storage.

Verifies API-key auth, billing linkage, and that language/tone settings reach the LLM prompt.
"""
from decimal import Decimal
from unittest.mock import MagicMock, patch

from starlette.testclient import TestClient

from testing.fixtures import SAMPLE_KNOWLEDGE, company_payload, settings_payload, random_suffix

_MOCK_RESPONSE = "Starter plan costs $29 per month."
_NEPALI_MOCK_RESPONSE = "स्टार्टर योजना महिनाको $29 हो।"


def _make_groq_chunk(content: str | None = None, *, done: bool = False, prompt_tokens: int = 100, completion_tokens: int = 40):
    chunk = MagicMock()
    chunk.choices = [MagicMock()]
    chunk.choices[0].delta = MagicMock()
    chunk.choices[0].delta.content = content
    chunk.usage = MagicMock(prompt_tokens=prompt_tokens, completion_tokens=completion_tokens) if done else None
    return chunk


def _mock_stream(text: str, *, prompt_tokens: int = 100, completion_tokens: int = 40):
    words = text.split()
    for i, word in enumerate(words):
        yield _make_groq_chunk(
            word + " ",
            done=(i == len(words) - 1),
            prompt_tokens=prompt_tokens,
            completion_tokens=completion_tokens,
        )


def _register_login_create_key(client: TestClient):
    payload = company_payload()
    reg = client.post("/api/v1/auth/register", json=payload)
    assert reg.status_code == 201, reg.text
    company_id = reg.json()["id"]

    login = client.post(
        "/api/v1/auth/login",
        json={"email": payload["company_email"], "password": payload["password"]},
    )
    assert login.status_code == 200
    jwt_headers = {"Authorization": f"Bearer {login.json()['access_token']}"}

    key_r = client.post(
        f"/api/v1/company/{company_id}/api-keys",
        json={"name": f"integration-key-{random_suffix()}"},
        headers=jwt_headers,
    )
    assert key_r.status_code == 201
    api_key = key_r.json()["key"]
    assert api_key.startswith("sk_")

    return company_id, jwt_headers, {"X-API-Key": api_key}, payload


def _bootstrap_company(client: TestClient, company_id: int, jwt_headers: dict, *, language: str = "english", tone: str = "formal"):
    finetune_r = client.post(
        f"/api/v1/company/{company_id}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=jwt_headers,
    )
    assert finetune_r.status_code == 201

    settings_r = client.post(
        f"/api/v1/company/{company_id}/settings",
        json=settings_payload(tone=tone, language=language, max_tokens=500),
        headers=jwt_headers,
    )
    assert settings_r.status_code in (200, 201)
    assert settings_r.json()["language"] == language
    assert settings_r.json()["tone"] == tone


def _system_prompt_from_mock(mock_stream) -> str:
    mock_stream.assert_called_once()
    messages = mock_stream.call_args[0][0]
    assert messages[0]["role"] == "system"
    return messages[0]["content"]


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_full_api_key_integration_flow(mock_stream, client: TestClient):
    company_id, jwt_headers, api_headers, _ = _register_login_create_key(client)
    _bootstrap_company(client, company_id, jwt_headers)

    me_r = client.get("/api/v1/auth/me", headers=jwt_headers)
    assert me_r.status_code == 200
    me = me_r.json()
    assert me["company_id"] == company_id
    assert me["currency"] == "USD"
    starting_balance = Decimal(str(me["balance"]))

    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    chat_r = client.post(
        f"/api/v1/company/{company_id}/chat/query",
        json={"prompt": "What is the Starter plan price?", "session_id": "integr01"},
        headers=api_headers,
    )
    assert chat_r.status_code == 200, chat_r.text
    chat = chat_r.json()
    assert chat["response"]
    assert chat["session_id"] == "integr01"
    assert chat["message_id"]
    assert chat["token_consume"] == 140
    assert chat["model_name"].startswith("perai-")
    assert Decimal(str(chat["balance_remaining"])) < starting_balance

    msgs = client.get(f"/api/v1/company/{company_id}/messages", headers=jwt_headers).json()
    assert any(m["id"] == chat["message_id"] for m in msgs)

    deducts = client.get(f"/api/v1/balanceDeducted/{company_id}", headers=jwt_headers).json()
    assert any(d["chat_message_id"] == chat["message_id"] for d in deducts)

    requests = client.get(f"/api/v1/company/{company_id}/requests", headers=jwt_headers).json()
    assert len(requests) >= 1

    dashboard = client.get(f"/api/v1/company/{company_id}/dashboard", headers=jwt_headers).json()
    assert dashboard["usage_metrics"]["today"]["total_requests"] >= 1

    client.delete(f"/api/v1/company/{company_id}", headers=jwt_headers)


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_system_prompt_uses_english_language(mock_stream, client: TestClient):
    company_id, jwt_headers, api_headers, _ = _register_login_create_key(client)
    _bootstrap_company(client, company_id, jwt_headers, language="english", tone="professional")

    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    r = client.post(
        f"/api/v1/company/{company_id}/chat/query",
        json={"prompt": "What is pricing?"},
        headers=api_headers,
    )
    assert r.status_code == 200

    system_prompt = _system_prompt_from_mock(mock_stream)
    assert "Language: english" in system_prompt or "language: english" in system_prompt.lower()
    assert "Respond in English" in system_prompt
    assert "professional" in system_prompt.lower()

    client.delete(f"/api/v1/company/{company_id}", headers=jwt_headers)


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_system_prompt_uses_nepali_language(mock_stream, client: TestClient):
    company_id, jwt_headers, api_headers, _ = _register_login_create_key(client)
    _bootstrap_company(client, company_id, jwt_headers, language="nepali", tone="friendly")

    mock_stream.return_value = _mock_stream(_NEPALI_MOCK_RESPONSE)

    r = client.post(
        f"/api/v1/company/{company_id}/chat/query",
        json={"prompt": "मूल्य कति हो?"},
        headers=api_headers,
    )
    assert r.status_code == 200

    system_prompt = _system_prompt_from_mock(mock_stream)
    assert "Language: nepali" in system_prompt or "language: nepali" in system_prompt.lower()
    assert "नेपाली" in system_prompt
    assert "Respond in Nepali" in system_prompt
    assert "friendly" in system_prompt.lower()

    client.delete(f"/api/v1/company/{company_id}", headers=jwt_headers)


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_revoked_api_key_rejected_for_chat(mock_stream, client: TestClient):
    company_id, jwt_headers, api_headers, _ = _register_login_create_key(client)
    _bootstrap_company(client, company_id, jwt_headers)

    keys = client.get(f"/api/v1/company/{company_id}/api-keys", headers=jwt_headers).json()
    key_id = keys[0]["id"]

    revoke_r = client.post(
        f"/api/v1/company/{company_id}/api-keys/{key_id}/revoke",
        headers=jwt_headers,
    )
    assert revoke_r.status_code == 200

    chat_r = client.post(
        f"/api/v1/company/{company_id}/chat/query",
        json={"prompt": "Hello"},
        headers=api_headers,
    )
    assert chat_r.status_code in (401, 403)
    mock_stream.assert_not_called()

    client.delete(f"/api/v1/company/{company_id}", headers=jwt_headers)


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_stream_via_api_key(mock_stream, client: TestClient):
    company_id, jwt_headers, api_headers, _ = _register_login_create_key(client)
    _bootstrap_company(client, company_id, jwt_headers, language="english")

    mock_stream.return_value = _mock_stream("Hello from stream.")

    r = client.post(
        f"/api/v1/company/{company_id}/chat/stream",
        json={"message": "Hi", "session_id": "stream01"},
        headers=api_headers,
    )
    assert r.status_code == 200
    body = r.text
    assert '"type": "start"' in body
    assert '"type": "token"' in body
    assert '"type": "done"' in body
    assert "stream01" in body
    assert "message_id" in body

    deducts = client.get(f"/api/v1/balanceDeducted/{company_id}", headers=jwt_headers).json()
    assert any(d["session_id"] == "stream01" for d in deducts)

    client.delete(f"/api/v1/company/{company_id}", headers=jwt_headers)
