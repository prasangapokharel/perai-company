#!/usr/bin/env python3
"""Comprehensive API test suite for Perai backend."""

import requests
import json
from datetime import datetime

BASE_URL = "http://127.0.0.1:8000"

# Test data
test_company = {
    "company_name": f"TestCorp-{datetime.now().timestamp()}",
    "company_email": f"test-{datetime.now().timestamp()}@testcorp.com",
    "password": "SecurePassword123!",
    "logo": "https://example.com/logo.png",
    "website": "https://testcorp.com"
}

company_id = None
api_key = None
api_key_id = None

def test_health():
    """Test health check endpoint."""
    print("\n🏥 TEST: Health Check")
    response = requests.get(f"{BASE_URL}/")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    print(f"✓ Health check passed: {data}")

def test_company_register():
    """Test company registration."""
    global company_id
    print("\n📝 TEST: Company Registration")
    response = requests.post(
        f"{BASE_URL}/api/v1/auth/register",
        json=test_company
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 201
    assert data["company_name"] == test_company["company_name"]
    assert data["company_email"] == test_company["company_email"]
    company_id = data["id"]
    print(f"✓ Company registered with ID: {company_id}")

def test_company_login():
    """Test company login."""
    print("\n🔐 TEST: Company Login")
    login_data = {
        "email": test_company["company_email"],
        "password": test_company["password"]
    }
    response = requests.post(
        f"{BASE_URL}/api/v1/auth/login",
        json=login_data
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert data["company"]["id"] == company_id
    print(f"✓ Company login successful")

def test_company_verify():
    """Test company verification."""
    print(f"\n✔️  TEST: Company Verification (ID: {company_id})")
    response = requests.get(
        f"{BASE_URL}/api/v1/auth/verify/{company_id}"
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert data["id"] == company_id
    print(f"✓ Company verification passed")

def test_api_key_create():
    """Test API key creation."""
    global api_key, api_key_id
    print(f"\n🔑 TEST: API Key Creation (Company: {company_id})")
    
    key_data = {
        "name": "Test Key"
    }
    response = requests.post(
        f"{BASE_URL}/api/v1/apikey/create/{company_id}",
        json=key_data
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 201
    assert data["name"] == key_data["name"]
    assert "key" in data
    api_key = data["key"]
    api_key_id = data["id"]
    print(f"✓ API key created: {data['key_preview']}")

def test_api_key_list():
    """Test API key listing."""
    print(f"\n📋 TEST: List API Keys (Company: {company_id})")
    response = requests.get(
        f"{BASE_URL}/api/v1/apikey/list/{company_id}"
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert isinstance(data, list)
    assert len(data) >= 1
    print(f"✓ Found {len(data)} API key(s)")

def test_finetune_upload():
    """Test finetune upload."""
    print(f"\n📚 TEST: Finetune Upload (Company: {company_id})")
    
    finetune_data = {
        "content": """# Test Company Knowledge

## Overview
This is a test company knowledge base.

## Services
- Service 1: Description
- Service 2: Description

## FAQ
Q: How does it work?
A: It works by processing your data.
"""
    }
    
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{company_id}/finetune",
        json=finetune_data,
        headers={"X-API-Key": api_key}
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert data["company_id"] == company_id
    assert data["company_model_name"].startswith("perai-")
    print(f"✓ Finetune uploaded with model: {data['company_model_name']}")

def test_company_details():
    """Test company details with model name."""
    print(f"\n🏢 TEST: Company Details (ID: {company_id})")
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}",
        headers={"X-API-Key": api_key}
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert data["id"] == company_id
    assert data["company_model_name"] is not None
    print(f"✓ Company details retrieved with model: {data['company_model_name']}")

def test_chat_query():
    """Test chat query."""
    print(f"\n💬 TEST: Chat Query (Company: {company_id})")
    
    chat_data = {
        "prompt": "What services do you offer?"
    }
    
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{company_id}/chat/query",
        json=chat_data,
        headers={"X-API-Key": api_key}
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert data["company_id"] == company_id
    assert "model_name" in data
    assert "response" in data
    print(f"✓ Chat query successful using model: {data['model_name']}")

def run_all_tests():
    """Run all tests in sequence."""
    print("=" * 60)
    print("🚀 PERAI BACKEND - COMPREHENSIVE API TEST SUITE")
    print("=" * 60)
    
    try:
        test_health()
        test_company_register()
        test_company_login()
        test_company_verify()
        test_api_key_create()
        test_api_key_list()
        test_finetune_upload()
        test_company_details()
        test_chat_query()
        
        print("\n" + "=" * 60)
        print("✅ ALL TESTS PASSED!")
        print("=" * 60)
        
    except AssertionError as e:
        print(f"\n❌ TEST FAILED: {e}")
        return False
    except Exception as e:
        print(f"\n❌ ERROR: {e}")
        return False
    
    return True

if __name__ == "__main__":
    import sys
    success = run_all_tests()
    sys.exit(0 if success else 1)
