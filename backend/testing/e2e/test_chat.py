"""
Full value chain E2E:
  Upload knowledge → set tone → chat query → usage row created → dashboard updated
"""
import json
from unittest.mock import MagicMock, patch

import pytest
from starlette.testclient import TestClient

from testing.fixtures import SAMPLE_KNOWLEDGE, settings_payload

_MOCK_RESPONSE = "Perai pricing starts at $29/month for the Starter plan."


def _make_groq_chunk(content: str | None = None, *, done: bool = False):
    chunk = MagicMock()
    chunk.choices = [MagicMock()]
    chunk.choices[0].delta = MagicMock()
    chunk.choices[0].delta.content = content
    chunk.usage = MagicMock(prompt_tokens=50, completion_tokens=20) if done else None
    return chunk


def _mock_stream(text: str):
    words = text.split()
    for i, word in enumerate(words):
        yield _make_groq_chunk(word + " ", done=(i == len(words) - 1))


@pytest.fixture(scope="module", autouse=True)
def upload_knowledge_for_chat(client: TestClient, company: dict, auth_headers: dict):
    """Upload knowledge base before chat tests run."""
    client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=auth_headers,
    )
    client.post(
        f"/api/v1/company/{company['id']}/settings",
        json=settings_payload(tone="friendly"),
        headers=auth_headers,
    )


def test_chat_ping(client: TestClient, company: dict):
    r = client.get(f"/api/v1/company/{company['id']}/chat/ping")
    assert r.status_code == 200
    assert r.json()["status"] == "ok"


def test_chat_query_requires_api_key(client: TestClient, company: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "What is the pricing?"},
    )
    assert r.status_code in (401, 403)


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_query_jwt_works_for_dashboard(
    mock_stream, client: TestClient, company: dict, auth_headers: dict
):
    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    r = client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "What is the pricing?"},
        headers=auth_headers,
    )
    assert r.status_code == 200
    assert r.json()["response"]


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_query_success(mock_stream, client: TestClient, company: dict, api_key_headers: dict):
    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    r = client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "What is the pricing?"},
        headers=api_key_headers,
    )
    assert r.status_code == 200
    data = r.json()
    assert "response" in data
    assert data["company_id"] == company["id"]
    assert len(data["response"]) > 0


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_query_creates_usage_record(
    mock_stream, client: TestClient, company: dict, api_key_headers: dict, auth_headers: dict
):
    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    requests_before = client.get(
        f"/api/v1/company/{company['id']}/requests", headers=auth_headers
    ).json()
    count_before = len(requests_before)

    client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "How many tokens are included in Business plan?"},
        headers=api_key_headers,
    )

    requests_after = client.get(
        f"/api/v1/company/{company['id']}/requests", headers=auth_headers
    ).json()
    assert len(requests_after) == count_before + 1


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_query_tone_respected(
    mock_stream, client: TestClient, company: dict, api_key_headers: dict, auth_headers: dict
):
    client.post(
        f"/api/v1/company/{company['id']}/settings",
        json=settings_payload(tone="casual", language="english"),
        headers=auth_headers,
    )
    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    r = client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "Tell me about Enterprise plan."},
        headers=api_key_headers,
    )
    assert r.status_code == 200

    messages = mock_stream.call_args[0][0]
    system_prompt = messages[0]["content"]
    assert "casual" in system_prompt.lower()
    assert "conversational" in system_prompt.lower() or "everyday language" in system_prompt.lower()


def test_chat_query_empty_prompt_rejected(client: TestClient, company: dict, api_key_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": ""},
        headers=api_key_headers,
    )
    assert r.status_code == 422


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_stores_finetune_model_name(mock_stream, client: TestClient, company: dict, api_key_headers: dict, auth_headers: dict):
    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    r = client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "What is Perai?"},
        headers=api_key_headers,
    )
    assert r.status_code == 200
    data = r.json()
    assert data["model_name"].startswith("perai-")

    deducts = client.get(f"/api/v1/balanceDeducted/{company['id']}", headers=auth_headers).json()
    linked = [d for d in deducts if d["chat_message_id"] == data["message_id"]]
    assert len(linked) == 1
    assert linked[0]["model_name"] == data["model_name"]


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_chat_query_stores_message_and_session(mock_stream, client: TestClient, company: dict, api_key_headers: dict, auth_headers: dict):
    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    r = client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "What is Perai?"},
        headers=api_key_headers,
    )
    assert r.status_code == 200
    data = r.json()
    assert data["session_id"]
    assert len(data["session_id"]) == 12
    assert data["message_id"] is not None
    assert data["token_consume"] == 70

    msgs = client.get(f"/api/v1/company/{company['id']}/messages", headers=auth_headers).json()
    assert any(m["id"] == data["message_id"] and m["session_id"] == data["session_id"] for m in msgs)


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_prompt_preview(mock_stream, client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/prompt/preview",
        json={
            "tone": "friendly",
            "language": "english",
            "max_tokens": 500,
            "company_name": "Test Corp",
            "category": "support",
            "website": "https://test.com",
        },
        headers=auth_headers,
    )
    assert r.status_code == 200
    assert "prompt" in r.json()
