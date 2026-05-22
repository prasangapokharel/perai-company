# Error Codes & Status Codes

## HTTP Status Codes

### 2xx Success

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful, returning data |
| 201 | Created | Resource successfully created |

### 4xx Client Errors

| Code | Status | Description |
|------|--------|-------------|
| 400 | Bad Request | Invalid input or business logic error |
| 401 | Unauthorized | Missing or invalid API key |
| 403 | Forbidden | Not allowed to access this resource |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation error (invalid data format) |

### 5xx Server Errors

| Code | Status | Description |
|------|--------|-------------|
| 500 | Internal Server Error | Unexpected server error |
| 503 | Service Unavailable | Server temporarily unavailable |

---

## Error Response Format

### Standard Error Response
```json
{
  "detail": "Error message describing what went wrong"
}
```

### Example
```json
{
  "detail": "Company with this email already exists"
}
```

---

## Common Error Messages

### Authentication Errors (401/403)

#### Missing API Key
**Response**: 403 Forbidden
```json
{
  "detail": "X-API-Key header is required"
}
```

**Solution**: Add `X-API-Key` header to your request

#### Invalid API Key
**Response**: 401 Unauthorized
```json
{
  "detail": "Invalid API key"
}
```

**Solution**: Verify API key is correct, not revoked, and not expired

#### Expired API Key
**Response**: 401 Unauthorized
```json
{
  "detail": "API key has expired"
}
```

**Solution**: Create a new API key with future expiry date

#### Revoked API Key
**Response**: 401 Unauthorized
```json
{
  "detail": "API key has been revoked"
}
```

**Solution**: Create a new API key

---

### Company Errors (400/404)

#### Company Not Found
**Response**: 404 Not Found
```json
{
  "detail": "Company not found"
}
```

**Solution**: Verify company ID exists

#### Email Already Exists
**Response**: 400 Bad Request
```json
{
  "detail": "Company with email 'contact@acme.com' already exists"
}
```

**Solution**: Use a different email address

#### Missing Required Field
**Response**: 422 Unprocessable Entity
```json
{
  "detail": "Field 'company_name' is required"
}
```

**Solution**: Include all required fields in request body

---

### API Key Errors (400/404)

#### Key Not Found
**Response**: 404 Not Found
```json
{
  "detail": "API key not found"
}
```

**Solution**: Verify key ID exists for the company

#### Key Name Already Exists
**Response**: 400 Bad Request
```json
{
  "detail": "API key with name 'prod_key' already exists for this company"
}
```

**Solution**: Use a unique key name or delete the existing one

#### Invalid Expiry Date
**Response**: 422 Unprocessable Entity
```json
{
  "detail": "Expiry date must be in the future"
}
```

**Solution**: Set expiry date to a future date

#### Key Already Revoked
**Response**: 400 Bad Request
```json
{
  "detail": "API key is already revoked"
}
```

**Solution**: Create a new key instead

---

### Finetune Errors (404)

#### Finetune Data Not Found
**Response**: 404 Not Found
```json
{
  "detail": "Finetune data not found for this company"
}
```

**Solution**: Upload finetune data first:
```bash
curl -X POST /api/v1/company/{id}/finetune \
  -H "X-API-Key: sk_..." \
  -d '{"content": "# Company KB"}'
```

---

### Validation Errors (422)

#### Invalid Request Body
**Response**: 422 Unprocessable Entity
```json
{
  "detail": "Invalid JSON in request body"
}
```

**Solution**: Ensure request body is valid JSON

#### Invalid Email Format
**Response**: 422 Unprocessable Entity
```json
{
  "detail": "Invalid email format"
}
```

**Solution**: Use a valid email address

#### Invalid Date Format
**Response**: 422 Unprocessable Entity
```json
{
  "detail": "Invalid date format. Use ISO 8601: YYYY-MM-DDTHH:MM:SS"
}
```

**Solution**: Use ISO 8601 date format: `2026-08-22T00:00:00`

---

## Error Handling in Frontend

