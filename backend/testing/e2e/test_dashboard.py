from unittest.mock import patch

import pytest
from starlette.testclient import TestClient

from testing.fixtures import SAMPLE_KNOWLEDGE


def _make_groq_chunk(content=None, done=False):
    from unittest.mock import MagicMock

    chunk = MagicMock()
    chunk.choices = [MagicMock()]
    chunk.choices[0].delta = MagicMock()
    chunk.choices[0].delta.content = content
    chunk.usage = MagicMock(prompt_tokens=30, completion_tokens=15) if done else None
    return chunk


def _mock_stream(text: str):
    words = text.split()
    for i, w in enumerate(words):
        yield _make_groq_chunk(w + " ", done=(i == len(words) - 1))


def test_dashboard_structure(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}/dashboard", headers=auth_headers)
    assert r.status_code == 200
    data = r.json()
    assert data["company_id"] == company["id"]
    assert "usage_metrics" in data
    assert "credit_deducted" in data
    assert "api_keys" in data
    assert "today" in data["usage_metrics"]
    assert "weekly" in data["usage_metrics"]
    assert "monthly" in data["usage_metrics"]


def test_dashboard_no_auth(client: TestClient, company: dict):
    r = client.get(f"/api/v1/company/{company['id']}/dashboard")
    assert r.status_code in (401, 403)


def test_dashboard_cross_company_rejected(client: TestClient, auth_headers: dict):
    r = client.get("/api/v1/company/999999/dashboard", headers=auth_headers)
    assert r.status_code in (401, 403, 404)


def test_dashboard_api_key_count(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}/dashboard", headers=auth_headers)
    assert r.status_code == 200
    data = r.json()
    assert data["total_api_keys"] >= 1
    assert data["active_api_keys"] >= 1


@patch("app.api.v1.chat.service.stream_chat_completion")
def test_dashboard_increments_after_chat(
    mock_stream, client: TestClient, company: dict, api_key_headers: dict, auth_headers: dict
):
    client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=auth_headers,
    )
    mock_stream.return_value = _mock_stream("Hello from Perai support.")

    r_before = client.get(f"/api/v1/company/{company['id']}/dashboard", headers=auth_headers)
    requests_before = r_before.json()["usage_metrics"]["today"]["total_requests"]

    mock_stream.return_value = _mock_stream("Hello from Perai support.")
    client.post(
        f"/api/v1/company/{company['id']}/chat/query",
        json={"prompt": "What does Perai do?"},
        headers=api_key_headers,
    )

    mock_stream.return_value = _mock_stream("Hello from Perai support.")
    r_after = client.get(f"/api/v1/company/{company['id']}/dashboard", headers=auth_headers)
    requests_after = r_after.json()["usage_metrics"]["today"]["total_requests"]
    assert requests_after == requests_before + 1
