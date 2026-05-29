# Perai Backend API Documentation

## Overview

The Perai Backend provides a complete REST API for company management, authentication, API key lifecycle, finetune/RAG data management, and AI chat queries via Groq.

**System Architecture:**
- Company-based authentication (no separate user entities)
- All data scoped to `company_id`
- API key-based request authentication
- Finetune data stored on disk (`backend/storage/companies/{company_id}/`)
- AI chat powered by Groq with company-specific context

---

## Base Configuration

### Base URL
```
http://localhost:8000  (development)
https://api.perai.com   (production - replace with your domain)
```

### API Version
```
v1
```

### Headers
All authenticated requests require:
```
X-API-Key: sk_YOUR_API_KEY_HERE
Content-Type: application/json
```

---

## Authentication Flow

### 1. Company Registration
- Endpoint: `POST /api/v1/auth/register`
- Body: email, password, company_name, website, logo
- Response: Company object with `id`

### 2. Company Login
- Endpoint: `POST /api/v1/auth/login`
- Body: email, password
- Response: Company object + instruction to create API key

### 3. Create API Key
- Endpoint: `POST /api/v1/company/{company_id}/api-keys`
- Body: name, expiry_date (optional)
- Response: Full API key (`sk_...`) - shown only once at creation
- Use returned key in `X-API-Key` header for all subsequent requests

### 4. Subsequent Requests
- Include `X-API-Key: sk_YOUR_KEY` in all authenticated requests
- Key validation happens automatically
- `last_used_at` timestamp updates on each valid request

---

## HTTP Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| `200` | OK | Request succeeded; check response body |
| `201` | Created | Resource created; check response for new ID |
| `204` | No Content | Success; no response body (DELETE operations) |
| `400` | Bad Request | Invalid input; check error message |
| `401` | Unauthorized | Missing or invalid API key |
| `403` | Forbidden | Permission denied |
| `404` | Not Found | Resource not found |
| `500` | Server Error | Internal server error; contact support |

---

## Response Format

### Success Response
```json
{
  "id": 1,
  "company_name": "TechCorp",
  "company_email": "admin@techcorp.com",
  ...
}
```

### Error Response
```json
{
  "detail": "Company not found"
}
```

---

## Common Patterns

### Creating a Resource
```bash
POST /api/v1/company/1/api-keys
X-API-Key: sk_YOUR_KEY
Content-Type: application/json

{
  "name": "Production API Key"
}
```

Response (201 Created):
```json
{
  "id": 5,
  "company_id": 1,
  "name": "Production API Key",
  "key_preview": "sk_A...Yz5",
  "status": "active",
  "created_at": "2026-05-29T14:00:00Z"
}
```

### Retrieving a Resource
```bash
GET /api/v1/company/1
X-API-Key: sk_YOUR_KEY
```

Response (200 OK):
```json
{
  "id": 1,
  "company_name": "TechCorp",
  "company_email": "admin@techcorp.com",
  "company_model_name": "perai-techcorp",
  "created_at": "2026-05-29T12:00:00Z",
  "updated_at": "2026-05-29T12:00:00Z"
}
```

### Updating a Resource
```bash
PUT /api/v1/company/1
X-API-Key: sk_YOUR_KEY
Content-Type: application/json

{
  "website": "https://newtechcorp.com"
}
```

Response (200 OK):
```json
{
  "id": 1,
  "company_name": "TechCorp",
  "website": "https://newtechcorp.com",
  "updated_at": "2026-05-29T14:30:00Z",
  ...
}
```

### Deleting a Resource
```bash
DELETE /api/v1/company/1
X-API-Key: sk_YOUR_KEY
```

Response (204 No Content):
```
(empty)
```

---

## Rate Limiting

Currently no rate limiting. Production deployment will include:
- Per-API-key rate limits
- Request throttling
- Usage analytics

---

## Error Handling

All errors include a `detail` field with description:

```json
{
  "detail": "Invalid API key"
}
```

Common errors:
- `"Company not found"` → Use valid company_id
- `"Invalid API key"` → Check X-API-Key header
- `"API key has been revoked"` → Create new API key
- `"API key has expired"` → Create new API key with future date

---

## Timestamps

All timestamps are ISO 8601 format with UTC timezone:
```
2026-05-29T14:30:00Z
```

---

## API Documentation Files

| File | Contents |
|------|----------|
| `auth.md` | Company registration, login, verification |
| `company.md` | Company CRUD, finetune data management |
| `apikey.md` | API key creation, list, update, revoke, delete |
| `chat.md` | Chat queries, ping, model context |
| `files.md` | Logo upload, content file management |

---

## Quick Start

### 1. Register Company
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "MyCompany",
    "company_email": "admin@mycompany.com",
    "password": "secure_password",
    "website": "https://mycompany.com"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@mycompany.com",
    "password": "secure_password"
  }'
```

### 3. Create API Key
```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys \
  -H "Content-Type: application/json" \
  -d '{"name": "My API Key"}'
```

### 4. Use API Key
```bash
curl -X GET http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_YOUR_FULL_KEY_HERE"
```

---

## Next Steps

- Read `auth.md` to implement authentication
- Read `company.md` for company management
- Read `apikey.md` for API key management
- Read `chat.md` to integrate AI chat
- Read `files.md` for file uploads (logos, content)

---

## Support

For issues or questions:
1. Check the error message in the response
2. Verify your API key is valid and not revoked
3. Ensure all required fields are provided
4. Check the endpoint path matches your resource IDs
