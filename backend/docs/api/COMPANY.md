# Company Management API

## Overview

Manage company profiles and metadata. All endpoints require API key authentication.

## Endpoints

### 1. Create Company

Creates a new company.

**Endpoint**: `POST /api/v1/company`

**Authentication**: Not required (first company setup)

**Request Body**:
```json
{
  "company_name": "Acme Corporation",
  "company_email": "contact@acme.com",
  "password": "SecurePassword123!",
  "logo": "https://acme.com/logo.png",
  "website": "https://acme.com"
}
```

**Parameters**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `company_name` | string | Yes | Company name (max 255 chars) |
| `company_email` | string | Yes | Unique company email |
| `password` | string | Yes | Password for authentication |
| `logo` | string | No | URL to company logo |
| `website` | string | No | Company website URL |

**Success Response** (201 Created):
```json
{
  "id": 1,
  "company_name": "Acme Corporation",
  "company_email": "contact@acme.com",
  "password": "SecurePassword123!",
  "logo": "https://acme.com/logo.png",
  "website": "https://acme.com",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:40:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 400 | Email already exists | Company with this email already registered |
| 422 | Validation error | Invalid input data |

**Example**:
```bash
curl -X POST http://localhost:8000/api/v1/company \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Acme Corp",
    "company_email": "contact@acme.com",
    "password": "SecurePass123!",
    "logo": "https://acme.com/logo.png",
    "website": "https://acme.com"
  }'
```

---

### 2. List All Companies

Retrieves all companies.

**Endpoint**: `GET /api/v1/company`

**Authentication**: Required (X-API-Key header)

**Parameters**: None

**Success Response** (200 OK):
```json
[
  {
    "id": 1,
    "company_name": "Acme Corporation",
    "company_email": "contact@acme.com",
    "password": "SecurePassword123!",
    "logo": "https://acme.com/logo.png",
    "website": "https://acme.com",
    "created_at": "2026-05-22T17:40:00Z",
    "updated_at": "2026-05-22T17:40:00Z"
  },
  {
    "id": 2,
    "company_name": "TechCorp",
    "company_email": "hello@techcorp.com",
    "password": "SecurePass456!",
    "logo": null,
    "website": "https://techcorp.com",
    "created_at": "2026-05-22T18:00:00Z",
    "updated_at": "2026-05-22T18:00:00Z"
  }
]
```

**Example**:
```bash
curl http://localhost:8000/api/v1/company \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

---

### 3. Get Company Details

Retrieves a specific company by ID.

**Endpoint**: `GET /api/v1/company/{id}`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Company ID |

**Success Response** (200 OK):
```json
{
  "id": 1,
  "company_name": "Acme Corporation",
  "company_email": "contact@acme.com",
  "password": "SecurePassword123!",
  "logo": "https://acme.com/logo.png",
  "website": "https://acme.com",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:40:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Company with this ID doesn't exist |

**Example**:
```bash
curl http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

---

### 4. Update Company

Updates company details.

**Endpoint**: `PUT /api/v1/company/{id}`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Company ID |

**Request Body** (all fields optional):
```json
{
  "company_name": "Acme Corp Updated",
  "company_email": "newemail@acme.com",
  "logo": "https://acme.com/new-logo.png",
  "website": "https://acme.co"
}
```

**Success Response** (200 OK):
```json
{
  "id": 1,
  "company_name": "Acme Corp Updated",
  "company_email": "newemail@acme.com",
  "password": "SecurePassword123!",
  "logo": "https://acme.com/new-logo.png",
  "website": "https://acme.co",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:42:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Company doesn't exist |
| 400 | Email already exists | Email already taken by another company |
| 422 | Validation error | Invalid input data |

**Example**:
```bash
curl -X PUT http://localhost:8000/api/v1/company/1 \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8" \
  -d '{
    "company_name": "Acme Corp Updated",
    "website": "https://acme.co"
  }'
