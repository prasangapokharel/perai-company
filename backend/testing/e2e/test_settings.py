import pytest
from starlette.testclient import TestClient

from testing.fixtures import settings_payload


def test_create_settings(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/settings",
        json=settings_payload(tone="friendly", language="english", max_tokens=500),
        headers=auth_headers,
    )
    assert r.status_code == 201
    data = r.json()
    assert data["tone"] == "friendly"
    assert data["language"] == "english"
    assert data["max_tokens"] == 500
    assert data["company_id"] == company["id"]


def test_get_settings(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}/settings", headers=auth_headers)
    assert r.status_code == 200
    assert "tone" in r.json()
    assert "language" in r.json()
    assert "max_tokens" in r.json()


def test_update_settings(client: TestClient, company: dict, auth_headers: dict):
    client.post(
        f"/api/v1/company/{company['id']}/settings",
        json=settings_payload(tone="formal"),
        headers=auth_headers,
    )
    r = client.put(
        f"/api/v1/company/{company['id']}/settings",
        json={"tone": "professional", "max_tokens": 800},
        headers=auth_headers,
    )
    assert r.status_code == 200
    data = r.json()
    assert data["tone"] == "professional"
    assert data["max_tokens"] == 800


def test_settings_invalid_tone_rejected(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/settings",
        json={"tone": "sarcastic", "language": "english", "max_tokens": 500},
        headers=auth_headers,
    )
    assert r.status_code == 422


def test_settings_max_tokens_out_of_range(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/settings",
        json={"tone": "friendly", "language": "english", "max_tokens": 99},
        headers=auth_headers,
    )
    assert r.status_code == 422


def test_settings_no_auth_rejected(client: TestClient, company: dict):
    r = client.get(f"/api/v1/company/{company['id']}/settings")
    assert r.status_code in (401, 403)


def test_settings_cross_company_rejected(client: TestClient, auth_headers: dict):
    r = client.get("/api/v1/company/999999/settings", headers=auth_headers)
    assert r.status_code in (401, 403, 404)


def test_delete_settings(client: TestClient, company: dict, auth_headers: dict):
    client.post(
        f"/api/v1/company/{company['id']}/settings",
        json=settings_payload(),
        headers=auth_headers,
    )
    r = client.delete(f"/api/v1/company/{company['id']}/settings", headers=auth_headers)
    assert r.status_code in (200, 204)
