import os
import pytest
from starlette.testclient import TestClient

os.environ.setdefault("DB_URL", "sqlite:///./test.db")
os.environ.setdefault("JWT_SECRET", "test-secret-key-for-ci")
os.environ.setdefault("GROQ_API_KEY", "test-groq-key")
os.environ.setdefault("GROQ_MODEL", "llama-3.3-70b-versatile")
os.environ.setdefault("GROQ_MODEL_INPUT_COST", "0.0003")
os.environ.setdefault("GROQ_MODEL_OUTPUT_COST", "0.0003")
os.environ.setdefault("DEFAULT_COMPANY_BALANCE", "10.00")

from app.main import app  # noqa: E402  (env vars must be set first)
from app.core.database import Base, engine  # noqa: E402


@pytest.fixture(scope="session", autouse=True)
def _ensure_test_schema():
    if str(engine.url).startswith("sqlite"):
        import app.models.balance  # noqa: F401
        import app.models.balance_deduct  # noqa: F401
        import app.models.balance_topup  # noqa: F401
        import app.models.chatMessage  # noqa: F401
        import app.models.company  # noqa: F401
        import app.models.companyRequests  # noqa: F401
        import app.models.companySettings  # noqa: F401
        import app.models.ticket  # noqa: F401
        Base.metadata.create_all(bind=engine)
    yield


@pytest.fixture(scope="session")
def client():
    with TestClient(app) as c:
        yield c


@pytest.fixture(scope="session")
def company(client: TestClient):
    payload = {
        "company_name": "E2E Test Corp",
        "company_email": "e2e-test@perai.test",
        "password": "testpass1234",
        "website": "https://perai.test",
    }
    r = client.post("/api/v1/auth/register", json=payload)
    assert r.status_code == 201, r.text
    data = r.json()
    yield data
    # cleanup — delete company after all session tests finish
    login_r = client.post(
        "/api/v1/auth/login",
        json={"email": payload["company_email"], "password": payload["password"]},
    )
    token = login_r.json().get("access_token", "")
    client.delete(
        f"/api/v1/company/{data['id']}",
        headers={"Authorization": f"Bearer {token}"},
    )


@pytest.fixture(scope="session")
def jwt(client: TestClient, company: dict):
    r = client.post(
        "/api/v1/auth/login",
        json={"email": company["company_email"], "password": "testpass1234"},
    )
    assert r.status_code == 200, r.text
    return r.json()["access_token"]


@pytest.fixture(scope="session")
def auth_headers(jwt: str):
    return {"Authorization": f"Bearer {jwt}"}


@pytest.fixture(scope="session")
def api_key(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/api-keys",
        json={"name": "e2e-test-key"},
        headers=auth_headers,
    )
    assert r.status_code == 201, r.text
    return r.json()["key"]


@pytest.fixture(scope="session")
def api_key_headers(api_key: str):
    return {"X-API-Key": api_key}
