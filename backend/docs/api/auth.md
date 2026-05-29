# Authentication Endpoints

Authentication in Perai is company-based. Companies register with email/password and receive a `company_id`. API keys are then created for authenticated requests.

---

## 1. Register Company

Register a new company account.

### Endpoint
```
POST /api/v1/auth/register
```

### Headers
```
Content-Type: application/json
```

### Request Body
```json
{
  "company_name": "TechCorp",
  "company_email": "admin@techcorp.com",
  "password": "secure_password_123",
  "website": "https://techcorp.com",
  "logo": "https://example.com/logo.png"  // optional
}
```

### Request Fields
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `company_name` | string | Yes | Unique company identifier (no spaces allowed) |
| `company_email` | string | Yes | Valid email address |
| `password` | string | Yes | Minimum 6 characters (will be hashed with PBKDF2) |
| `website` | string | No | Company website URL |
| `logo` | string | No | Logo image URL |

### Response (201 Created)
```json
{
  "id": 1,
  "company_name": "TechCorp",
  "company_email": "admin@techcorp.com",
  "logo": "https://example.com/logo.png",
  "website": "https://techcorp.com",
  "company_model_name": null,
  "created_at": "2026-05-29T14:00:00Z",
  "updated_at": "2026-05-29T14:00:00Z"
}
```

### Response Fields
| Field | Type | Notes |
|-------|------|-------|
| `id` | integer | Company ID - use this for all subsequent requests |
| `company_name` | string | Your company name |
| `company_model_name` | string\|null | Auto-generated after first finetune upload as `perai-{company_name_lowercase}` |
| `created_at` | string | ISO 8601 timestamp |
| `updated_at` | string | ISO 8601 timestamp |

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "TechCorp",
    "company_email": "admin@techcorp.com",
    "password": "secure_password_123",
    "website": "https://techcorp.com"
  }'
```

### Example JavaScript (Fetch)
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    company_name: 'TechCorp',
    company_email: 'admin@techcorp.com',
    password: 'secure_password_123',
    website: 'https://techcorp.com'
  })
});

const company = await response.json();
console.log('Company ID:', company.id);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `400` | `"Company with this email already exists"` | Email already registered |
| `400` | `"Company with this name already exists"` | Company name taken |
| `400` | `"Password must be at least 6 characters"` | Password too short |

---

## 2. Login Company

Login with company credentials.

### Endpoint
```
POST /api/v1/auth/login
```

### Headers
```
Content-Type: application/json
```

### Request Body
```json
{
  "email": "admin@techcorp.com",
  "password": "secure_password_123"
}
```

### Request Fields
| Field | Type | Required |
|-------|------|----------|
| `email` | string | Yes |
| `password` | string | Yes |

### Response (200 OK)
```json
{
  "message": "Login successful. Use X-API-Key header for API requests.",
  "company": {
    "id": 1,
    "company_name": "TechCorp",
    "company_email": "admin@techcorp.com",
    "logo": "https://example.com/logo.png",
    "website": "https://techcorp.com",
    "company_model_name": "perai-techcorp",
    "created_at": "2026-05-29T14:00:00Z",
    "updated_at": "2026-05-29T14:00:00Z"
  },
  "api_key_instruction": "Create an API key from /api/v1/apikey/create endpoint"
}
```

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@techcorp.com",
    "password": "secure_password_123"
  }'
```

### Example JavaScript (Fetch)
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'admin@techcorp.com',
    password: 'secure_password_123'
  })
});

const result = await response.json();
if (response.ok) {
  console.log('Logged in as:', result.company.company_name);
  console.log('Company ID:', result.company.id);
  // Next: Create API key from /api/v1/company/{company_id}/api-keys
} else {
  console.error('Login failed:', result.detail);
}
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `400` | `"Invalid email or password"` | Email not found or password incorrect |

---

## 3. Verify Company

Verify company details by ID.

### Endpoint
```
GET /api/v1/auth/verify/{company_id}
```

### Headers
```
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
curl -X GET http://localhost:8000/api/v1/auth/verify/1
```

### Example JavaScript (Fetch)
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/verify/1');
const company = await response.json();
console.log('Company:', company.company_name);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `404` | `"Company not found"` | Invalid company_id |

---

## Authentication Flow Diagram

```
┌─────────────────────────────────────────────────────┐
│ 1. User Opens Frontend                              │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ 2. Registration Form Shown                          │
│    - Company Name                                   │
│    - Email                                          │
│    - Password                                       │
│    - Website (optional)                             │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
        POST /auth/register
        ──────────────────────
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ 3. Receive company_id                               │
│    Save locally or in session                       │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ 4. User Can Login Later OR Create API Key Now       │
└────────────────┬───────────────┬────────────────────┘
                 │               │
        POST /auth/login   POST /company/{id}/api-keys
                 │               │
                 ▼               ▼
         Verify Credentials   Create Key
                 │               │
                 ├───────┬───────┘
                         ▼
           Save API Key in LocalStorage
                         │
                         ▼
       Use X-API-Key Header in All Requests
```

---

## Next Steps

1. **Register Company** → Get `company_id`
2. **Create API Key** → See `apikey.md`
3. **Make Authenticated Requests** → Include `X-API-Key` header
4. **Manage Company Data** → See `company.md`
5. **Upload Finetune Data** → See `company.md`
6. **Chat with AI** → See `chat.md`
