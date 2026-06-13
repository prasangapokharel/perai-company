import pytest
from starlette.testclient import TestClient

from testing.fixtures import SAMPLE_KNOWLEDGE


def test_upload_knowledge(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=auth_headers,
    )
    assert r.status_code == 201
    data = r.json()
    assert data["company_id"] == company["id"]
    assert "id" in data
    assert "rag_company_path" in data


def test_upload_knowledge_no_auth(client: TestClient, company: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
    )
    assert r.status_code in (401, 403)


def test_upload_knowledge_wrong_company(client: TestClient, auth_headers: dict):
    r = client.post(
        "/api/v1/company/999999/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=auth_headers,
    )
    assert r.status_code in (401, 403, 404)


def test_upload_invalid_jsonl_rejected(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": "not valid jsonl content"},
        headers=auth_headers,
    )
    assert r.status_code == 400


def test_upload_empty_content_rejected(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": ""},
        headers=auth_headers,
    )
    assert r.status_code == 422


def test_get_finetune(client: TestClient, company: dict, auth_headers: dict):
    client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=auth_headers,
    )
    r = client.get(f"/api/v1/company/{company['id']}/finetune", headers=auth_headers)
    assert r.status_code == 200
    data = r.json()
    assert data["company_id"] == company["id"]


def test_get_finetune_not_found(client: TestClient, company: dict, auth_headers: dict):
    r = client.get("/api/v1/company/999999/finetune", headers=auth_headers)
    assert r.status_code in (401, 403, 404)


def test_upsert_replaces_existing(client: TestClient, company: dict, auth_headers: dict):
    updated_knowledge = SAMPLE_KNOWLEDGE + "\n\n## Updated Section\nNew content here."
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": updated_knowledge},
        headers=auth_headers,
    )
    assert r.status_code == 201

    get_r = client.get(f"/api/v1/company/{company['id']}/finetune", headers=auth_headers)
    assert get_r.status_code == 200


def test_delete_finetune(client: TestClient, company: dict, auth_headers: dict):
    client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=auth_headers,
    )
    r = client.delete(f"/api/v1/company/{company['id']}/finetune", headers=auth_headers)
    assert r.status_code == 204
