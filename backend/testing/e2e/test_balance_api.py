from decimal import Decimal

from starlette.testclient import TestClient

from testing.fixtures import company_payload


def test_auth_me_returns_balance(client: TestClient):
    payload = company_payload()
    reg = client.post("/api/v1/auth/register", json=payload)
    company_id = reg.json()["id"]

    login = client.post(
        "/api/v1/auth/login",
        json={"email": payload["company_email"], "password": payload["password"]},
    )
    token = login.json()["access_token"]
    headers = {"Authorization": f"Bearer {token}"}

    r = client.get("/api/v1/auth/me", headers=headers)
    assert r.status_code == 200
    data = r.json()
    assert data["company_id"] == company_id
    assert data["currency"] == "USD"
    assert Decimal(str(data["balance"])) > 0

    client.delete(f"/api/v1/company/{company_id}", headers=headers)


def test_company_balance_endpoint(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/companyBalance/{company['id']}", headers=auth_headers)
    assert r.status_code == 200
    assert r.json()["currency"] == "USD"


def test_balance_deducted_list(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/balanceDeducted/{company['id']}", headers=auth_headers)
    assert r.status_code == 200
    assert isinstance(r.json(), list)


def test_topup_increases_balance(client: TestClient, company: dict, auth_headers: dict):
    before = Decimal(str(client.get(f"/api/v1/companyBalance/{company['id']}", headers=auth_headers).json()["balance"]))
    r = client.post(
        f"/api/v1/companyBalance/{company['id']}/topup",
        json={"amount": "5.00"},
        headers=auth_headers,
    )
    assert r.status_code == 200
    after = Decimal(str(r.json()["balance"]))
    assert after == before + Decimal("5.00")

    topups = client.get(f"/api/v1/companyBalance/{company['id']}/topups", headers=auth_headers)
    assert topups.status_code == 200
    assert len(topups.json()) >= 1
