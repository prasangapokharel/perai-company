"""Khalti top-up flow with the gateway mocked — initiate, verify, idempotency."""

import itertools
from decimal import Decimal

import pytest
from starlette.testclient import TestClient

import app.api.v1.companyBalance.khalti_service as khalti_service

_pidx_counter = itertools.count(1)


class FakeResponse:
    def __init__(self, status_code: int, payload: dict):
        self.status_code = status_code
        self._payload = payload

    def json(self) -> dict:
        return self._payload


@pytest.fixture()
def mock_khalti(monkeypatch):
    """Fake the Khalti gateway. Remembers initiated amounts per pidx so the
    lookup response mirrors what a real completed payment would return."""
    state = {"lookup_status": "Completed", "amounts": {}, "last_pidx": None}

    def fake_post(url, json=None, headers=None, timeout=None):
        if url.endswith("/epayment/initiate/"):
            pidx = f"test-pidx-{next(_pidx_counter)}"
            state["amounts"][pidx] = json["amount"]
            state["last_pidx"] = pidx
            return FakeResponse(
                200,
                {"pidx": pidx, "payment_url": f"https://test-pay.khalti.com/?pidx={pidx}"},
            )
        if url.endswith("/epayment/lookup/"):
            return FakeResponse(
                200,
                {
                    "pidx": json["pidx"],
                    "status": state["lookup_status"],
                    "total_amount": state["amounts"].get(json["pidx"], 0),
                    "transaction_id": "txn-1",
                },
            )
        raise AssertionError(f"unexpected Khalti URL {url}")

    monkeypatch.setattr(khalti_service.httpx, "post", fake_post)
    return state


def _get_balance(client: TestClient, company: dict, auth_headers: dict) -> Decimal:
    r = client.get(f"/api/v1/companyBalance/{company['id']}", headers=auth_headers)
    assert r.status_code == 200, r.text
    return Decimal(r.json()["balance"])


def _initiate(client: TestClient, company: dict, auth_headers: dict, amount: int) -> dict:
    r = client.post(
        f"/api/v1/companyBalance/{company['id']}/khalti/initiate",
        json={"amount": amount},
        headers=auth_headers,
    )
    assert r.status_code == 200, r.text
    return r.json()


def _verify(client: TestClient, company: dict, auth_headers: dict, pidx: str):
    return client.post(
        f"/api/v1/companyBalance/{company['id']}/khalti/verify",
        json={"pidx": pidx},
        headers=auth_headers,
    )


def test_khalti_initiate_returns_payment_url(client, company, auth_headers, mock_khalti):
    data = _initiate(client, company, auth_headers, 5)
    assert data["pidx"].startswith("test-pidx-")
    assert data["payment_url"].startswith("https://test-pay.khalti.com/")
    assert data["amount_npr_paisa"] == 5 * 140 * 100
    assert data["status"] == "Initiated"


def test_khalti_verify_completed_credits_once(client, company, auth_headers, mock_khalti):
    before = _get_balance(client, company, auth_headers)
    pidx = _initiate(client, company, auth_headers, 5)["pidx"]

    r = _verify(client, company, auth_headers, pidx)
    assert r.status_code == 200, r.text
    data = r.json()
    assert data["status"] == "Completed"
    after = Decimal(data["balance"])
    assert after == before + Decimal("5")

    # Second verify must not double-credit.
    r2 = _verify(client, company, auth_headers, pidx)
    assert r2.status_code == 200, r2.text
    assert Decimal(r2.json()["balance"]) == after

    # Credited top-up appears in history with the khalti reference exactly once.
    topups = client.get(
        f"/api/v1/companyBalance/{company['id']}/topups", headers=auth_headers
    ).json()
    khalti_rows = [t for t in topups if t["reference"] == f"khalti:{pidx}"]
    assert len(khalti_rows) == 1


def test_khalti_verify_pending_does_not_credit(client, company, auth_headers, mock_khalti):
    mock_khalti["lookup_status"] = "Pending"
    pidx = _initiate(client, company, auth_headers, 10)["pidx"]

    before = _get_balance(client, company, auth_headers)
    r = _verify(client, company, auth_headers, pidx)
    assert r.status_code == 200, r.text
    assert r.json()["status"] == "Pending"
    assert _get_balance(client, company, auth_headers) == before


def test_khalti_verify_amount_mismatch_rejected(client, company, auth_headers, mock_khalti):
    pidx = _initiate(client, company, auth_headers, 25)["pidx"]
    mock_khalti["amounts"][pidx] = 1  # gateway reports a different paid amount

    before = _get_balance(client, company, auth_headers)
    r = _verify(client, company, auth_headers, pidx)
    assert r.status_code == 400
    assert "does not match" in r.json()["detail"]
    assert _get_balance(client, company, auth_headers) == before


def test_khalti_verify_unknown_pidx_rejected(client, company, auth_headers, mock_khalti):
    r = _verify(client, company, auth_headers, "does-not-exist")
    assert r.status_code == 400
    assert "Unknown payment reference" in r.json()["detail"]


def test_khalti_other_company_cannot_verify(client, company, auth_headers, mock_khalti):
    pidx = _initiate(client, company, auth_headers, 5)["pidx"]
    r = client.post(
        f"/api/v1/companyBalance/{company['id'] + 999}/khalti/verify",
        json={"pidx": pidx},
        headers=auth_headers,
    )
    assert r.status_code == 403
