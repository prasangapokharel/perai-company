import io

import pytest
from starlette.testclient import TestClient


def _png_bytes() -> bytes:
    """Minimal valid 1x1 white PNG."""
    import base64

    data = (
        "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk"
        "YPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=="
    )
    return base64.b64decode(data)


def test_upload_logo(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/files/companies/{company['id']}/logo",
        files={"file": ("logo.png", io.BytesIO(_png_bytes()), "image/png")},
        headers=auth_headers,
    )
    assert r.status_code == 200


def test_upload_logo_no_auth(client: TestClient, company: dict):
    r = client.post(
        f"/api/v1/files/companies/{company['id']}/logo",
        files={"file": ("logo.png", io.BytesIO(_png_bytes()), "image/png")},
    )
    assert r.status_code in (401, 403)


def test_download_logo_public(client: TestClient, company: dict, auth_headers: dict):
    client.post(
        f"/api/v1/files/companies/{company['id']}/logo",
        files={"file": ("logo.png", io.BytesIO(_png_bytes()), "image/png")},
        headers=auth_headers,
    )
    r = client.get(f"/api/v1/files/companies/{company['id']}/logo")
    assert r.status_code in (200, 404)


def test_upload_content(client: TestClient, company: dict, auth_headers: dict):
    content = b"This is company knowledge content for testing."
    r = client.post(
        f"/api/v1/files/companies/{company['id']}/content",
        files={"file": ("knowledge.txt", io.BytesIO(content), "text/plain")},
        headers=auth_headers,
    )
    assert r.status_code == 200


def test_upload_content_no_auth(client: TestClient, company: dict):
    content = b"Unauthorized upload attempt."
    r = client.post(
        f"/api/v1/files/companies/{company['id']}/content",
        files={"file": ("knowledge.txt", io.BytesIO(content), "text/plain")},
    )
    assert r.status_code in (401, 403)


def test_list_company_storage(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/files/companies/{company['id']}/list", headers=auth_headers)
    assert r.status_code == 200
    assert isinstance(r.json(), list)


def test_list_storage_no_auth(client: TestClient, company: dict):
    r = client.get(f"/api/v1/files/companies/{company['id']}/list")
    assert r.status_code in (401, 403)


def test_upload_cross_company_rejected(client: TestClient, auth_headers: dict):
    r = client.post(
        "/api/v1/files/companies/999999/logo",
        files={"file": ("logo.png", io.BytesIO(_png_bytes()), "image/png")},
        headers=auth_headers,
    )
    assert r.status_code in (401, 403, 404)
