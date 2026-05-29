# API Key Endpoints

Manage API keys for authenticated requests. Each key allows access to all company resources and should be kept secure.

---

## API Key Lifecycle

```
Create → List → Get → Use (in X-API-Key header) → Update → Revoke → Delete

Statuses:
- active: Can be used for requests
- revoked: Cannot be used
- expired: Cannot be used (expiry_date has passed)
```

---

## 1. Create API Key

Create a new API key for the company.

### Endpoint
```
POST /api/v1/company/{company_id}/api-keys
```

### Headers
```
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Request Body
```json
{
  "name": "Production Key",
  "expiry_date": "2027-05-29T14:00:00Z"
}
```

### Request Fields
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | Yes | Human-readable key name |
| `expiry_date` | string | No | ISO 8601 timestamp for expiration (e.g., 1 year from now) |

### Response (201 Created)
```json
{
  "id": 5,
  "company_id": 1,
  "name": "Production Key",
  "key_preview": "sk_A...Yz5",
  "status": "active",
  "expiry_date": "2027-05-29T14:00:00Z",
  "last_used_at": null,
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T14:00:00Z"
}
```

### Important: Full Key in Response

The response body contains **one additional field** `key`:

```json
{
  "id": 5,
  "company_id": 1,
  "name": "Production Key",
  "key": "sk_RRFCHcvl3fYk7wX9qM4nZ2pL8jB5dH6tQ1vW0aY9E4",
  "key_preview": "sk_R...Y9E4",
  "status": "active",
  "expiry_date": "2027-05-29T14:00:00Z",
  "last_used_at": null,
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T14:00:00Z"
}
```

**⚠️ IMPORTANT: The full key is shown only once at creation. Save it immediately.**

You will only ever see the preview (`sk_R...Y9E4`) in future requests. To use the key, store it securely:

```javascript
// In browser: localStorage (or sessionStorage)
localStorage.setItem('perai_api_key', response.key);

// In Node.js: Environment variable or secure store
process.env.PERAI_API_KEY = response.key;
```

### Response Fields
| Field | Type | Notes |
|-------|------|-------|
| `key` | string | Full API key (48 chars, format: `sk_{random}`) - shown only once |
| `key_preview` | string | Safe preview for display (`sk_X...4chars`) |
| `status` | string | `active`, `revoked`, or `expired` |
| `expiry_date` | string\|null | Expiration time, or null for no expiry |

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Production Key",
    "expiry_date": "2027-05-29T14:00:00Z"
  }'
```

### Example JavaScript
```javascript
async function createApiKey() {
  const response = await fetch('http://localhost:8000/api/v1/company/1/api-keys', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: 'Production Key',
      expiry_date: new Date(Date.now() + 365*24*60*60*1000).toISOString() // 1 year from now
    })
  });

  const result = await response.json();
  
  // ⚠️ SAVE THE KEY IMMEDIATELY
  localStorage.setItem('perai_api_key', result.key);
  
  console.log('Key created:', result.name);
  console.log('Preview:', result.key_preview);
}
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `400` | `"Company ID must be positive"` | Invalid company_id |
| `404` | `"Company not found"` | Company doesn't exist |

---

## 2. List API Keys

List all API keys for a company.

### Endpoint
```
GET /api/v1/company/{company_id}/api-keys
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Response (200 OK)
```json
[
  {
    "id": 5,
    "company_id": 1,
    "name": "Production Key",
    "key_preview": "sk_A...Yz5",
    "status": "active",
    "expiry_date": "2027-05-29T14:00:00Z",
    "last_used_at": "2026-05-29T14:30:00Z",
    "created_at": "2026-05-29T14:00:00Z",
    "updated_at": "2026-05-29T14:00:00Z"
  },
  {
    "id": 6,
    "company_id": 1,
    "name": "Development Key",
    "key_preview": "sk_B...Kl2",
    "status": "active",
    "expiry_date": null,
    "last_used_at": null,
    "created_at": "2026-05-29T14:30:00Z",
    "updated_at": "2026-05-29T14:30:00Z"
  }
]
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/api-keys
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/api-keys');
const keys = await response.json();

keys.forEach(key => {
  console.log(`${key.name}: ${key.key_preview} (${key.status})`);
});
```

---

## 3. Get API Key Details

Retrieve details for a specific API key.

