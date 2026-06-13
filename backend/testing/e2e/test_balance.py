from starlette.testclient import TestClient


def test_get_balance(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}/balance", headers=auth_headers)
    assert r.status_code == 200
    data = r.json()
    assert data["company_id"] == company["id"]
    assert float(data["balance"]) >= 0


def test_balance_created_on_register(client: TestClient):
    from testing.fixtures import company_payload

    payload = company_payload()
    reg = client.post("/api/v1/auth/register", json=payload)
    assert reg.status_code == 201
    company_id = reg.json()["id"]

    login = client.post(
        "/api/v1/auth/login",
        json={"email": payload["company_email"], "password": payload["password"]},
    )
    token = login.json()["access_token"]
    headers = {"Authorization": f"Bearer {token}"}

    r = client.get(f"/api/v1/company/{company_id}/balance", headers=headers)
    assert r.status_code == 200
    assert float(r.json()["balance"]) > 0

    client.delete(f"/api/v1/company/{company_id}", headers=headers)


def test_balance_no_auth(client: TestClient, company: dict):
    r = client.get(f"/api/v1/company/{company['id']}/balance")
    assert r.status_code in (401, 403)
