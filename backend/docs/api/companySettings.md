# Company Settings API Documentation

## Overview

The Company Settings system allows companies to customize AI behavior with language preferences, tone/style selection, and token limits. Settings are dynamically integrated into the chat system to provide personalized AI responses.

**Base URL**: `/api/v1/company`  
**Authentication**: Required (X-API-Key header)  
**Total Endpoints**: 4

---

## Features

- **Language Support**: English, Nepali
- **Tone Options**: Formal, Casual, Friendly, Professional
- **Token Control**: 100-4000 tokens per response
- **Dynamic System Prompts**: Auto-generated based on settings
- **Full CRUD**: Create, Read, Update, Delete operations
- **Partial Updates**: Update individual fields without affecting others
- **Auto-defaults**: Automatic creation of default settings
- **Authorization**: Company isolation enforced

---

## Data Model

### CompanySettings Table

```sql
CREATE TABLE company_settings (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    company_id INTEGER NOT NULL UNIQUE,
    language VARCHAR(50) DEFAULT 'english',
    tone VARCHAR(50) DEFAULT 'formal',
    max_tokens INTEGER DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE
);
```

### Enums

**Language Options**:
- `english` (default)
- `nepali`

**Tone Options**:
- `formal` (default) - Professional, structured manner
- `casual` - Conversational, everyday language
- `friendly` - Warm, welcoming tone
- `professional` - Expert, authoritative manner

---

## Endpoints

### 1. Create or Update Settings

**POST** `/company/{company_id}/settings`

Create new or update existing company settings.

#### Request

```bash
curl -X POST http://localhost:8000/api/v1/company/1/settings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_your_api_key_here" \
  -d '{
    "language": "english",
    "tone": "professional",
    "max_tokens": 1500
  }'
```

**Request Body**:
```json
{
  "language": "english",          // Optional: english | nepali
  "tone": "formal",               // Optional: formal | casual | friendly | professional
  "max_tokens": 1000              // Optional: 100-4000, must be multiple of 100
}
```

#### Response (201 Created)

```json
{
  "id": 1,
  "company_id": 1,
  "language": "english",
  "tone": "formal",
  "max_tokens": 1000,
  "message": "Settings created/updated successfully"
}
```

#### Error Responses

| Status | Error | Description |
|--------|-------|-------------|
| 401 | Unauthorized | Missing or invalid API key |
| 403 | Forbidden | Access denied (different company) |
| 422 | Unprocessable Entity | Invalid field values |
| 500 | Internal Server Error | Database error |

---

### 2. Get Settings

**GET** `/company/{company_id}/settings`

Retrieve company settings. Automatically creates default settings if none exist.

#### Request

```bash
curl -X GET http://localhost:8000/api/v1/company/1/settings \
  -H "X-API-Key: sk_your_api_key_here"
```

#### Response (200 OK)

```json
{
  "id": 1,
  "company_id": 1,
  "language": "english",
  "tone": "formal",
  "max_tokens": 1000,
  "message": "Settings retrieved successfully"
}
```

#### Response Details

- **Auto-creation**: If settings don't exist, defaults are automatically created
- **Default Values**:
  - `language`: "english"
  - `tone`: "formal"
  - `max_tokens`: 1000

#### Error Responses

| Status | Error | Description |
|--------|-------|-------------|
| 401 | Unauthorized | Missing or invalid API key |
| 403 | Forbidden | Access denied (different company) |
| 500 | Internal Server Error | Database error |

---

### 3. Update Settings

**PUT** `/company/{company_id}/settings`

Update specific settings (partial update supported). Only provided fields are updated.

#### Request

```bash
curl -X PUT http://localhost:8000/api/v1/company/1/settings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_your_api_key_here" \
  -d '{
    "tone": "casual"
  }'
```

**Request Body** (all fields optional):
```json
{
  "language": "nepali",              // Optional
  "tone": "friendly",                // Optional
  "max_tokens": 2000                 // Optional
}
```

#### Response (200 OK)

```json
{
  "id": 1,
  "company_id": 1,
  "language": "english",             // Unchanged
  "tone": "casual",                  // Updated
  "max_tokens": 1000,                // Unchanged
  "message": "Settings updated successfully"
}
```

#### Partial Update Example

Update only tone without affecting language or max_tokens:

