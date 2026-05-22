# Frontend Integration Guide - Clean Minimal API

## Complete Flow in 5 Steps

### Step 1: Create Company
```python
import requests

BASE_URL = "http://localhost:8000"

# Create company (no API key needed for first company)
company = requests.post(f"{BASE_URL}/api/v1/company", json={
    "company_name": "MyCompany",
    "company_email": "contact@mycompany.com",
    "password": "SecurePass@123"
}).json()

company_id = company["id"]
print(f"Company created: {company_id}")
```

### Step 2: Create API Key
```python
# Create API key for future requests
from datetime import datetime, timedelta

api_key_response = requests.post(
    f"{BASE_URL}/api/v1/company/{company_id}/api-keys",
    headers={"X-API-Key": "dummy"},  # Dummy for first request
    json={
        "name": "production",
        "expiry_date": (datetime.utcnow() + timedelta(days=90)).isoformat()
    }
).json()

API_KEY = api_key_response["key"]  # Store this securely!
print(f"API Key: {API_KEY}")
```

### Step 3: Upload Finetune Data
```python
# Upload company knowledge base
finetune_response = requests.post(
    f"{BASE_URL}/api/v1/company/{company_id}/finetune",
    headers={"X-API-Key": API_KEY},
    json={
        "content": """# MyCompany Knowledge Base

## About
We provide AI solutions.

## Services
- Consulting
- Development
- Support
"""
    }
).json()

model_name = finetune_response["company_model_name"]
print(f"Model Name: {model_name}")  # e.g., "perai-mycompany"
```

### Step 4: Get Company Details (Optional)
```python
# Get company info including model_name
company_details = requests.get(
    f"{BASE_URL}/api/v1/company/{company_id}",
    headers={"X-API-Key": API_KEY}
).json()

model_name = company_details["company_model_name"]
print(f"Company: {company_details['company_name']}")
print(f"Model: {model_name}")
```

### Step 5: Chat Query
```python
# Query the model with company's finetune data
response = requests.post(
    f"{BASE_URL}/api/v1/company/{company_id}/chat/query",
    headers={"X-API-Key": API_KEY},
    json={"prompt": "What services do you offer?"}
).json()

print(f"Model: {response['model_name']}")
print(f"Response: {response['response']}")
```

---

## API Endpoints

### 1. CREATE COMPANY
```
POST /api/v1/company
Content-Type: application/json

{
  "company_name": "string",
  "company_email": "string",
  "password": "string"
}

Response (201 Created):
{
  "id": int,
  "company_name": "string",
  "company_email": "string",
  "company_model_name": null,
  "logo": null,
  "website": null,
  "created_at": "2026-05-22T17:56:30Z",
  "updated_at": "2026-05-22T17:56:30Z"
}
```

### 2. CREATE API KEY
```
POST /api/v1/company/{company_id}/api-keys
X-API-Key: dummy (for first request only)
Content-Type: application/json

{
  "name": "string",
  "expiry_date": "2026-08-22T00:00:00"
}

Response (201 Created):
{
  "id": int,
  "company_id": int,
  "name": "string",
  "key": "sk_...",  # FULL KEY - SAVE THIS!
  "key_preview": "sk_Q...fYFQ",
  "status": "active",
  "expiry_date": "2026-08-22T00:00:00Z",
  "created_at": "2026-05-22T17:56:31Z"
}
```

### 3. UPLOAD FINETUNE
```
POST /api/v1/company/{company_id}/finetune
X-API-Key: sk_...
Content-Type: application/json

{
  "content": "# Knowledge Base\n\n## Section\nContent"
}

Response (201 Created):
{
  "id": int,
  "company_id": int,
  "company_model_name": "perai-{company_name}",  # Auto-generated!
  "rag_company_path": "/path/to/file",
  "created_at": "2026-05-22T17:56:32Z",
  "updated_at": "2026-05-22T17:56:32Z"
}
```

### 4. GET COMPANY DETAILS
```
GET /api/v1/company/{company_id}
X-API-Key: sk_...

Response (200 OK):
{
  "id": int,
  "company_name": "string",
  "company_email": "string",
  "company_model_name": "perai-{company_name}",  # After finetune upload!
  "logo": null,
  "website": null,
  "created_at": "2026-05-22T17:56:30Z",
  "updated_at": "2026-05-22T17:56:30Z"
}
```

