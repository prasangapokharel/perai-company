# API Keys Management

## Overview

Complete API key lifecycle management including create, list, update, revoke, and delete operations.

## Endpoints

### 1. Create API Key

Generate a new API key for a company.

**Endpoint**: `POST /api/v1/company/{company_id}/api-keys`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |

**Request Body**:
```json
{
  "name": "production_key",
  "expiry_date": "2026-08-22T00:00:00"
}
```

**Parameters**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Unique key name (max 100 chars) |
| `expiry_date` | string (ISO 8601) | Yes | Key expiry date/time |

**Success Response** (201 Created):
```json
{
  "id": 1,
  "company_id": 1,
  "name": "production_key",
  "key": "sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8",
  "key_preview": "sk_a...rUG8",
  "status": "active",
  "expiry_date": "2026-08-22T00:00:00Z",
  "created_at": "2026-05-22T17:40:00Z"
}
```

**Important**: The full `key` is only shown in this response. Save it securely immediately.

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Company doesn't exist |
| 400 | Name exists | Key with this name already exists |
| 422 | Validation error | Invalid expiry date or name |

**Example**:
```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_existing_key" \
  -d '{
    "name": "prod_key_v1",
    "expiry_date": "2026-08-22T00:00:00"
  }'
```

---

### 2. List API Keys

Retrieve all API keys for a company.

**Endpoint**: `GET /api/v1/company/{company_id}/api-keys`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |

**Success Response** (200 OK):
```json
[
  {
    "id": 1,
    "company_id": 1,
    "name": "production_key",
    "key_preview": "sk_a...rUG8",
    "status": "active",
    "expiry_date": "2026-08-22T00:00:00Z",
    "last_used_at": "2026-05-22T18:00:00Z",
    "created_at": "2026-05-22T17:40:00Z",
    "updated_at": "2026-05-22T17:40:00Z"
  },
  {
    "id": 2,
    "company_id": 1,
    "name": "staging_key",
    "key_preview": "sk_b...nKxE",
    "status": "active",
    "expiry_date": "2026-06-22T00:00:00Z",
    "last_used_at": null,
    "created_at": "2026-05-22T17:45:00Z",
    "updated_at": "2026-05-22T17:45:00Z"
  }
]
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Company doesn't exist |

**Example**:
```bash
curl http://localhost:8000/api/v1/company/1/api-keys \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

---

### 3. Get API Key Details

Retrieve a specific API key.

**Endpoint**: `GET /api/v1/company/{company_id}/api-keys/{key_id}`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |
| `key_id` | integer | Yes | API Key ID |

**Success Response** (200 OK):
```json
{
  "id": 1,
  "company_id": 1,
  "name": "production_key",
  "key_preview": "sk_a...rUG8",
  "status": "active",
  "expiry_date": "2026-08-22T00:00:00Z",
  "last_used_at": "2026-05-22T18:00:00Z",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:40:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Key doesn't exist |

**Example**:
```bash
curl http://localhost:8000/api/v1/company/1/api-keys/1 \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

---

### 4. Update API Key

Update API key details (name and expiry date).

**Endpoint**: `PUT /api/v1/company/{company_id}/api-keys/{key_id}`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |
| `key_id` | integer | Yes | API Key ID |

**Request Body** (at least one field required):
```json
{
  "name": "production_key_v2",
  "expiry_date": "2026-09-22T00:00:00"
}
```

**Success Response** (200 OK):
```json
{
  "id": 1,
  "company_id": 1,
  "name": "production_key_v2",
  "key_preview": "sk_a...rUG8",
  "status": "active",
  "expiry_date": "2026-09-22T00:00:00Z",
  "last_used_at": "2026-05-22T18:00:00Z",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:50:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Key doesn't exist |
| 400 | Name exists | New name already taken |
| 422 | Validation error | Invalid data |

**Example**:
```bash
curl -X PUT http://localhost:8000/api/v1/company/1/api-keys/1 \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8" \
  -d '{
    "name": "production_key_v2",
    "expiry_date": "2026-09-22T00:00:00"
  }'