```bash
curl -X PUT http://localhost:8000/api/v1/company/1/settings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_your_api_key_here" \
  -d '{"tone": "friendly"}'
```

#### Error Responses

| Status | Error | Description |
|--------|-------|-------------|
| 401 | Unauthorized | Missing or invalid API key |
| 403 | Forbidden | Access denied (different company) |
| 404 | Not Found | Settings not found for company |
| 422 | Unprocessable Entity | Invalid field values |
| 500 | Internal Server Error | Database error |

---

### 4. Delete Settings

**DELETE** `/company/{company_id}/settings`

Delete company settings. Next GET request will create default settings automatically.

#### Request

```bash
curl -X DELETE http://localhost:8000/api/v1/company/1/settings \
  -H "X-API-Key: sk_your_api_key_here"
```

#### Response (204 No Content)

No response body (successful deletion).

#### Behavior After Delete

- Settings record is deleted from database
- Next GET request automatically creates new settings with defaults
- No data loss (defaults are reapplied)

#### Error Responses

| Status | Error | Description |
|--------|-------|-------------|
| 401 | Unauthorized | Missing or invalid API key |
| 403 | Forbidden | Access denied (different company) |
| 500 | Internal Server Error | Database error |

---

## Integration with Chat

Settings are automatically integrated into the chat system to customize AI responses.

### How It Works

1. **Settings Retrieval**: Chat endpoint loads company settings
2. **Prompt Generation**: Dynamic system prompt created with:
   - Tone-specific instructions
   - Language preference
   - Company knowledge base
   - Token limit
3. **API Call**: Groq API called with custom parameters
4. **Response**: AI returns response matching tone and language

### Example Chat Flow

```
User sends: POST /company/1/chat/query
                 with API key

System:
1. Loads company settings:
   - language: "nepali"
   - tone: "friendly"
   - max_tokens: 2000

2. Generates system prompt:
   "You are a friendly AI assistant...
    Respond in Nepali...
    Know about: [company knowledge base]
    Max tokens: 2000"

3. Sends to Groq with max_tokens=2000

4. Returns response in Nepali with friendly tone
```

### System Prompt Example

For settings: `{language: "nepali", tone: "friendly", max_tokens: 2000}`

Generated prompt:
```
SYSTEM PROMPT FOR AI ASSISTANT
==================================================

TONE & BEHAVIOR:
You are a warm and welcoming AI assistant. Respond with friendliness and empathy.
Use a personable tone while maintaining professionalism. Show genuine interest in helping.

LANGUAGE PREFERENCE:
आप नेपालीमा प्रतिक्रिया दिइरहेको हुनुहुन्छ। स्पष्ट र व्याकरणिक रूपमा सही प्रतिक्रियाहरू प्रदान गर्नुहोस्।

TOKEN LIMIT: 2000 tokens

COMPANY KNOWLEDGE BASE:
[Your company's finetune data]

==================================================
```

---

## Validation Rules

### Language
- **Type**: String (enum)
- **Allowed Values**: "english", "nepali"
- **Default**: "english"
- **Required**: No

### Tone
- **Type**: String (enum)
- **Allowed Values**: "formal", "casual", "friendly", "professional"
- **Default**: "formal"
- **Required**: No

### Max Tokens
- **Type**: Integer
- **Min Value**: 100
- **Max Value**: 4000
- **Default**: 1000
- **Required**: No
- **Note**: Controls maximum length of AI responses

---

## Examples

### Example 1: Formal English Settings

Create formal settings for English-speaking users:

```bash
curl -X POST http://localhost:8000/api/v1/company/1/settings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_abc123..." \
  -d '{
    "language": "english",
    "tone": "formal",
    "max_tokens": 1000
  }'
```

### Example 2: Friendly Nepali Settings

Create settings for Nepali-speaking users with friendly tone:

```bash
curl -X POST http://localhost:8000/api/v1/company/1/settings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_abc123..." \
  -d '{
    "language": "nepali",
    "tone": "friendly",
    "max_tokens": 1500
  }'
```

### Example 3: Increase Token Limit

Update only token limit without affecting language or tone:

```bash
curl -X PUT http://localhost:8000/api/v1/company/1/settings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_abc123..." \
  -d '{"max_tokens": 3000}'
```

### Example 4: Change Tone Only

Change tone to casual without affecting other settings:

