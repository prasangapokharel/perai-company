# Perai Backend API Documentation

## Overview

Complete REST API documentation for Perai backend. This document provides everything needed for frontend integration.

**Base URL**: `http://localhost:8000` (development) / `https://api.perai.com` (production)

**API Version**: v1

**Content-Type**: `application/json`

## Quick Start

### 1. Health Check
```bash
curl http://localhost:8000/
```

### 2. Create Company
```bash
curl -X POST http://localhost:8000/api/v1/company \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Acme Corp",
    "company_email": "contact@acme.com",
    "password": "SecurePass123!"
  }'
```

### 3. Create API Key
```bash
curl -X POST http://localhost:8000/api/v1/company/1/api-keys \
  -H "Content-Type: application/json" \
  -d '{
    "name": "prod_key",
    "expiry_date": "2026-08-22T00:00:00"
  }'
```

### 4. Use API Key in Requests
```bash
curl http://localhost:8000/api/v1/company/1/api-keys \
  -H "X-API-Key: sk_your_full_api_key_here"
```

## Documentation Structure

- **[AUTHENTICATION.md](./AUTHENTICATION.md)** - API key auth, headers, security
- **[COMPANY.md](./COMPANY.md)** - Company management endpoints
- **[FINETUNE.md](./FINETUNE.md)** - Finetune/RAG upload and retrieval
- **[API_KEYS.md](./API_KEYS.md)** - API key lifecycle management
- **[ERROR_CODES.md](./ERROR_CODES.md)** - Error handling and status codes
- **[EXAMPLES.md](./EXAMPLES.md)** - Complete integration examples
- **[POSTMAN.md](./POSTMAN.md)** - Postman collection setup

## API Summary

### Company Management (4 endpoints)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/company` | Create company |
| GET | `/api/v1/company` | List all companies |
| GET | `/api/v1/company/{id}` | Get company details |
| PUT | `/api/v1/company/{id}` | Update company |

### Finetune/RAG (2 endpoints)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/company/{id}/finetune` | Upload finetune data |
| GET | `/api/v1/company/{id}/finetune` | Retrieve finetune data |

### API Keys (6 endpoints)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/company/{id}/api-keys` | Create API key |
| GET | `/api/v1/company/{id}/api-keys` | List API keys |
| GET | `/api/v1/company/{id}/api-keys/{key_id}` | Get API key details |
| PUT | `/api/v1/company/{id}/api-keys/{key_id}` | Update API key |
| POST | `/api/v1/company/{id}/api-keys/{key_id}/revoke` | Revoke API key |
| DELETE | `/api/v1/company/{id}/api-keys/{key_id}` | Delete API key |

### Health (1 endpoint)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Health check |

## Authentication

All endpoints except health check require **X-API-Key** header:

```bash
curl -H "X-API-Key: sk_your_full_api_key_here" \
  http://localhost:8000/api/v1/company
```

See [AUTHENTICATION.md](./AUTHENTICATION.md) for details.

## Response Format

### Success Response (2xx)
```json
{
  "id": 1,
  "company_name": "Acme Corp",
  "company_email": "contact@acme.com",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:40:00Z"
}
```

### Error Response (4xx/5xx)
```json
{
  "detail": "Error message describing what went wrong"
}
```

See [ERROR_CODES.md](./ERROR_CODES.md) for all status codes and error types.

## Tools & Resources

- **Swagger UI**: http://localhost:8000/docs
- **ReDoc**: http://localhost:8000/redoc
- **Postman Collection**: See [POSTMAN.md](./POSTMAN.md)

## Frontend Integration Guide

### JavaScript/TypeScript Example
```typescript
const API_BASE = 'http://localhost:8000';
const API_KEY = 'sk_your_full_api_key_here';

// Helper function
async function apiCall(endpoint: string, options: RequestInit = {}) {
  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': API_KEY,
      ...options.headers,
    },
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.detail);
  }
  
  return response.json();
}

// Usage
const company = await apiCall('/api/v1/company/1');
```

See [EXAMPLES.md](./EXAMPLES.md) for more patterns.

## Rate Limiting

Currently: No rate limiting (will be added in Phase 2)

Future: 100 requests/minute per API key

## Pagination

Currently: All list endpoints return full results

Future: Will support limit/offset parameters

## Support

For issues or questions:
- Check [EXAMPLES.md](./EXAMPLES.md) for integration patterns
- Review [ERROR_CODES.md](./ERROR_CODES.md) for error handling
- Check Swagger UI: http://localhost:8000/docs
- See [POSTMAN.md](./POSTMAN.md) for testing

---

**Last Updated**: 2026-05-22
**API Version**: v1
**Status**: Production Ready
