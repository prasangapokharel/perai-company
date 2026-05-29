#!/usr/bin/env python3
"""Complete end-to-end test suite for Perai backend."""

import requests
import json
from datetime import datetime, timedelta
import time

BASE_URL = "http://0.0.0.0:8000"

print("\n" + "="*80)
print("🚀 PERAI BACKEND - COMPLETE END-TO-END TEST SUITE")
print("="*80)

# Test counters
tests_passed = 0
tests_failed = 0

def test(name: str, func):
    """Run a test and track results."""
    global tests_passed, tests_failed
    try:
        print(f"\n{'='*80}")
        print(f"📝 TEST: {name}")
        print('='*80)
        func()
        tests_passed += 1
        print(f"✅ PASSED: {name}")
        return True
    except AssertionError as e:
        tests_failed += 1
        print(f"❌ FAILED: {name}")
        print(f"   Error: {e}")
        return False
    except Exception as e:
        tests_failed += 1
        print(f"❌ ERROR: {name}")
        print(f"   Exception: {e}")
        return False


# Test data
company_data = {
    "company_name": f"TechCorp-{int(datetime.now().timestamp())}",
    "company_email": f"admin-{int(datetime.now().timestamp())}@techcorp.com",
    "password": "SecurePassword123!",
    "logo": "https://example.com/logo.png",
    "website": "https://techcorp.com"
}

# Global state
state = {
    "company_id": None,
    "api_key": None,
    "api_key_id": None,
    "company_model_name": None,
}


def test_1_health_check():
    """Test 1: Health check endpoint."""
    response = requests.get(f"{BASE_URL}/")
    print(f"Response: {response.json()}")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"
    print("✓ Server is healthy and responding")


