# Authentication Guide

## Overview

Perai uses **API Key Authentication** via the `X-API-Key` header.

All protected endpoints require a valid API key. The health check endpoint (`GET /`) is public.

## API Key Format

```
sk_{random_token}
```

- **Prefix**: `sk_` (identifies as secret key)
- **Token**: 44 random URL-safe characters (base64url encoded)
- **Total Length**: 48 characters

**Example**: `sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8`

## Getting an API Key

### Step 1: Create a Company
```bash
curl -X POST http://localhost:8000/api/v1/company \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "My Company",
    "company_email": "contact@mycompany.com",
    "password": "SecurePassword123!"
  }'
```

**Response**:
```json
{
  "id": 1,
  "company_name": "My Company",
  "company_email": "contact@mycompany.com",
  "password": "SecurePassword123!",
  "logo": null,
  "website": null,
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:40:00Z"
}
```

Save the company `id` (e.g., `1`).

### Step 2: Create an API Key

```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys \
  -H "Content-Type: application/json" \
  -d '{
    "name": "production_key",
    "expiry_date": "2026-08-22T00:00:00"
  }'
```

**Response**:
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

**IMPORTANT**: Save the full `key` value. It will never be shown again after this response.

### Step 3: Use the API Key

Add the `X-API-Key` header to all requests:

```bash
curl http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

## Using API Keys in Frontend

### JavaScript/Fetch
```javascript
const API_KEY = 'sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8';

fetch('http://localhost:8000/api/v1/company/1', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': API_KEY,
  },
})
  .then(res => res.json())
  .then(data => console.log(data));
```

### React Example
```jsx
import { useState, useEffect } from 'react';

function CompanyDetails() {
  const [company, setCompany] = useState(null);
  const API_KEY = process.env.REACT_APP_API_KEY;

  useEffect(() => {
    fetch('http://localhost:8000/api/v1/company/1', {
      headers: {
        'X-API-Key': API_KEY,
      },
    })
      .then(res => res.json())
      .then(data => setCompany(data));
  }, []);

  return <div>{company?.company_name}</div>;
}

export default CompanyDetails;
```

### TypeScript/Axios
```typescript
import axios from 'axios';

const API_KEY = process.env.REACT_APP_API_KEY;
const api = axios.create({
  baseURL: 'http://localhost:8000',
  headers: {
    'X-API-Key': API_KEY,
  },
});

// Now all requests automatically include the API key
api.get('/api/v1/company/1').then(res => console.log(res.data));
```

## API Key Management

### List API Keys
```bash
curl http://localhost:8000/api/v1/company/1/api-keys \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

**Response**:
```json
[
  {
    "id": 1,
    "company_id": 1,
    "name": "production_key",
    "key_preview": "sk_a...rUG8",
    "status": "active",
    "expiry_date": "2026-08-22T00:00:00Z",
    "last_used_at": null,
    "created_at": "2026-05-22T17:40:00Z",
    "updated_at": "2026-05-22T17:40:00Z"
  }
]
```

### Revoke an API Key
```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys/1/revoke \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

**Response**:
```json
{
  "id": 1,
  "company_id": 1,
  "name": "production_key",
  "key_preview": "sk_a...rUG8",
  "status": "revoked",
  "expiry_date": "2026-08-22T00:00:00Z",
  "last_used_at": "2026-05-22T17:45:00Z",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:45:00Z"
}
```

## API Key Status

| Status | Description |
|--------|-------------|
| `active` | Key is valid and can be used |
| `revoked` | Key has been revoked and cannot be used |
| `expired` | Key expiry date has passed |

## Security Best Practices

### ✓ DO
- Store API keys in environment variables (`.env`, secrets manager)
- Use HTTPS in production (not HTTP)
- Rotate keys periodically (e.g., quarterly)
- Use different keys for different environments (dev/staging/prod)
- Revoke keys when they're no longer needed
- Keep expiry dates reasonable (90 days recommended)

### ✗ DON'T
- Hardcode API keys in source code
- Share API keys in emails or Slack
- Commit API keys to version control
- Use the same key across multiple environments
- Store API keys in local storage (browser)
- Use API keys in frontend JavaScript (use backend proxy)

## Error Responses

### Invalid API Key
```bash
curl http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_invalid_key"
```

**Response** (401 Unauthorized):
```json
{
  "detail": "Invalid API key"
}
```

### Missing API Key
```bash
curl http://localhost:8000/api/v1/company/1
```

**Response** (403 Forbidden):
```json
{
  "detail": "X-API-Key header is required"
}
```

### Expired API Key
```bash
curl http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_expired_key"
```

**Response** (401 Unauthorized):
```json
{
  "detail": "API key has expired"
}
```

### Revoked API Key
```bash
curl http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_revoked_key"
```

**Response** (401 Unauthorized):
```json
{
  "detail": "API key has been revoked"
}
```

## Production Setup

### Environment Variables

**Frontend (.env.local)**:
```
REACT_APP_API_BASE=https://api.perai.com
REACT_APP_API_KEY=sk_production_key_here
```

**Backend (.env)**:
```
DB_URL=postgresql://user:pass@db.provider.com/perai
GROQ_API_KEY=your_groq_key
API_KEY_EXPIRY_DAYS=90
CORS_ORIGINS=https://frontend.perai.com,https://app.perai.com
```

### CORS Configuration

Frontend and backend should be on different domains in production:

- Frontend: `https://app.perai.com`
- Backend API: `https://api.perai.com`

CORS is pre-configured to allow cross-domain requests.

## Troubleshooting

### "X-API-Key header is required"
- Add the `X-API-Key` header to your request
- Check that the header name is exactly `X-API-Key` (case-sensitive)

### "Invalid API key"
- Verify the API key is correct (copy from secure storage)
- Check that the key hasn't been revoked
- Verify the key hasn't expired

### "API key has been revoked"
- Create a new API key
- Update your frontend configuration with the new key

### "API key has expired"
- Create a new API key
- Update your frontend configuration with the new key

## Next Steps

1. Read [COMPANY.md](./COMPANY.md) to learn company management
2. Read [API_KEYS.md](./API_KEYS.md) for API key operations
3. Check [EXAMPLES.md](./EXAMPLES.md) for complete integration examples
4. Review [POSTMAN.md](./POSTMAN.md) for testing with Postman

---

**Last Updated**: 2026-05-22
**Version**: 1.0
