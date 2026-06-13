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
    updated_knowledge = SAMPLE_KNOWLEDGE + '\n{"title":"Updated","content":"New content here."}'
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": updated_knowledge, "mode": "replace"},
        headers=auth_headers,
    )
    assert r.status_code == 201

    get_r = client.get(f"/api/v1/company/{company['id']}/finetune", headers=auth_headers)
    assert get_r.status_code == 200


def test_upload_appends_to_existing_knowledge(client: TestClient, company: dict, auth_headers: dict):
    first = '{"question":"What grades do you offer?","answer":"We offer grades 1 through 11."}'
    second = '{"title":"Class 12 Results","content":"98% pass rate with 45 distinctions."}'

    r1 = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": first, "mode": "append"},
        headers=auth_headers,
    )
    assert r1.status_code == 201

    r2 = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": second, "mode": "append"},
        headers=auth_headers,
    )
    assert r2.status_code == 201

    content = client.get(
        f"/api/v1/company/{company['id']}/finetune",
        headers=auth_headers,
    ).json()["content"]
    assert "grades 1 through 11" in content
    assert "Class 12 Results" in content
    assert "98% pass rate" in content


def test_delete_finetune(client: TestClient, company: dict, auth_headers: dict):
    client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=auth_headers,
    )
    r = client.delete(f"/api/v1/company/{company['id']}/finetune", headers=auth_headers)
    assert r.status_code == 204


def test_upload_finetune_with_api_key(client: TestClient, company: dict, api_key_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE, "mode": "append"},
        headers=api_key_headers,
    )
    assert r.status_code == 201, r.text
    data = r.json()
    assert data["company_id"] == company["id"]
    assert data["company_model_name"].startswith("perai-")


def test_get_finetune_with_api_key(client: TestClient, company: dict, api_key_headers: dict):
    client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=api_key_headers,
    )
    r = client.get(f"/api/v1/company/{company['id']}/finetune", headers=api_key_headers)
    assert r.status_code == 200
    assert "Perai" in (r.json().get("content") or "")


def test_finetune_api_key_wrong_company(client: TestClient, api_key_headers: dict):
    r = client.post(
        "/api/v1/company/999999/finetune",
        json={"content": SAMPLE_KNOWLEDGE},
        headers=api_key_headers,
    )
    assert r.status_code in (403, 404)


def test_finetune_invalid_mode_rejected(client: TestClient, company: dict, api_key_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": SAMPLE_KNOWLEDGE, "mode": "overwrite"},
        headers=api_key_headers,
    )
    assert r.status_code == 422


def test_finetune_invalid_jsonl_with_api_key(client: TestClient, company: dict, api_key_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": "not-jsonl"},
        headers=api_key_headers,
    )
    assert r.status_code == 400


def test_finetune_append_with_api_key(client: TestClient, company: dict, api_key_headers: dict):
    first = '{"question":"What grades do you offer?","answer":"Grades 1 through 11."}'
    second = '{"title":"Class 12","content":"98% pass rate."}'
    assert client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": first, "mode": "append"},
        headers=api_key_headers,
    ).status_code == 201
    assert client.post(
        f"/api/v1/company/{company['id']}/finetune",
        json={"content": second, "mode": "append"},
        headers=api_key_headers,
    ).status_code == 201
    content = client.get(
        f"/api/v1/company/{company['id']}/finetune",
        headers=api_key_headers,
    ).json()["content"]
    assert "Grades 1 through 11" in content
    assert "98% pass rate" in content
