"""Comprehensive API testing script."""

import requests
import json
from datetime import datetime, timedelta

BASE_URL = "http://127.0.0.1:8000"
HEADERS = {"Content-Type": "application/json"}

# Colors for terminal output
GREEN = "\033[92m"
RED = "\033[91m"
YELLOW = "\033[93m"
RESET = "\033[0m"


def print_test(name: str, passed: bool, details: str = ""):
    """Print test result."""
    status = f"{GREEN}✓ PASS{RESET}" if passed else f"{RED}✗ FAIL{RESET}"
    print(f"  {status} {name}")
    if details:
        print(f"    {details}")


def test_health_check():
    """Test health check endpoint."""
    print(f"\n{YELLOW}Testing Health Check{RESET}")
    try:
        resp = requests.get(f"{BASE_URL}/")
        passed = resp.status_code == 200
        print_test("GET /", passed)
        return passed
    except Exception as e:
        print_test("GET /", False, str(e))
        return False


def test_company_create():
    """Test company creation."""
    print(f"\n{YELLOW}Testing Company CRUD{RESET}")
    payload = {
        "company_name": f"TestCo_{datetime.now().timestamp()}",
        "company_email": f"test_{datetime.now().timestamp()}@example.com",
        "password": "SecurePass123!",
        "logo": "https://example.com/logo.png",
        "website": "https://example.com",
    }

    try:
        resp = requests.post(
            f"{BASE_URL}/api/v1/company",
            json=payload,
            headers=HEADERS,
        )
        passed = resp.status_code == 201
        print_test("POST /api/v1/company", passed, f"Company ID: {resp.json().get('id')}")

        if passed:
            return resp.json()["id"], resp.json()
        return None, None
    except Exception as e:
        print_test("POST /api/v1/company", False, str(e))
        return None, None


def test_company_list():
    """Test company list."""
    try:
        resp = requests.get(f"{BASE_URL}/api/v1/company", headers=HEADERS)
        passed = resp.status_code == 200
        count = len(resp.json()) if passed else 0
        print_test("GET /api/v1/company", passed, f"Companies: {count}")
        return passed
    except Exception as e:
        print_test("GET /api/v1/company", False, str(e))
        return False


def test_company_get(company_id: int):
    """Test get company."""
    try:
        resp = requests.get(f"{BASE_URL}/api/v1/company/{company_id}", headers=HEADERS)
        passed = resp.status_code == 200
        print_test(f"GET /api/v1/company/{company_id}", passed)
        return passed
    except Exception as e:
        print_test(f"GET /api/v1/company/{company_id}", False, str(e))
        return False


def test_company_update(company_id: int):
    """Test update company."""
    payload = {"company_name": f"UpdatedCo_{datetime.now().timestamp()}"}

    try:
        resp = requests.put(
            f"{BASE_URL}/api/v1/company/{company_id}",
            json=payload,
            headers=HEADERS,
        )
        passed = resp.status_code == 200
        print_test(f"PUT /api/v1/company/{company_id}", passed)
        return passed
    except Exception as e:
        print_test(f"PUT /api/v1/company/{company_id}", False, str(e))
        return False


def test_api_key_create(company_id: int):
    """Test API key creation."""
    print(f"\n{YELLOW}Testing API Key CRUD{RESET}")
    payload = {
        "name": f"TestKey_{datetime.now().timestamp()}",
        "expiry_date": (datetime.utcnow() + timedelta(days=30)).isoformat(),
    }

    try:
        resp = requests.post(
            f"{BASE_URL}/api/v1/company/{company_id}/api-keys",
            json=payload,
            headers=HEADERS,
        )
        passed = resp.status_code == 201
        if passed:
            data = resp.json()
            print_test("POST /api/v1/company/{id}/api-keys", passed, f"Key ID: {data['id']}, Preview: {data['key_preview']}")
            return data["id"], data["key"]
        else:
            print_test("POST /api/v1/company/{id}/api-keys", passed, f"Error: {resp.text}")
            return None, None
    except Exception as e:
        print_test("POST /api/v1/company/{id}/api-keys", False, str(e))
        return None, None


