"""
Full billing flow:
  register → login → balance loaded (USD) → finetune → chat → token cost deducted
"""
from decimal import Decimal
from unittest.mock import MagicMock, patch

from starlette.testclient import TestClient

from app.utils.token_cost import calculate_usd_cost
from testing.fixtures import SAMPLE_KNOWLEDGE, company_payload

_MOCK_RESPONSE = "Starter plan is $29 per month."
_PROMPT_TOKENS = 1000
_COMPLETION_TOKENS = 500


def _make_groq_chunk(content: str | None = None, *, done: bool = False):
    chunk = MagicMock()
    chunk.choices = [MagicMock()]
    chunk.choices[0].delta = MagicMock()
    chunk.choices[0].delta.content = content
    if done:
        chunk.usage = MagicMock(
            prompt_tokens=_PROMPT_TOKENS,
            completion_tokens=_COMPLETION_TOKENS,
        )
    else:
        chunk.usage = None
    return chunk


def _mock_stream(text: str):
    words = text.split()
    for i, word in enumerate(words):
        yield _make_groq_chunk(word + " ", done=(i == len(words) - 1))


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_full_billing_flow(mock_stream, client: TestClient):
    payload = company_payload()
    reg = client.post("/api/v1/auth/register", json=payload)
    assert reg.status_code == 201, reg.text
    company_id = reg.json()["id"]

    login = client.post(
        "/api/v1/auth/login",
        json={"email": payload["company_email"], "password": payload["password"]},
    )
    assert login.status_code == 200
    token = login.json()["access_token"]
    headers = {"Authorization": f"Bearer {token}"}

    balance_r = client.get(f"/api/v1/company/{company_id}/balance", headers=headers)
    assert balance_r.status_code == 200
    balance_data = balance_r.json()
    assert balance_data["currency"] == "USD"
    starting_balance = Decimal(str(balance_data["balance"]))
    assert starting_balance > 0

    key_r = client.post(
        f"/api/v1/company/{company_id}/api-keys",
        json={"name": "billing-test-key"},
        headers=headers,
    )
    assert key_r.status_code == 201
    api_key = key_r.json()["key"]
    api_headers = {"X-API-Key": api_key}

    finetune_r = client.post(
        f"/api/v1/company/{company_id}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=headers,
    )
    assert finetune_r.status_code == 201

    expected_cost = calculate_usd_cost(_PROMPT_TOKENS, _COMPLETION_TOKENS)
    mock_stream.return_value = _mock_stream(_MOCK_RESPONSE)

    chat_r = client.post(
        f"/api/v1/company/{company_id}/chat/query",
        json={"prompt": "What is the Starter plan price?", "session_id": "billflow01"},
        headers=api_headers,
    )
    assert chat_r.status_code == 200, chat_r.text
    chat_data = chat_r.json()
    assert chat_data["response"]
    assert chat_data["session_id"] == "billflow01"
    assert chat_data["message_id"] is not None
    assert chat_data["token_consume"] == _PROMPT_TOKENS + _COMPLETION_TOKENS
    assert chat_data["model_name"].startswith("perai-")
    assert Decimal(str(chat_data["balance_remaining"])) == starting_balance - expected_cost

    messages_r = client.get(
        f"/api/v1/company/{company_id}/messages",
        headers=headers,
    )
    assert messages_r.status_code == 200
    stored_msgs = [m for m in messages_r.json() if m["id"] == chat_data["message_id"]]
    assert len(stored_msgs) == 1
    stored = stored_msgs[0]
    assert stored["session_id"] == "billflow01"
    assert stored["token_consume"] == _PROMPT_TOKENS + _COMPLETION_TOKENS
    assert stored["model_name"] == chat_data["model_name"]
    assert "Starter plan" in stored["conversation"]

    deduct_r = client.get(f"/api/v1/balanceDeducted/{company_id}", headers=headers)
    assert deduct_r.status_code == 200
    deduct_rows = [d for d in deduct_r.json() if d["chat_message_id"] == chat_data["message_id"]]
    assert len(deduct_rows) == 1
    deduct = deduct_rows[0]
    assert deduct["session_id"] == "billflow01"
    assert deduct["token_consume"] == _PROMPT_TOKENS + _COMPLETION_TOKENS
    assert deduct["model_name"] == chat_data["model_name"]
    assert Decimal(str(deduct["amount"])) == expected_cost

    after_r = client.get(f"/api/v1/company/{company_id}/balance", headers=headers)
    assert Decimal(str(after_r.json()["balance"])) == starting_balance - expected_cost

    requests_r = client.get(f"/api/v1/company/{company_id}/requests", headers=headers)
    assert requests_r.status_code == 200
    usage_rows = [r for r in requests_r.json() if r["token_consume"] == _PROMPT_TOKENS + _COMPLETION_TOKENS]
    assert len(usage_rows) >= 1
    latest = usage_rows[0]
    assert latest["token_consume"] == _PROMPT_TOKENS + _COMPLETION_TOKENS
    assert Decimal(str(latest["balance_deducted"])) == expected_cost

    dashboard_r = client.get(f"/api/v1/company/{company_id}/dashboard", headers=headers)
    assert dashboard_r.status_code == 200
    dash = dashboard_r.json()
    assert Decimal(str(dash["current_balance"])) == starting_balance - expected_cost
    assert dash["usage_metrics"]["today"]["total_requests"] >= 1

    client.delete(f"/api/v1/company/{company_id}", headers=headers)


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_insufficient_balance_blocks_ai(mock_stream, client: TestClient):
    payload = company_payload()
    reg = client.post("/api/v1/auth/register", json=payload)
    company_id = reg.json()["id"]

    login = client.post(
        "/api/v1/auth/login",
        json={"email": payload["company_email"], "password": payload["password"]},
    )
    headers = {"Authorization": f"Bearer {login.json()['access_token']}"}

    from app.core.database import SessionLocal
    from app.models.balance import CompanyBalance

    db = SessionLocal()
    try:
        row = db.query(CompanyBalance).filter(CompanyBalance.company_id == company_id).one()
        row.balance = Decimal("0.000001")
        db.commit()
    finally:
        db.close()

    key_r = client.post(
        f"/api/v1/company/{company_id}/api-keys",
        json={"name": "low-balance-key"},
        headers=headers,
    )
    api_headers = {"X-API-Key": key_r.json()["key"]}

    client.post(
        f"/api/v1/company/{company_id}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=headers,
    )

    chat_r = client.post(
        f"/api/v1/company/{company_id}/chat/query",
        json={"prompt": "Hello"},
        headers=api_headers,
    )
    assert chat_r.status_code == 402
    mock_stream.assert_not_called()

    client.delete(f"/api/v1/company/{company_id}", headers=headers)
