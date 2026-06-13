import pytest
from starlette.testclient import TestClient


def test_create_api_key(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/api-keys",
        json={"name": "create-test-key"},
        headers=auth_headers,
    )
    assert r.status_code == 201
    data = r.json()
    assert "key" in data
    assert data["key"].startswith("sk_")
    assert data["name"] == "create-test-key"
    assert data["status"] == "active"
    assert "key_preview" in data


def test_create_api_key_no_auth(client: TestClient, company: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/api-keys",
        json={"name": "no-auth-key"},
    )
    assert r.status_code in (401, 403)


def test_create_api_key_wrong_company(client: TestClient, auth_headers: dict):
    r = client.post(
        "/api/v1/company/999999/api-keys",
        json={"name": "wrong-company-key"},
        headers=auth_headers,
    )
    assert r.status_code in (401, 403, 404)


def test_list_api_keys(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}/api-keys", headers=auth_headers)
    assert r.status_code == 200
    assert isinstance(r.json(), list)
    assert len(r.json()) >= 1


def test_get_api_key(client: TestClient, company: dict, auth_headers: dict):
    list_r = client.get(f"/api/v1/company/{company['id']}/api-keys", headers=auth_headers)
    key_id = list_r.json()[0]["id"]

    r = client.get(f"/api/v1/company/{company['id']}/api-keys/{key_id}", headers=auth_headers)
    assert r.status_code == 200
    assert r.json()["id"] == key_id


def test_update_api_key_name(client: TestClient, company: dict, auth_headers: dict):
    list_r = client.get(f"/api/v1/company/{company['id']}/api-keys", headers=auth_headers)
    key_id = list_r.json()[0]["id"]

    r = client.put(
        f"/api/v1/company/{company['id']}/api-keys/{key_id}",
        json={"name": "renamed-key"},
        headers=auth_headers,
    )
    assert r.status_code == 200
    assert r.json()["name"] == "renamed-key"


def test_revoke_api_key(client: TestClient, company: dict, auth_headers: dict):
    create_r = client.post(
        f"/api/v1/company/{company['id']}/api-keys",
        json={"name": "revoke-test-key"},
        headers=auth_headers,
    )
    key_id = create_r.json()["id"]

    r = client.post(
        f"/api/v1/company/{company['id']}/api-keys/{key_id}/revoke",
        headers=auth_headers,
    )
    assert r.status_code == 200
    assert r.json()["status"] == "revoked"


def test_delete_api_key(client: TestClient, company: dict, auth_headers: dict):
    create_r = client.post(
        f"/api/v1/company/{company['id']}/api-keys",
        json={"name": "delete-test-key"},
        headers=auth_headers,
    )
    key_id = create_r.json()["id"]

    r = client.delete(
        f"/api/v1/company/{company['id']}/api-keys/{key_id}",
        headers=auth_headers,
    )
    assert r.status_code == 204

    get_r = client.get(
        f"/api/v1/company/{company['id']}/api-keys/{key_id}",
        headers=auth_headers,
    )
    assert get_r.status_code == 404
