import pytest
from starlette.testclient import TestClient

from testing.fixtures import company_payload


def test_register_success(client: TestClient):
    payload = company_payload()
    r = client.post("/api/v1/auth/register", json=payload)
    assert r.status_code == 201
    data = r.json()
    assert data["company_name"] == payload["company_name"]
    assert data["company_email"] == payload["company_email"]
    assert "id" in data
    assert "password_hash" not in data


def test_register_duplicate_email(client: TestClient, company: dict):
    payload = company_payload()
    payload["company_email"] = company["company_email"]
    r = client.post("/api/v1/auth/register", json=payload)
    assert r.status_code in (400, 409, 422)


def test_register_missing_fields(client: TestClient):
    r = client.post("/api/v1/auth/register", json={"company_name": "X"})
    assert r.status_code == 422


def test_register_short_password(client: TestClient):
    payload = company_payload()
    payload["password"] = "short"
    r = client.post("/api/v1/auth/register", json=payload)
    assert r.status_code == 422


def test_login_success(client: TestClient, company: dict):
    r = client.post(
        "/api/v1/auth/login",
        json={"email": company["company_email"], "password": "testpass1234"},
    )
    assert r.status_code == 200
    data = r.json()
    assert "access_token" in data
    assert data["token_type"] == "bearer"
    assert data["company"]["id"] == company["id"]


def test_login_wrong_password(client: TestClient, company: dict):
    r = client.post(
        "/api/v1/auth/login",
        json={"email": company["company_email"], "password": "wrongpassword"},
    )
    assert r.status_code in (400, 401, 403)


def test_login_unknown_email(client: TestClient):
    r = client.post(
        "/api/v1/auth/login",
        json={"email": "nobody@nowhere.test", "password": "testpass1234"},
    )
    assert r.status_code in (400, 401, 403, 404)


def test_verify_company_exists(client: TestClient, company: dict):
    r = client.get(f"/api/v1/auth/verify/{company['id']}")
    assert r.status_code == 200
    assert r.json()["id"] == company["id"]


def test_verify_company_not_found(client: TestClient):
    r = client.get("/api/v1/auth/verify/999999")
    assert r.status_code == 404


def test_jwt_authorizes_company_route(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}", headers=auth_headers)
    assert r.status_code == 200


def test_no_auth_rejected(client: TestClient, company: dict):
    r = client.get(f"/api/v1/company/{company['id']}")
    assert r.status_code in (401, 403)