```

---

### 5. Revoke API Key

Revoke (disable) an API key. Once revoked, it cannot be used.

**Endpoint**: `POST /api/v1/company/{company_id}/api-keys/{key_id}/revoke`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |
| `key_id` | integer | Yes | API Key ID |

**Request Body**: Empty

**Success Response** (200 OK):
```json
{
  "id": 1,
  "company_id": 1,
  "name": "production_key",
  "key_preview": "sk_a...rUG8",
  "status": "revoked",
  "expiry_date": "2026-08-22T00:00:00Z",
  "last_used_at": "2026-05-22T18:00:00Z",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:52:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Key doesn't exist |
| 400 | Already revoked | Key is already revoked |

**Example**:
```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys/1/revoke \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

---

### 6. Delete API Key

Permanently delete an API key.

**Endpoint**: `DELETE /api/v1/company/{company_id}/api-keys/{key_id}`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |
| `key_id` | integer | Yes | API Key ID |

**Request Body**: Empty

**Success Response** (200 OK):
```json
{
  "message": "API key deleted successfully"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Key doesn't exist |

**Example**:
```bash
curl -X DELETE http://localhost:8000/api/v1/company/1/api-keys/1 \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

---

## API Key Statuses

| Status | Description | Usable |
|--------|-------------|--------|
| `active` | Key is valid and can be used | ✓ Yes |
| `revoked` | Key has been manually revoked | ✗ No |
| `expired` | Key expiry date has passed | ✗ No |

---

## Key Properties

### id
- Type: `integer`
- Unique identifier for the API key
- Used in endpoint URLs

### company_id
- Type: `integer`
- References the company this key belongs to

### name
- Type: `string` (1-100 characters)
- Human-readable key name
- Unique per company
- Examples: "production", "staging", "dev-machine"

### key
- Type: `string` (48 characters)
- Full API key (only shown at creation)
- Format: `sk_{44_random_chars}`
- Use in X-API-Key header

### key_preview
- Type: `string` (max 20 characters)
- Partial key for identification
- Format: `sk_4...4chars`
- Example: `sk_a...rUG8`

### status
- Type: `string` (active | revoked | expired)
- Current state of the key
- Updated on revoke or auto-expiry

### expiry_date
- Type: `string` (ISO 8601 datetime)
- When the key expires
- Format: `YYYY-MM-DDTHH:MM:SSZ`
- Example: `2026-08-22T00:00:00Z`

### last_used_at
- Type: `string | null` (ISO 8601 datetime)
- Timestamp of last successful use
- Null if never used
- Updated automatically on each request

### created_at
- Type: `string` (ISO 8601 datetime)
- When the key was created
- Read-only

### updated_at
- Type: `string` (ISO 8601 datetime)
- Last time any property changed
- Read-only

---

## Integration Examples

### JavaScript/React
```javascript
import { useState, useEffect } from 'react';

function APIKeyManager({ companyId, apiKey }) {
  const [keys, setKeys] = useState([]);
  const [newKeyName, setNewKeyName] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadKeys();
  }, []);

  const loadKeys = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/api-keys`,
        {
          headers: { 'X-API-Key': apiKey },
        }
      );
      const data = await response.json();
      setKeys(data);
    } catch (err) {
      console.error('Error loading keys:', err);
    }
    setLoading(false);
  };

  const handleCreateKey = async () => {
    if (!newKeyName) {
      alert('Please enter a key name');
      return;
    }

    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + 90); // 90 days

    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/api-keys`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-API-Key': apiKey,
          },
          body: JSON.stringify({
            name: newKeyName,
            expiry_date: expiryDate.toISOString(),
          }),
        }
      );

      const data = await response.json();
      alert(`Key created! Full key: ${data.key}`);
      setNewKeyName('');
      loadKeys();
    } catch (err) {
      alert(`Error: ${err.message}`);
    }
  };

  const handleRevokeKey = async (keyId) => {
    if (!window.confirm('Revoke this key? It cannot be undone.')) return;

    try {
      await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/api-keys/${keyId}/revoke`,
        {
          method: 'POST',
          headers: { 'X-API-Key': apiKey },
        }
      );
      loadKeys();
    } catch (err) {
      alert(`Error: ${err.message}`);
    }
  };

  return (
    <div>
      <h2>API Keys</h2>
      <div>
        <input
          type="text"
          placeholder="Key name"
          value={newKeyName}
          onChange={(e) => setNewKeyName(e.target.value)}
        />
        <button onClick={handleCreateKey}>Create Key</button>
      </div>

      {loading ? (
        <p>Loading...</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Preview</th>
              <th>Status</th>
              <th>Expires</th>
              <th>Last Used</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {keys.map((key) => (
              <tr key={key.id}>
                <td>{key.name}</td>
                <td>{key.key_preview}</td>
                <td>{key.status}</td>
                <td>{new Date(key.expiry_date).toLocaleDateString()}</td>
                <td>{key.last_used_at ? new Date(key.last_used_at).toLocaleString() : 'Never'}</td>
                <td>
                  <button onClick={() => handleRevokeKey(key.id)}>
                    Revoke
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

export default APIKeyManager;
```

### TypeScript/Axios
```typescript
import axios from 'axios';

interface APIKey {
  id: number;
  company_id: number;
  name: string;
  key_preview: string;
  status: 'active' | 'revoked' | 'expired';
  expiry_date: string;
  last_used_at: string | null;
  created_at: string;
  updated_at: string;
}

interface APIKeyCreateResponse extends APIKey {
  key: string; // Only in create response
}

const api = axios.create({
  baseURL: 'http://localhost:8000',
  headers: {
    'X-API-Key': process.env.REACT_APP_API_KEY,
  },
});

// Create API key
export async function createAPIKey(
  companyId: number,
  name: string,
  expiryDate: string
): Promise<APIKeyCreateResponse> {
  const response = await api.post(`/api/v1/company/${companyId}/api-keys`, {
    name,
    expiry_date: expiryDate,
  });
  return response.data;
}

// List API keys
export async function listAPIKeys(companyId: number): Promise<APIKey[]> {
  const response = await api.get(`/api/v1/company/${companyId}/api-keys`);
  return response.data;
}

// Get API key
export async function getAPIKey(
  companyId: number,
  keyId: number
): Promise<APIKey> {
  const response = await api.get(
    `/api/v1/company/${companyId}/api-keys/${keyId}`
  );
  return response.data;
}

// Update API key
export async function updateAPIKey(
  companyId: number,
  keyId: number,
  name?: string,
  expiryDate?: string
): Promise<APIKey> {
  const response = await api.put(
    `/api/v1/company/${companyId}/api-keys/${keyId}`,
    {
      ...(name && { name }),
      ...(expiryDate && { expiry_date: expiryDate }),
    }
  );
  return response.data;
}

// Revoke API key
export async function revokeAPIKey(
  companyId: number,
  keyId: number
): Promise<APIKey> {
  const response = await api.post(
    `/api/v1/company/${companyId}/api-keys/${keyId}/revoke`
  );
  return response.data;
}

// Delete API key
export async function deleteAPIKey(
  companyId: number,
  keyId: number
): Promise<{ message: string }> {
  const response = await api.delete(
    `/api/v1/company/${companyId}/api-keys/${keyId}`
  );
  return response.data;
}
```

---

## Common Patterns

### Set Key Expiry to 90 Days
```javascript
const expiryDate = new Date();
expiryDate.setDate(expiryDate.getDate() + 90);
const isoDate = expiryDate.toISOString();
// "2026-08-22T17:40:00.000Z"
```

### Check if Key is Active
```javascript
const isActive = key.status === 'active' 
  && new Date(key.expiry_date) > new Date();
```

### Display Readable Status
```javascript
function getStatusBadge(key) {
  if (key.status === 'revoked') return '❌ Revoked';
  if (new Date(key.expiry_date) < new Date()) return '⏰ Expired';
  if (key.status === 'active') return '✅ Active';
  return '❓ Unknown';
}
```

---

## Troubleshooting

### "Name already exists"
- Key names must be unique per company
- Choose a different name or check existing keys

### "API key has expired"
- Create a new key with a future expiry date
- Update frontend with the new key

### "Cannot find key"
- Verify company_id and key_id are correct
- Check key wasn't deleted

---

**Last Updated**: 2026-05-22
**Version**: 1.0
