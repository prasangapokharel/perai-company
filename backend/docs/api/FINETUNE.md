# Finetune/RAG API

## Overview

Manage company knowledge base (finetune data) for RAG (Retrieval-Augmented Generation).

All finetune data is stored as markdown files on disk for easy management and retrieval.

## Endpoints

### 1. Upload Finetune Data

Upload or update company knowledge base (markdown format).

**Endpoint**: `POST /api/v1/company/{company_id}/finetune`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |

**Request Body**:
```json
{
  "content": "# Company Knowledge Base\n\n## Services\n- Service A\n- Service B"
}
```

**Parameters**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `content` | string | Yes | Markdown content of knowledge base |

**Success Response** (200/201):
```json
{
  "id": 1,
  "company_id": 1,
  "rag_company_path": "/home/prasanga/perai-company/backend/app/core/finetune/rag/companies/1/company.md",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:40:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Company doesn't exist |
| 400 | Invalid content | Empty or invalid markdown |

**Example**:
```bash
curl -X POST http://localhost:8000/api/v1/company/1/finetune \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8" \
  -d '{
    "content": "# Acme Corp Knowledge Base\n\n## About Us\nWe provide innovative solutions.\n\n## Services\n- Consulting\n- Development\n- Support"
  }'
```

---

### 2. Retrieve Finetune Data

Get the company knowledge base.

**Endpoint**: `GET /api/v1/company/{company_id}/finetune`

**Authentication**: Required (X-API-Key header)

**Path Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `company_id` | integer | Yes | Company ID |

**Query Parameters**: None

**Success Response** (200 OK):
```json
{
  "id": 1,
  "company_id": 1,
  "content": "# Acme Corp Knowledge Base\n\n## About Us\nWe provide innovative solutions.\n\n## Services\n- Consulting\n- Development\n- Support",
  "rag_company_path": "/home/prasanga/perai-company/backend/app/core/finetune/rag/companies/1/company.md",
  "created_at": "2026-05-22T17:40:00Z",
  "updated_at": "2026-05-22T17:40:00Z"
}
```

**Error Responses**:
| Status | Error | Description |
|--------|-------|-------------|
| 404 | Not found | Company doesn't exist or no finetune data |

**Example**:
```bash
curl http://localhost:8000/api/v1/company/1/finetune \
  -H "X-API-Key: sk_a99KGHR57bhNzRv5vhnR-X8jhtoqm1qiIclDt2urUG8"
```

---

## Finetune Data Format

### Markdown Structure

Finetune data should be well-structured markdown for optimal RAG performance.

**Recommended Structure**:
```markdown
# Company Name - Knowledge Base

## Overview
Brief description of the company and what you do.

## About Us
Company history, mission, and values.

## Products/Services
### Product A
Description and key features.

### Product B
Description and key features.

## Pricing
Pricing tiers and options.

## Support
How customers can get help.

## FAQ
Common questions and answers.

## Contact
Contact information and links.
```

**Example - Full Knowledge Base**:
```markdown
# Acme Corporation Knowledge Base

## Overview
Acme is a leading provider of innovative business solutions.

## About Us
Founded in 2015, we've been helping businesses streamline operations
for over a decade. Our team of 50+ experts is dedicated to your success.

## Services
### Consulting
Strategic advice for business growth
- Digital transformation
- Process optimization
- Change management

### Development
Custom software solutions
- Web applications
- Mobile apps
- API development

### Support
24/7 professional support
- Email support
- Phone support
- Dedicated account manager

## Pricing
- Starter: $99/month
- Professional: $299/month
- Enterprise: Custom pricing

## FAQ
### Q: How quickly can you start?
A: Usually within 1 week of contract signing.

### Q: Do you offer discounts for annual plans?
A: Yes, 15% discount for annual contracts.

## Contact
Email: contact@acme.com
Phone: +1-800-ACME-123
Website: https://acme.com
```

---

## Best Practices

### ✓ DO
- Use clear heading hierarchy (# for main title, ## for sections)
- Include all relevant company information
- Use bullet points for lists
- Keep content accurate and up-to-date
- Use simple, clear language
- Include FAQ section
- Add contact information
- Structure for easy scanning

### ✗ DON'T
- Include sensitive information (passwords, API keys)
- Use very long paragraphs
- Include HTML or code blocks (use markdown)
- Add images or binary data
- Include customer data or PII
- Use special characters excessively
- Leave outdated information

---

## Integration Examples

### JavaScript/React
```javascript
import { useState } from 'react';

