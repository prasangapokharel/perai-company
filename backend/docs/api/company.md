# Company Endpoints

Manage company details, finetune data, and related operations. All endpoints require authentication via `X-API-Key` header (except those noted).

---

## 1. Get All Companies

Retrieve list of all companies (useful for admin dashboards).

### Endpoint
```
GET /api/v1/company
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Response (200 OK)
```json
[
  {
    "id": 1,
    "company_name": "TechCorp",
    "company_email": "admin@techcorp.com",
    "logo": "https://example.com/logo.png",
    "website": "https://techcorp.com",
    "company_model_name": "perai-techcorp",
    "created_at": "2026-05-29T14:00:00Z",
    "updated_at": "2026-05-29T14:00:00Z"
  },
  {
    "id": 2,
    "company_name": "DataSoft",
    "company_email": "admin@datasoft.com",
    "logo": null,
    "website": "https://datasoft.com",
    "company_model_name": null,
    "created_at": "2026-05-29T14:30:00Z",
    "updated_at": "2026-05-29T14:30:00Z"
  }
]
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company', {
  method: 'GET',
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY'
  }
});

const companies = await response.json();
console.log('Total companies:', companies.length);
```

---

## 2. Get Company Details

Retrieve details for a specific company.

### Endpoint
```
GET /api/v1/company/{company_id}
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Response (200 OK)
```json
{
  "id": 1,
  "company_name": "TechCorp",
  "company_email": "admin@techcorp.com",
  "logo": "https://example.com/logo.png",
  "website": "https://techcorp.com",
  "company_model_name": "perai-techcorp",
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T14:00:00Z"
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
const companyId = 1;
const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}`, {
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY'
  }
});

const company = await response.json();
console.log('Company:', company.company_name);
console.log('Model Name:', company.company_model_name);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## 3. Create Company

Create a new company (alternative to registration).

### Endpoint
```
POST /api/v1/company
```

### Headers
```
Content-Type: application/json
```

### Request Body
```json
{
  "company_name": "NewTech",
  "company_email": "admin@newtech.com",
  "password": "secure_password_123",
  "website": "https://newtech.com",
  "logo": "https://example.com/logo.png"
}
```

### Response (201 Created)
```json
{
  "id": 3,
  "company_name": "NewTech",
  "company_email": "admin@newtech.com",
  "logo": "https://example.com/logo.png",
  "website": "https://newtech.com",
  "company_model_name": null,
  "created_at": "2026-05-29T15:00:00Z",
  "updated_at": "2026-05-29T15:00:00Z"
}
```

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "NewTech",
    "company_email": "admin@newtech.com",
    "password": "secure_password_123",
    "website": "https://newtech.com"
  }'
```

---

## 4. Update Company

Update company details.

### Endpoint
```
PUT /api/v1/company/{company_id}
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Request Body (all fields optional)
```json
{
  "company_name": "TechCorp Updated",
  "website": "https://newtechcorp.com",
  "logo": "https://example.com/new-logo.png"
}
```

### Response (200 OK)
```json
{
  "id": 1,
  "company_name": "TechCorp Updated",
  "company_email": "admin@techcorp.com",
  "logo": "https://example.com/new-logo.png",
  "website": "https://newtechcorp.com",
  "company_model_name": "perai-techcorp",
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T15:30:00Z"
}
```

### Example cURL
```bash
curl -X PUT http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "website": "https://newtechcorp.com"
  }'
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1', {
  method: 'PUT',
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    website: 'https://newtechcorp.com'
  })
});

const updated = await response.json();
console.log('Updated company:', updated.company_name);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## 5. Delete Company

Delete a company and all associated data (API keys, finetune data, files).

### Endpoint
```
DELETE /api/v1/company/{company_id}
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Response (204 No Content)
```
(empty)
```

### What Gets Deleted
- Company record
- All API keys
- Finetune data and files
- Stored logos and content
- All chat history