```

---

## Field Descriptions

### company_name
- Type: `string` (1-255 characters)
- The display name of the company
- Used in UI and communications
- Example: "Acme Corporation", "TechStartup Inc"

### company_email
- Type: `string` (valid email format)
- Unique identifier for the company
- Used for login and communications
- Example: "contact@acme.com"

### password
- Type: `string` (8+ characters)
- Initial authentication password
- Encrypted in database
- Used for future authentication

### logo
- Type: `string` (URL) | `null`
- Optional company logo URL
- Displayed in frontend UI
- Should be HTTPS in production

### website
- Type: `string` (URL) | `null`
- Optional company website
- Publicly available information
- Used for company profile

### created_at
- Type: `string` (ISO 8601 datetime)
- Timestamp when company was created
- Read-only (set by system)
- Example: "2026-05-22T17:40:00Z"

### updated_at
- Type: `string` (ISO 8601 datetime)
- Timestamp of last update
- Read-only (set by system)
- Example: "2026-05-22T17:42:00Z"

---

## Integration Examples

### JavaScript/React
```javascript
import { useState } from 'react';

function CreateCompanyForm() {
  const [formData, setFormData] = useState({
    company_name: '',
    company_email: '',
    password: '',
    logo: '',
    website: '',
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const response = await fetch('http://localhost:8000/api/v1/company', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData),
    });

    if (!response.ok) {
      const error = await response.json();
      alert(`Error: ${error.detail}`);
      return;
    }

    const company = await response.json();
    alert(`Company created! ID: ${company.id}`);
    // Store API key in secure location
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        name="company_name"
        placeholder="Company Name"
        value={formData.company_name}
        onChange={handleChange}
        required
      />
      <input
        name="company_email"
        placeholder="Email"
        type="email"
        value={formData.company_email}
        onChange={handleChange}
        required
      />
      <input
        name="password"
        placeholder="Password"
        type="password"
        value={formData.password}
        onChange={handleChange}
        required
      />
      <input
        name="logo"
        placeholder="Logo URL (optional)"
        value={formData.logo}
        onChange={handleChange}
      />
      <input
        name="website"
        placeholder="Website (optional)"
        value={formData.website}
        onChange={handleChange}
      />
      <button type="submit">Create Company</button>
    </form>
  );
}

export default CreateCompanyForm;
```

### TypeScript/Axios
```typescript
import axios from 'axios';

interface Company {
  id: number;
  company_name: string;
  company_email: string;
  password: string;
  logo: string | null;
  website: string | null;
  created_at: string;
  updated_at: string;
}

const api = axios.create({
  baseURL: 'http://localhost:8000',
  headers: {
    'X-API-Key': process.env.REACT_APP_API_KEY,
  },
});

// Create company
export async function createCompany(data: Partial<Company>): Promise<Company> {
  const response = await api.post('/api/v1/company', data);
  return response.data;
}

// List companies
export async function listCompanies(): Promise<Company[]> {
  const response = await api.get('/api/v1/company');
  return response.data;
}

// Get company
export async function getCompany(id: number): Promise<Company> {
  const response = await api.get(`/api/v1/company/${id}`);
  return response.data;
}

// Update company
export async function updateCompany(
  id: number,
  data: Partial<Company>
): Promise<Company> {
  const response = await api.put(`/api/v1/company/${id}`, data);
  return response.data;
}
```

---

## Common Patterns

### Check if Email Exists
Before creating a company, list all companies and check emails:
```bash
curl http://localhost:8000/api/v1/company \
  -H "X-API-Key: sk_..." | jq '.[] | .company_email'
```

### Update Company Profile
```bash
curl -X PUT http://localhost:8000/api/v1/company/1 \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_..." \
  -d '{"website": "https://newsite.com"}'
```

### Display Company Info
```bash
curl http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_..." | jq '.company_name'
```

---

**Last Updated**: 2026-05-22
**Version**: 1.0