function FinetuneEditor() {
  const [content, setContent] = useState('');
  const [status, setStatus] = useState('');
  const companyId = 1;
  const apiKey = process.env.REACT_APP_API_KEY;

  const handleUpload = async () => {
    setStatus('Uploading...');
    
    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/finetune`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-API-Key': apiKey,
          },
          body: JSON.stringify({ content }),
        }
      );

      if (!response.ok) {
        const error = await response.json();
        setStatus(`Error: ${error.detail}`);
        return;
      }

      setStatus('✓ Finetune data updated successfully!');
    } catch (err) {
      setStatus(`Error: ${err.message}`);
    }
  };

  const handleRetrieve = async () => {
    setStatus('Loading...');
    
    try {
      const response = await fetch(
        `http://localhost:8000/api/v1/company/${companyId}/finetune`,
        {
          headers: { 'X-API-Key': apiKey },
        }
      );

      if (!response.ok) {
        const error = await response.json();
        setStatus(`Error: ${error.detail}`);
        return;
      }

      const data = await response.json();
      setContent(data.content);
      setStatus('✓ Finetune data loaded');
    } catch (err) {
      setStatus(`Error: ${err.message}`);
    }
  };

  return (
    <div>
      <textarea
        value={content}
        onChange={(e) => setContent(e.target.value)}
        placeholder="Enter markdown knowledge base..."
        rows={20}
        cols={80}
      />
      <div>
        <button onClick={handleUpload}>Upload</button>
        <button onClick={handleRetrieve}>Load</button>
      </div>
      {status && <p>{status}</p>}
    </div>
  );
}

export default FinetuneEditor;
```

### TypeScript/Axios
```typescript
import axios from 'axios';

interface FinetuneData {
  id: number;
  company_id: number;
  content: string;
  rag_company_path: string;
  created_at: string;
  updated_at: string;
}

const api = axios.create({
  baseURL: 'http://localhost:8000',
  headers: {
    'X-API-Key': process.env.REACT_APP_API_KEY,
  },
});

// Upload finetune data
export async function uploadFinetune(
  companyId: number,
  content: string
): Promise<FinetuneData> {
  const response = await api.post(
    `/api/v1/company/${companyId}/finetune`,
    { content }
  );
  return response.data;
}

// Retrieve finetune data
export async function getFinetune(companyId: number): Promise<FinetuneData> {
  const response = await api.get(`/api/v1/company/${companyId}/finetune`);
  return response.data;
}

// Update finetune data
export async function updateFinetune(
  companyId: number,
  content: string
): Promise<FinetuneData> {
  return uploadFinetune(companyId, content);
}
```

### cURL Examples
```bash
# Upload finetune data
curl -X POST http://localhost:8000/api/v1/company/1/finetune \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_..." \
  -d '{
    "content": "# My Company\n\n## Services\n- Service A\n- Service B"
  }'

# Retrieve finetune data
curl http://localhost:8000/api/v1/company/1/finetune \
  -H "X-API-Key: sk_..."

# Save to file
curl http://localhost:8000/api/v1/company/1/finetune \
  -H "X-API-Key: sk_..." | jq -r '.content' > company_kb.md
```

---

## Storage Details

### File Location
```
backend/app/core/finetune/rag/companies/{company_id}/company.md
```

### Example Paths
- Company 1: `backend/app/core/finetune/rag/companies/1/company.md`
- Company 2: `backend/app/core/finetune/rag/companies/2/company.md`
- Company 10: `backend/app/core/finetune/rag/companies/10/company.md`

### File Format
- Format: Plain text markdown (`.md`)
- Encoding: UTF-8
- Newlines: LF (Unix style)
- Line endings: Preserved as-is

### Versioning
- No version control in API
- Latest version always overwrites
- For history, maintain backups manually
- Consider using Git for version control

---

## Common Patterns

### Initialize Company Knowledge Base
```bash
curl -X POST http://localhost:8000/api/v1/company/1/finetune \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_..." \
  -d '{
    "content": "# Company Knowledge Base\n\nPlaceholder content"
  }'
```

### Update with External Markdown
```bash
# Create local file
cat > kb.md << 'EOF'
# Company KB
## Services
- Service A
EOF

# Upload
curl -X POST http://localhost:8000/api/v1/company/1/finetune \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_..." \
  -d @<(jq -R -s '{content:.}' kb.md)
```

### Preview Knowledge Base
```bash
curl http://localhost:8000/api/v1/company/1/finetune \
  -H "X-API-Key: sk_..." | jq -r '.content'
```

---

## Troubleshooting

### Empty Knowledge Base
If finetune data is empty, ensure you've uploaded content:
```bash
curl -X POST http://localhost:8000/api/v1/company/1/finetune \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_..." \
  -d '{"content": "# Initial KB"}'
```

### 404 Not Found
Ensure the company exists:
```bash
curl http://localhost:8000/api/v1/company/1 \
  -H "X-API-Key: sk_..."
```

### Special Characters
For special characters, ensure proper JSON escaping:
```json
{
  "content": "Line 1\nLine 2\n# Heading"
}
```

---

**Last Updated**: 2026-05-22
**Version**: 1.0