### Example cURL
```bash
curl -X DELETE http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1', {
  method: 'DELETE',
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY'
  }
});

if (response.status === 204) {
  console.log('Company deleted successfully');
}
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## Finetune Data Management

### 6. Upload Finetune Data

Upload knowledge base / RAG data for the company. This content will be used as context for AI chat queries.

### Endpoint
```
POST /api/v1/company/{company_id}/finetune
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Request Body
```json
{
  "content": "# Company Knowledge Base\n\n## About Us\nWe are TechCorp...\n\n## Services\n1. Cloud solutions\n2. AI consulting"
}
```

### Request Fields
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `content` | string | Yes | Markdown content (knowledge base) |

### Response (201 Created)
```json
{
  "id": 1,
  "company_id": 1,
  "company_model_name": "perai-techcorp",
  "rag_company_path": "/home/prasanga/perai-company/backend/app/core/finetune/rag/companies/1/company.md",
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T14:00:00Z"
}
```

### Response Fields
| Field | Type | Notes |
|-------|------|-------|
| `company_model_name` | string | Auto-generated as `perai-{company_name_lowercase}` |
| `rag_company_path` | string | Where content is stored on disk |

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company/1/finetune \
  -H "X-API-Key: sk_YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "# TechCorp Knowledge Base\n\n## About Us\nTechCorp specializes in cloud solutions and AI consulting.\n\n## Services\n1. Cloud Infrastructure\n2. AI Solutions\n3. Consulting"
  }'
```

### Example JavaScript
```javascript
const knowledge = `# TechCorp Knowledge Base

## About Us
TechCorp specializes in cloud solutions and AI consulting.

## Services
1. Cloud Infrastructure - Scalable hosting solutions
2. AI Solutions - Machine learning and NLP
3. Consulting - Architecture and optimization

## Pricing
- Starter: $499/month
- Professional: $1,999/month
- Enterprise: Custom
`;

const response = await fetch('http://localhost:8000/api/v1/company/1/finetune', {
  method: 'POST',
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ content: knowledge })
});

const result = await response.json();
console.log('Model Name:', result.company_model_name);
console.log('Stored at:', result.rag_company_path);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

### 7. Get Finetune Data

Retrieve company's finetune/RAG data.

### Endpoint
```
GET /api/v1/company/{company_id}/finetune
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Response (200 OK)
```json
{
  "id": 1,
  "company_id": 1,
  "company_model_name": "perai-techcorp",
  "rag_company_path": "/home/prasanga/perai-company/backend/app/core/finetune/rag/companies/1/company.md",
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T14:00:00Z"
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/finetune \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/finetune', {
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY'
  }
});

const finetune = await response.json();
console.log('Model Name:', finetune.company_model_name);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |
| `404` | `"No finetune data found"` | Company has no finetune data yet |

---

### 8. Delete Finetune Data

Delete company's knowledge base / RAG data.

### Endpoint
```
DELETE /api/v1/company/{company_id}/finetune
```

### Headers
```
X-API-Key: sk_YOUR_API_KEY
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Response (204 No Content)
```
(empty)
```

### Example cURL
```bash
curl -X DELETE http://localhost:8000/api/v1/company/1/finetune \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/finetune', {
  method: 'DELETE',
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY'
  }
});

if (response.status === 204) {
  console.log('Finetune data deleted');
}
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## Company Data Flow

```
┌─────────────────────────────────────┐
│ Frontend                            │
│ - Company Settings Page             │
│ - Upload Knowledge Base             │
└────────────┬────────────────────────┘
             │
             ▼
    POST /company/{id}/finetune
    ──────────────────────────
             │
             ▼
┌─────────────────────────────────────┐
│ Backend                             │
│ - Validate company exists           │
│ - Generate model_name               │
│ - Store content on disk             │
└────────────┬────────────────────────┘
             │
             ▼
    Response: company_model_name
             │
             ├─ Used in chat queries ──────┐
             │                             ▼
             │                  POST /chat/query
             │                  with model context
             │
             └─ Retrieved by ──────────────┐
                                           ▼
                                 GET /company/{id}/finetune
```

---

## Next Steps

1. **Create/Update Company** → Use endpoints 1-5
2. **Upload Knowledge Base** → Use endpoint 6
3. **Chat with AI** → See `chat.md` (uses finetune data)
4. **Manage API Keys** → See `apikey.md`
5. **Upload Files** → See `files.md`