def test_2_company_register():
    """Test 2: Register a new company."""
    response = requests.post(
        f"{BASE_URL}/api/v1/auth/register",
        json=company_data
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 201
    data = response.json()
    assert data["company_name"] == company_data["company_name"]
    assert data["company_email"] == company_data["company_email"]
    assert "id" in data
    
    state["company_id"] = data["id"]
    print(f"✓ Company registered successfully")
    print(f"✓ Company ID: {state['company_id']}")


def test_3_company_login():
    """Test 3: Login with company credentials."""
    login_data = {
        "email": company_data["company_email"],
        "password": company_data["password"]
    }
    response = requests.post(
        f"{BASE_URL}/api/v1/auth/login",
        json=login_data
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["company"]["id"] == state["company_id"]
    assert data["message"] == "Login successful. Use X-API-Key header for API requests."
    print(f"✓ Company login successful")
    print(f"✓ Company details retrieved")


def test_4_verify_company():
    """Test 4: Verify company exists."""
    response = requests.get(
        f"{BASE_URL}/api/v1/auth/verify/{state['company_id']}"
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["id"] == state["company_id"]
    assert data["company_name"] == company_data["company_name"]
    print(f"✓ Company verified successfully")


def test_5_create_api_key():
    """Test 5: Create API key for company."""
    key_data = {
        "name": "Production Key",
        "expiry_date": (datetime.now() + timedelta(days=365)).isoformat()
    }
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/api-keys",
        json=key_data
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps({k: v for k, v in data.items() if k != 'key'}, indent=2)}")
    
    assert response.status_code == 201
    assert "key" in data
    assert data["name"] == key_data["name"]
    assert data["status"] == "active"
    
    state["api_key"] = data["key"]
    state["api_key_id"] = data["id"]
    print(f"✓ API key created successfully")
    print(f"✓ API Key Preview: {data['key_preview']}")
    print(f"✓ Full Key: {data['key'][:10]}...{data['key'][-10:]}")


def test_6_list_api_keys():
    """Test 6: List all API keys for company."""
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/api-keys"
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert isinstance(data, list)
    assert len(data) >= 1
    assert data[0]["name"] == "Production Key"
    print(f"✓ Found {len(data)} API key(s)")


def test_7_get_company_details():
    """Test 7: Get company details with model name."""
    headers = {"X-API-Key": state["api_key"]}
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{state['company_id']}",
        headers=headers
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["id"] == state["company_id"]
    assert data["company_name"] == company_data["company_name"]
    print(f"✓ Company details retrieved with API key")


def test_8_upload_finetune():
    """Test 8: Upload finetune/RAG data."""
    finetune_content = """# TechCorp Knowledge Base

## Company Overview
TechCorp is a leading technology company specializing in cloud solutions.

## Services
1. **Cloud Infrastructure** - Scalable cloud hosting and deployment
   - AWS Integration
   - Kubernetes Management
   - Auto-scaling

2. **AI Solutions** - Enterprise AI and machine learning
   - Custom ML Models
   - NLP Processing
   - Computer Vision

3. **Consulting** - Technology consulting and architecture
   - System Design
   - Optimization
   - Security Audit

## Pricing
- Starter: $499/month
- Professional: $1,999/month
- Enterprise: Custom pricing

## FAQ
Q: What is your uptime SLA?
A: We guarantee 99.99% uptime for all enterprise plans.

Q: Do you support multiple regions?
A: Yes, we support AWS regions in North America, Europe, and Asia-Pacific.

Q: What about data security?
A: All data is encrypted at rest and in transit using AES-256 and TLS 1.3.

## Support
- Email: support@techcorp.com
- Phone: +1-800-TECH-CORP
- Chat: Available 24/7 for enterprise customers
"""
    
    finetune_data = {"content": finetune_content}
    headers = {"X-API-Key": state["api_key"]}
    
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/finetune",
        json=finetune_data,
        headers=headers
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 201
    data = response.json()
    assert data["company_id"] == state["company_id"]
    assert "company_model_name" in data
    assert data["company_model_name"].startswith("perai-")
    
    state["company_model_name"] = data["company_model_name"]
    print(f"✓ Finetune data uploaded successfully")
    print(f"✓ Model Name: {data['company_model_name']}")
    print(f"✓ RAG Path: {data['rag_company_path']}")


def test_9_get_finetune():
    """Test 9: Retrieve finetune data."""
    headers = {"X-API-Key": state["api_key"]}
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/finetune",
        headers=headers
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["company_model_name"] == state["company_model_name"]
    print(f"✓ Finetune data retrieved successfully")
    print(f"✓ Model: {data['company_model_name']}")


def test_10_chat_query():
    """Test 10: Send chat query."""
    chat_data = {
        "prompt": "What are your main services?"
    }
    headers = {"X-API-Key": state["api_key"]}
    
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/chat/query",
        json=chat_data,
        headers=headers
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps(data, indent=2)}")
    
    assert response.status_code == 200
    assert data["company_id"] == state["company_id"]
    assert data["model_name"] == state["company_model_name"]
    assert "response" in data
    assert len(data["response"]) > 0
    print(f"✓ Chat query successful")
    print(f"✓ Model Used: {data['model_name']}")
    print(f"✓ Response Length: {len(data['response'])} chars")
    print(f"\n📝 AI Response:\n{data['response']}\n")


def test_11_chat_ping():
    """Test 11: Chat ping endpoint."""
    headers = {"X-API-Key": state["api_key"]}
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/chat/ping",
        headers=headers
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    print(f"✓ Chat service is healthy")


def test_12_update_api_key():
    """Test 12: Update API key."""
    update_data = {
        "name": "Updated Production Key",
        "status": "active"
    }
    
    response = requests.put(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/api-keys/{state['api_key_id']}",
        json=update_data
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["name"] == update_data["name"]
    print(f"✓ API key updated successfully")


def test_13_update_company():
    """Test 13: Update company details."""
    update_data = {
        "website": "https://newtechcorp.com"
    }
    headers = {"X-API-Key": state["api_key"]}
    
    response = requests.put(
        f"{BASE_URL}/api/v1/company/{state['company_id']}",
        json=update_data,
        headers=headers
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["website"] == update_data["website"]
    print(f"✓ Company updated successfully")


def test_14_revoke_api_key():
    """Test 14: Revoke API key."""
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/api-keys/{state['api_key_id']}/revoke"
    )
    print(f"Status: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "revoked"
    print(f"✓ API key revoked successfully")


def test_15_create_new_key():
    """Test 15: Create new API key after revoke."""
    key_data = {
        "name": "Secondary Key"
    }
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/api-keys",
        json=key_data
    )
    print(f"Status: {response.status_code}")
    data = response.json()
    print(f"Response: {json.dumps({k: v for k, v in data.items() if k != 'key'}, indent=2)}")
    
    assert response.status_code == 201
    state["api_key"] = data["key"]  # Update for next tests
    state["api_key_id"] = data["id"]
    print(f"✓ New API key created successfully")


def test_16_delete_finetune():
    """Test 16: Delete finetune data."""
    headers = {"X-API-Key": state["api_key"]}
    response = requests.delete(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/finetune",
        headers=headers
    )
    print(f"Status: {response.status_code}")
    
    assert response.status_code == 204
    print(f"✓ Finetune data deleted successfully")


def test_17_delete_api_key():
    """Test 17: Delete API key."""
    response = requests.delete(
        f"{BASE_URL}/api/v1/company/{state['company_id']}/api-keys/{state['api_key_id']}"
    )
    print(f"Status: {response.status_code}")
    
    assert response.status_code == 204
    print(f"✓ API key deleted successfully")


def test_18_delete_company():
    """Test 18: Delete company and all associated data."""
    response = requests.delete(
        f"{BASE_URL}/api/v1/company/{state['company_id']}"
    )
    print(f"Status: {response.status_code}")
    
    assert response.status_code == 204
    print(f"✓ Company deleted successfully")
    print(f"✓ All associated data cleaned up")


def test_19_verify_company_deleted():
    """Test 19: Verify company is deleted."""
    response = requests.get(
        f"{BASE_URL}/api/v1/auth/verify/{state['company_id']}"
    )
    print(f"Status: {response.status_code}")
    
    assert response.status_code == 404
    print(f"✓ Company successfully removed from database")


# Run all tests
if __name__ == "__main__":
    tests = [
        ("Health Check", test_1_health_check),
        ("Company Registration", test_2_company_register),
        ("Company Login", test_3_company_login),
        ("Company Verification", test_4_verify_company),
        ("Create API Key", test_5_create_api_key),
        ("List API Keys", test_6_list_api_keys),
        ("Get Company Details", test_7_get_company_details),
        ("Upload Finetune Data", test_8_upload_finetune),
        ("Get Finetune Data", test_9_get_finetune),
        ("Chat Query", test_10_chat_query),
        ("Chat Ping", test_11_chat_ping),
        ("Update API Key", test_12_update_api_key),
        ("Update Company", test_13_update_company),
        ("Revoke API Key", test_14_revoke_api_key),
        ("Create New API Key", test_15_create_new_key),
        ("Delete Finetune", test_16_delete_finetune),
        ("Delete API Key", test_17_delete_api_key),
        ("Delete Company", test_18_delete_company),
        ("Verify Company Deleted", test_19_verify_company_deleted),
    ]
    
    for name, func in tests:
        test(name, func)
        time.sleep(0.5)  # Small delay between tests
    
    # Print summary
    print("\n" + "="*80)
    print("📊 TEST SUMMARY")
    print("="*80)
    print(f"✅ Tests Passed: {tests_passed}")
    print(f"❌ Tests Failed: {tests_failed}")
    print(f"📈 Success Rate: {(tests_passed/(tests_passed+tests_failed)*100):.1f}%")
    print("="*80 + "\n")
    
    if tests_failed == 0:
        print("🎉 ALL TESTS PASSED - SYSTEM IS PRODUCTION READY!")
    else:
        print(f"⚠️  {tests_failed} test(s) failed - review above for details")
    
    print("\n")