### Endpoint
```
GET /api/v1/company/{company_id}/api-keys/{key_id}
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `key_id` | integer | Yes |

### Response (200 OK)
```json
{
  "id": 5,
  "company_id": 1,
  "name": "Production Key",
  "key_preview": "sk_A...Yz5",
  "status": "active",
  "expiry_date": "2027-05-29T14:00:00Z",
  "last_used_at": "2026-05-29T14:30:00Z",
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T14:00:00Z"
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/api-keys/5
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/api-keys/5');
const key = await response.json();

console.log(`Key: ${key.key_preview}`);
console.log(`Status: ${key.status}`);
console.log(`Last used: ${key.last_used_at || 'Never'}`);
```

---

## 4. Update API Key

Update API key name or status.

### Endpoint
```
PUT /api/v1/company/{company_id}/api-keys/{key_id}
```

### Headers
```
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `key_id` | integer | Yes |

### Request Body (all fields optional)
```json
{
  "name": "Updated Production Key",
  "status": "active"
}
```

### Request Fields
| Field | Type | Allowed Values | Notes |
|-------|------|---|-------|
| `name` | string | Any | New key name |
| `status` | string | `active`, `revoked` | Change key status |

### Response (200 OK)
```json
{
  "id": 5,
  "company_id": 1,
  "name": "Updated Production Key",
  "key_preview": "sk_A...Yz5",
  "status": "active",
  "expiry_date": "2027-05-29T14:00:00Z",
  "last_used_at": "2026-05-29T14:30:00Z",
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T15:00:00Z"
}
```

### Example cURL
```bash
curl -X PUT http://localhost:8000/api/v1/company/1/api-keys/5 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Production Key"
  }'
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/api-keys/5', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Updated Production Key'
  })
});

const updated = await response.json();
console.log('Updated:', updated.name);
```

---

## 5. Revoke API Key

Revoke an API key (prevents use but doesn't delete it).

### Endpoint
```
POST /api/v1/company/{company_id}/api-keys/{key_id}/revoke
```

### Headers
```
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `key_id` | integer | Yes |

### Request Body
```json
{}
```

### Response (200 OK)
```json
{
  "id": 5,
  "company_id": 1,
  "name": "Production Key",
  "key_preview": "sk_A...Yz5",
  "status": "revoked",
  "expiry_date": "2027-05-29T14:00:00Z",
  "last_used_at": "2026-05-29T14:30:00Z",
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T15:05:00Z"
}
```

### What Happens After Revoke
- API key cannot be used for requests
- Requests with revoked key return `401 Unauthorized`
- Key can be permanently deleted using DELETE endpoint
- Can create new key as replacement

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys/5/revoke \
  -H "Content-Type: application/json"
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/api-keys/5/revoke', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({})
});

const revoked = await response.json();
console.log('Key revoked:', revoked.status);
```

---

## 6. Delete API Key

Permanently delete an API key.

### Endpoint
```
DELETE /api/v1/company/{company_id}/api-keys/{key_id}
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `key_id` | integer | Yes |

### Response (204 No Content)
```
(empty)
```

### Example cURL
```bash
curl -X DELETE http://localhost:8000/api/v1/company/1/api-keys/5
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/api-keys/5', {
  method: 'DELETE'
});

if (response.status === 204) {
  console.log('API key deleted');
}
```

---

## Key Management Best Practices

### 1. Secure Storage
```javascript
// ❌ DON'T: Hardcode keys
const API_KEY = 'sk_RRFCHcvl3fYk7wX9qM4nZ2pL8jB5dH6tQ1vW0aY9E4';

// ✅ DO: Use environment variables (Node.js)
const API_KEY = process.env.PERAI_API_KEY;

// ✅ DO: Use localStorage (Browser - with caution)
const API_KEY = localStorage.getItem('perai_api_key');

// ✅ DO: Use secure cookie (Browser - most secure)
// Set HttpOnly, Secure, SameSite cookies on server
```

### 2. Key Rotation
```javascript
// 1. Create new key
const newKey = await createApiKey();

// 2. Update application to use new key
localStorage.setItem('perai_api_key', newKey.key);

// 3. Verify new key works
await testNewKey();

// 4. Revoke old key
await revokeApiKey(oldKeyId);

// 5. Delete old key after confirmation
await deleteApiKey(oldKeyId);
```

### 3. Multiple Keys
```javascript
// Create keys for different environments
const prodKey = await createApiKey('Production', '2027-05-29');
const devKey = await createApiKey('Development', null); // No expiry
const stagingKey = await createApiKey('Staging', '2026-12-31');

// Rotate one without affecting others
await revokeApiKey(devKey.id);
const newDevKey = await createApiKey('Development', null);
```

### 4. Monitoring
```javascript
// Check last_used_at to detect unused keys
const keys = await listApiKeys();
keys.forEach(key => {
  const lastUsed = key.last_used_at ? new Date(key.last_used_at) : null;
  console.log(`${key.name}: Last used ${lastUsed || 'never'}`);
});
```

---

## Error Handling

### Invalid Key
```javascript
try {
  const response = await fetch('http://localhost:8000/api/v1/company/1', {
    headers: {
      'X-API-Key': 'sk_invalid_key'
    }
  });
  
  if (response.status === 401) {
    console.error('Invalid API key');
    // Prompt user to provide valid key
  }
} catch (error) {
  console.error('Request failed:', error);
}
```

### Revoked Key
```
Request: X-API-Key: sk_A...Yz5 (revoked)
Response: 401 Unauthorized
Detail: "API key has been revoked"
```

### Expired Key
```
Request: X-API-Key: sk_A...Yz5 (expiry_date < now)
Response: 401 Unauthorized
Detail: "API key has expired"
```

---

## Next Steps

1. **Create API Key** → Use endpoint 1, save key immediately
2. **Use in Requests** → Include `X-API-Key` header in all authenticated calls
3. **Monitor Keys** → Use endpoint 2 to list keys
4. **Rotate as Needed** → Create new key, test, then revoke old one
5. **Access Protected Resources** → See `company.md`, `chat.md`, `files.md`