### JavaScript/Fetch
```javascript
async function apiCall(url, options = {}) {
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': API_KEY,
        ...options.headers,
      },
    });

    // Handle error status codes
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.detail || `HTTP ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error('API Error:', error.message);
    // Show user-friendly error message
    throw error;
  }
}
```

### React Error Boundary
```jsx
import { useState } from 'react';

function APIErrorHandler({ children }) {
  const [error, setError] = useState(null);

  const handleError = (err) => {
    setError(err.message);
    setTimeout(() => setError(null), 5000);
  };

  return (
    <div>
      {error && (
        <div className="error-banner">
          <p>Error: {error}</p>
        </div>
      )}
      {children}
    </div>
  );
}

export default APIErrorHandler;
```

### Axios Interceptor
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000',
});

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      // API returned error response
      const message = error.response.data.detail || 'An error occurred';
      console.error('API Error:', message);
      throw new Error(message);
    } else if (error.request) {
      // Request made but no response
      console.error('Network Error:', error.message);
      throw new Error('Network error. Please try again.');
    } else {
      // Error in request setup
      console.error('Error:', error.message);
      throw error;
    }
  }
);

export default api;
```

---

## Status Code Decision Tree

```
┌─ Did request succeed?
│
├─ YES
│  ├─ Was resource created? → 201 Created
│  └─ Otherwise → 200 OK
│
└─ NO
   ├─ Is it an auth error?
   │  ├─ Missing header? → 403 Forbidden
   │  └─ Invalid key? → 401 Unauthorized
   │
   ├─ Is it a validation error?
   │  └─ Bad format/invalid data? → 422 Unprocessable Entity
   │
   ├─ Is resource not found?
   │  └─ Doesn't exist? → 404 Not Found
   │
   ├─ Is it a business logic error?
   │  └─ Email exists, key name taken, etc? → 400 Bad Request
   │
   └─ Is it a server problem?
      └─ Unexpected error? → 500 Internal Server Error
```

---

## Debugging Tips

### Enable Verbose Logging
```bash
# cURL with verbose output
curl -v http://localhost:8000/api/v1/company \
  -H "X-API-Key: sk_..."

# Firefox DevTools: Network tab
# Chrome DevTools: Network tab
# Postman: Console tab
```

### Check Request Headers
```javascript
// In browser console
fetch('http://localhost:8000/api/v1/company', {
  headers: { 'X-API-Key': 'sk_...' }
}).then(r => {
  console.log('Status:', r.status);
  console.log('Headers:', r.headers);
  return r.json();
}).then(data => console.log('Data:', data));
```

### Validate JSON
```bash
# Check if JSON is valid
echo '{"test": "value"}' | jq .

# Pretty print response
curl ... | jq .
```

### Common Mistakes

#### Mistake 1: Wrong Header Name
```javascript
// ❌ Wrong
fetch(url, { headers: { 'api-key': key } })

// ✓ Correct
fetch(url, { headers: { 'X-API-Key': key } })
```

#### Mistake 2: Missing Content-Type
```javascript
// ❌ Wrong (POST)
fetch(url, { 
  method: 'POST',
  body: JSON.stringify({...})
})

// ✓ Correct
fetch(url, { 
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({...})
})
```

#### Mistake 3: Forgetting to Check Status
```javascript
// ❌ Wrong
const data = await response.json();

// ✓ Correct
if (!response.ok) throw new Error(response.statusText);
const data = await response.json();
```

#### Mistake 4: Invalid Date Format
```javascript
// ❌ Wrong
{ expiry_date: "08/22/2026" }

// ✓ Correct
{ expiry_date: "2026-08-22T00:00:00" }
```

---

## Rate Limiting (Future)

Currently: No rate limiting

Future: 100 requests/minute per API key

When implemented:
```
429 Too Many Requests
{
  "detail": "Rate limit exceeded. Maximum 100 requests per minute."
}
```

---

## Support

For persistent issues:
1. Check this error document
2. Review relevant endpoint documentation
3. Enable verbose logging in your client
4. Check server logs
5. Try with Postman to isolate the issue

---

**Last Updated**: 2026-05-22
**Version**: 1.0