```bash
curl -X PUT http://localhost:8000/api/v1/company/1/settings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_abc123..." \
  -d '{"tone": "casual"}'
```

### Example 5: Retrieve Current Settings

Get company's current settings:

```bash
curl -X GET http://localhost:8000/api/v1/company/1/settings \
  -H "X-API-Key: sk_abc123..."
```

Response:
```json
{
  "id": 1,
  "company_id": 1,
  "language": "nepali",
  "tone": "friendly",
  "max_tokens": 1500,
  "message": "Settings retrieved successfully"
}
```

---

## Best Practices

### 1. Set Settings Early

Set company settings when first creating the company:

```
1. Register company
2. Create API key
3. Create settings (language, tone, max_tokens)
4. Upload finetune data
5. Start using chat
```

### 2. Match Audience Language

Set language based on company's primary audience:

- **English**: For English-speaking users
- **Nepali**: For Nepali-speaking users

### 3. Choose Appropriate Tone

Match tone to company's brand and communication style:

- **Formal**: Banks, Government, Legal services
- **Professional**: Consulting, Technical support
- **Friendly**: Retail, E-commerce, Customer service
- **Casual**: Entertainment, Social media, Community

### 4. Optimize Token Limits

Consider response quality vs. performance:

- **100-500**: Short, quick responses
- **500-1500**: Standard responses (recommended)
- **1500-4000**: Detailed, comprehensive responses

### 5. Test Before Going Live

Test settings with sample queries:

```bash
# Set test settings
curl -X POST .../settings -d '...'

# Send test query
curl -X POST .../chat/query -d '{"prompt": "test query"}'

# Verify response tone and language
```

---

## Frontend Integration

### JavaScript Example

```javascript
// Set company settings
async function setCompanySettings(companyId, apiKey, settings) {
  const response = await fetch(
    `/api/v1/company/${companyId}/settings`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey
      },
      body: JSON.stringify({
        language: settings.language || 'english',
        tone: settings.tone || 'formal',
        max_tokens: settings.max_tokens || 1000
      })
    }
  );
  return response.json();
}

// Get company settings
async function getCompanySettings(companyId, apiKey) {
  const response = await fetch(
    `/api/v1/company/${companyId}/settings`,
    {
      headers: { 'X-API-Key': apiKey }
    }
  );
  return response.json();
}

// Update settings (partial)
async function updateCompanySettings(companyId, apiKey, updates) {
  const response = await fetch(
    `/api/v1/company/${companyId}/settings`,
    {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey
      },
      body: JSON.stringify(updates)
    }
  );
  return response.json();
}

// Usage
await setCompanySettings(1, 'sk_abc...', {
  language: 'english',
  tone: 'friendly',
  max_tokens: 1500
});
```

---

## Testing

Run the test suite to verify all functionality:

```bash
cd backend
python scripts/test_company_settings.py
```

**Test Coverage**:
- ✅ Create settings
- ✅ Get settings (with auto-creation)
- ✅ Update settings (full and partial)
- ✅ Delete settings
- ✅ Authorization checks
- ✅ All language options
- ✅ All tone options
- ✅ Token limit ranges

---

## Troubleshooting

### Issue: "Settings not found"

**Cause**: Trying to update non-existent settings

**Solution**: Use GET first (which auto-creates) or POST to create

```bash
# GET auto-creates if not exists
curl -X GET .../settings -H "X-API-Key: ..."
```

### Issue: "Access denied"

**Cause**: Trying to access another company's settings

**Solution**: Use API key for the correct company

```bash
# Make sure X-API-Key matches company_id in URL
curl -X GET /company/1/settings -H "X-API-Key: sk_for_company_1"
```

### Issue: "Invalid field values"

**Cause**: Invalid language, tone, or token value

**Solution**: Use only valid enum values

```json
{
  "language": "english",              // ✓ Valid
  "tone": "professional",             // ✓ Valid
  "max_tokens": 1500                  // ✓ Valid (100-4000)
}
```

---

## Related Documentation

- [Chat API](/docs/api/chat.md) - Integration with chat system
- [API Key Management](/docs/api/apikey.md) - Authentication details
- [Company Management](/docs/api/company.md) - Company CRUD operations
- [Authentication](/docs/api/auth.md) - Authentication flow

---

**Last Updated**: May 31, 2026  
**API Version**: 1.0.0  
**Status**: Production Ready