def test_api_key_list(company_id: int):
    """Test list API keys."""
    try:
        resp = requests.get(
            f"{BASE_URL}/api/v1/company/{company_id}/api-keys",
            headers=HEADERS,
        )
        passed = resp.status_code == 200
        count = len(resp.json()) if passed else 0
        print_test(f"GET /api/v1/company/{company_id}/api-keys", passed, f"Keys: {count}")
        return passed
    except Exception as e:
        print_test(f"GET /api/v1/company/{company_id}/api-keys", False, str(e))
        return False


def test_api_key_get(company_id: int, key_id: int):
    """Test get API key."""
    try:
        resp = requests.get(
            f"{BASE_URL}/api/v1/company/{company_id}/api-keys/{key_id}",
            headers=HEADERS,
        )
        passed = resp.status_code == 200
        print_test(f"GET /api/v1/company/{company_id}/api-keys/{key_id}", passed)
        return passed
    except Exception as e:
        print_test(f"GET /api/v1/company/{company_id}/api-keys/{key_id}", False, str(e))
        return False


def test_api_key_update(company_id: int, key_id: int):
    """Test update API key."""
    payload = {
        "name": f"UpdatedKey_{datetime.now().timestamp()}",
    }

    try:
        resp = requests.put(
            f"{BASE_URL}/api/v1/company/{company_id}/api-keys/{key_id}",
            json=payload,
            headers=HEADERS,
        )
        passed = resp.status_code == 200
        print_test(f"PUT /api/v1/company/{company_id}/api-keys/{key_id}", passed)
        return passed
    except Exception as e:
        print_test(f"PUT /api/v1/company/{company_id}/api-keys/{key_id}", False, str(e))
        return False


def test_api_key_revoke(company_id: int, key_id: int):
    """Test revoke API key."""
    try:
        resp = requests.post(
            f"{BASE_URL}/api/v1/company/{company_id}/api-keys/{key_id}/revoke",
            headers=HEADERS,
        )
        passed = resp.status_code == 200
        if passed:
            status = resp.json().get("status")
            print_test(f"POST /api/v1/company/{company_id}/api-keys/{key_id}/revoke", passed, f"Status: {status}")
        return passed
    except Exception as e:
        print_test(f"POST /api/v1/company/{company_id}/api-keys/{key_id}/revoke", False, str(e))
        return False


def test_api_key_delete(company_id: int, key_id: int):
    """Test delete API key."""
    try:
        resp = requests.delete(
            f"{BASE_URL}/api/v1/company/{company_id}/api-keys/{key_id}",
            headers=HEADERS,
        )
        passed = resp.status_code == 204
        print_test(f"DELETE /api/v1/company/{company_id}/api-keys/{key_id}", passed)
        return passed
    except Exception as e:
        print_test(f"DELETE /api/v1/company/{company_id}/api-keys/{key_id}", False, str(e))
        return False


def main():
    """Run all tests."""
    print(f"\n{YELLOW}{'='*60}")
    print(f"COMPREHENSIVE API KEY TESTING SUITE")
    print(f"{'='*60}{RESET}")

    # Health check
    if not test_health_check():
        print(f"\n{RED}Server not running! Start server with:{RESET}")
        print("  cd backend && python3 -m uvicorn app.main:app --host 127.0.0.1 --port 8000")
        return

    # Company tests
    company_id, company_data = test_company_create()
    if not company_id:
        print(f"\n{RED}Company creation failed!{RESET}")
        return

    test_company_list()
    test_company_get(company_id)
    test_company_update(company_id)

    # API Key tests
    key_id, full_key = test_api_key_create(company_id)
    if not key_id:
        print(f"\n{RED}API Key creation failed!{RESET}")
        return

    print(f"\n{YELLOW}⚠️  Full API Key (Only shown once):{RESET}")
    print(f"  {full_key}")

    test_api_key_list(company_id)
    test_api_key_get(company_id, key_id)
    test_api_key_update(company_id, key_id)

    # Test with second key for revoke/delete
    key_id_2, _ = test_api_key_create(company_id)
    if key_id_2:
        test_api_key_revoke(company_id, key_id_2)

    key_id_3, _ = test_api_key_create(company_id)
    if key_id_3:
        test_api_key_delete(company_id, key_id_3)

    print(f"\n{YELLOW}{'='*60}")
    print(f"ALL TESTS COMPLETED")
    print(f"{'='*60}{RESET}\n")


if __name__ == "__main__":
    main()