### 5. CHAT QUERY (MAIN ENDPOINT)
```
POST /api/v1/company/{company_id}/chat/query
X-API-Key: sk_...
Content-Type: application/json

{
  "prompt": "What services do you offer?"
}

Response (200 OK):
{
  "model_name": "perai-{company_name}",
  "company_id": int,
  "response": "The AI response based on company finetune data"
}
```

---

## Response Summary

| Field | Endpoint | Value | Purpose |
|-------|----------|-------|---------|
| `id` | All | auto | Company unique identifier |
| `company_id` | Most | auto | References company |
| `company_model_name` | Finetune/Company | `perai-{name}` | Model name for queries |
| `key` | Create API Key | `sk_{random}` | API authentication (save once!) |
| `key_preview` | List API Keys | `sk_Q...fYFQ` | Public identifier |
| `response` | Chat Query | AI text | Answer from company model |
| `model_name` | Chat Query | `perai-{name}` | Which model answered |

---

## Frontend Example (JavaScript)

```javascript
const BASE_URL = 'http://localhost:8000';
let API_KEY = null;
let COMPANY_ID = null;
let MODEL_NAME = null;

// 1. Create company
async function createCompany() {
  const res = await fetch(`${BASE_URL}/api/v1/company`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      company_name: 'MyCompany',
      company_email: 'contact@mycompany.com',
      password: 'SecurePass@123'
    })
  });
  const data = await res.json();
  COMPANY_ID = data.id;
  return data;
}

// 2. Create API key
async function createAPIKey() {
  const expiryDate = new Date();
  expiryDate.setDate(expiryDate.getDate() + 90);
  
  const res = await fetch(
    `${BASE_URL}/api/v1/company/${COMPANY_ID}/api-keys`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'dummy'
      },
      body: JSON.stringify({
        name: 'production',
        expiry_date: expiryDate.toISOString()
      })
    }
  );
  const data = await res.json();
  API_KEY = data.key; // SAVE THIS!
  return data;
}

// 3. Upload finetune
async function uploadFinetune() {
  const res = await fetch(
    `${BASE_URL}/api/v1/company/${COMPANY_ID}/finetune`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': API_KEY
      },
      body: JSON.stringify({
        content: `# MyCompany\n\n## Services\n- Service A\n- Service B`
      })
    }
  );
  const data = await res.json();
  MODEL_NAME = data.company_model_name;
  return data;
}

// 4. Chat query
async function chatQuery(prompt) {
  const res = await fetch(
    `${BASE_URL}/api/v1/company/${COMPANY_ID}/chat/query`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': API_KEY
      },
      body: JSON.stringify({ prompt })
    }
  );
  return await res.json();
}

// Usage
(async () => {
  await createCompany();
  await createAPIKey();
  await uploadFinetune();
  
  const result = await chatQuery('What services do you offer?');
  console.log(`Model: ${result.model_name}`);
  console.log(`Response: ${result.response}`);
})();
```

---

## Key Points for Frontend

1. **Company ID**: Save after Step 1
2. **API Key**: Save securely after Step 2 (shown only once!)
3. **Model Name**: Auto-generated as `perai-{company_name}` after Step 3
4. **Chat Query**: Use model_name from response for reference

---

## Clean Minimal Test

```bash
# Set variables
export BASE_URL="http://localhost:8000"
export COMPANY_ID="13"
export API_KEY="sk_QoJbBDaj5mk028xA6H3bY8PQbI0UmOe4ilzqifpfYFQ"

# Query chat
curl -X POST "$BASE_URL/api/v1/company/$COMPANY_ID/chat/query" \
  -H "X-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"prompt": "What services do you offer?"}' | jq .
```

Expected response:
```json
{
  "model_name": "perai-pureai",
  "company_id": 13,
  "response": "At PureAI, we offer..."
}
```

---

**Last Updated**: 2026-05-22
**Version**: 1.0 - Production Ready
