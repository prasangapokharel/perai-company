# Chat Endpoints

Send chat queries to the AI and retrieve responses. The AI uses your company's finetune/RAG data as context for all responses.

---

## Chat System Overview

```
User Message
    ↓
POST /chat/query
    ↓
Backend retrieves company finetune data
    ↓
Sends to Groq AI with company context
    ↓
AI generates response using your knowledge base
    ↓
Response returned to frontend
    ↓
Display to user
```

---

## 1. Chat Query

Send a message to the AI and get a response.

### Endpoint
```
POST /api/v1/company/{company_id}/chat/query
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
  "prompt": "What are your main services?"
}
```

### Request Fields
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `prompt` | string | Yes | User question or message (max 4096 chars) |

### Response (200 OK)
```json
{
  "model_name": "perai-techcorp",
  "company_id": 1,
  "response": "TechCorp specializes in three main services:\n\n1. **Cloud Infrastructure** - We provide scalable cloud hosting and deployment solutions with AWS integration and Kubernetes management.\n\n2. **AI Solutions** - Our team develops custom machine learning models, NLP processing, and computer vision solutions.\n\n3. **Consulting** - We offer technology consulting and architecture services including system design, optimization, and security audits.\n\nFor more information, visit our website at https://techcorp.com or contact our sales team."
}
```

### Response Fields
| Field | Type | Notes |
|-------|------|-------|
| `model_name` | string | The company's model name (e.g., `perai-techcorp`) |
| `company_id` | integer | Company ID for verification |
| `response` | string | AI-generated response using company context |

### How It Works

1. **Retrieves Context**: Backend fetches company's finetune data (knowledge base)
2. **Prepares Prompt**: Combines user prompt with company context
3. **Calls Groq AI**: Sends combined prompt to Groq with model name
4. **Returns Response**: AI response based on your company's specific information

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company/1/chat/query \
  -H "X-API-Key: sk_YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "What are your main services?"
  }'
```

### Example JavaScript
```javascript
async function chatWithAI(companyId, question) {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/chat/query`, {
    method: 'POST',
    headers: {
      'X-API-Key': localStorage.getItem('perai_api_key'),
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      prompt: question
    })
  });

  const result = await response.json();
  return result.response;
}

// Usage
const answer = await chatWithAI(1, 'What services do you offer?');
console.log('AI Response:', answer);
```

### Example React Component
```jsx
import { useState } from 'react';

export function ChatWidget() {
  const [message, setMessage] = useState('');
  const [response, setResponse] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSend = async () => {
    setLoading(true);
    try {
      const res = await fetch(`http://localhost:8000/api/v1/company/1/chat/query`, {
        method: 'POST',
        headers: {
          'X-API-Key': localStorage.getItem('perai_api_key'),
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ prompt: message })
      });

      const data = await res.json();
      setResponse(data.response);
    } catch (error) {
      console.error('Chat failed:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="chat-widget">
      <textarea
        value={message}
        onChange={(e) => setMessage(e.target.value)}
        placeholder="Ask a question..."
      />
      <button onClick={handleSend} disabled={loading}>
        {loading ? 'Thinking...' : 'Send'}
      </button>
      {response && <div className="response">{response}</div>}
    </div>
  );
}
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |
| `400` | `"No finetune data found"` | Company hasn't uploaded knowledge base yet |
| `400` | `"Empty prompt"` | Prompt is empty or whitespace only |
| `500` | `"AI service error"` | Groq API error (contact support) |

### Best Practices

#### 1. Error Handling
```javascript
async function safeChatQuery(companyId, prompt) {
  try {
    const response = await fetch(`/api/v1/company/${companyId}/chat/query`, {
      method: 'POST',
      headers: {
        'X-API-Key': localStorage.getItem('perai_api_key'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ prompt })
    });

    if (response.status === 400) {
      const error = await response.json();
      console.error('Bad request:', error.detail);
      // Suggest uploading finetune data if missing
      return null;
    }

    if (response.status === 401) {
      console.error('Invalid API key');
      // Redirect to API key management
      return null;
    }

    if (response.status === 500) {
      console.error('AI service unavailable');
      return null;
    }

    return await response.json();
  } catch (error) {
    console.error('Network error:', error);
    return null;
  }
}
```

#### 2. Loading States
```javascript
const [chatState, setChatState] = useState({
  loading: false,
  messages: [],
  error: null
});

async function sendMessage(prompt) {
  setChatState(prev => ({
    ...prev,
    loading: true,
    error: null
  }));

  try {
    const result = await chatWithAI(companyId, prompt);
    
    setChatState(prev => ({
      ...prev,
      loading: false,
      messages: [...prev.messages, {
        role: 'user',
        content: prompt
      }, {
        role: 'assistant',
        content: result.response
      }]
    }));
  } catch (error) {
    setChatState(prev => ({
      ...prev,
      loading: false,
      error: error.message
    }));
  }
}
```

#### 3. Message Streaming (Future)
```javascript
// Current: Single response
POST /api/v1/company/{company_id}/chat/query → Response

// Future: Streaming responses (coming in Phase 2)
POST /api/v1/company/{company_id}/chat/stream → Server-Sent Events
```

---

## 2. Chat Ping

Health check for chat service.

### Endpoint
```
GET /api/v1/company/{company_id}/chat/ping
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
  "company_id": 1,
  "status": "ok"
}
```

### Use Cases

#### 1. Before Uploading Finetune Data
```javascript
// Check if backend is ready before letting user upload
const pingResponse = await fetch(`/api/v1/company/1/chat/ping`, {
  headers: { 'X-API-Key': apiKey }
});

if (pingResponse.ok) {
  // Enable upload button
  setCanUpload(true);
}
```

#### 2. Connection Verification
```javascript
async function isConnected(companyId, apiKey) {
  try {
    const response = await fetch(`/api/v1/company/${companyId}/chat/ping`, {
      headers: { 'X-API-Key': apiKey }
    });
    return response.ok;
  } catch {
    return false;
  }
}

// Show status indicator
const connected = await isConnected(1, apiKey);
setStatus(connected ? 'connected' : 'disconnected');
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/chat/ping \
  -H "X-API-Key: sk_YOUR_API_KEY"
```

### Example JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/company/1/chat/ping', {
  headers: {
    'X-API-Key': 'sk_YOUR_API_KEY'
  }
});

const result = await response.json();
console.log('Chat Service Status:', result.status);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `401` | `"Invalid API key"` | Missing or invalid API key |
| `404` | `"Company not found"` | Invalid company_id |

---

## Chat Workflow

### 1. First Time Setup
```
┌─────────────────────────────────┐
│ User Registers Company          │
│ Receives company_id             │
└────────────┬────────────────────┘
             │
             ▼
     Create API Key
             │
             ▼
┌─────────────────────────────────┐
│ User Uploads Finetune Data      │
│ - Knowledge base content        │
│ - Company information           │
│ - Services description          │
│ - FAQ, pricing, etc             │
└────────────┬────────────────────┘
             │
             ▼
     GET /company/{id}/finetune
     Verify data uploaded
             │
             ▼
     Ping chat service
             │
             ▼
┌─────────────────────────────────┐
│ Ready to Chat!                  │
└─────────────────────────────────┘
```

### 2. During Chat
```
User Input
    ↓
"What are your services?"
    ↓
POST /chat/query
    ↓
Backend + Context (from finetune)
    ↓
Groq AI processes with company knowledge
    ↓
Response generated
    ↓
"Based on our knowledge base, we offer..."
    ↓
Display to User
```

---

## Context Usage Example

### Finetune Data Uploaded
```markdown
# TechCorp Knowledge Base

## Services
1. Cloud Infrastructure - $999/month
2. AI Solutions - $499/month
3. Consulting - Custom pricing

## Support
- Email: support@techcorp.com
- Phone: +1-800-TECH-CORP
```

### User Ask
```
"How much do your services cost?"
```

### AI Response
```
Based on our services, here's our pricing:

1. **Cloud Infrastructure** - $999/month
2. **AI Solutions** - $499/month
3. **Consulting** - Custom pricing

For more details, contact us at:
- Email: support@techcorp.com
- Phone: +1-800-TECH-CORP
```

---

## Rate Limiting & Quotas

Current: No limits (Phase 2 will add):
- Per-company rate limits (e.g., 100 requests/minute)
- Monthly usage quotas
- Usage analytics dashboard

---

## Troubleshooting

### No Finetune Data Error
```
Request: POST /chat/query with prompt
Response: 400 "No finetune data found"

Solution:
1. Upload finetune data: POST /company/{id}/finetune
2. Verify with: GET /company/{id}/finetune
3. Retry chat query
```

### Invalid API Key
```
Response: 401 "Invalid API key"

Solution:
1. Check X-API-Key header is present
2. Verify key hasn't expired: GET /company/{id}/api-keys
3. Check key hasn't been revoked
4. Create new key if needed: POST /company/{id}/api-keys
```

### Slow Responses
```
Timeout waiting for response

Solution:
1. Check server is running
2. Verify backend health: GET /health (no auth needed)
3. Contact support if issue persists
```

---

## Next Steps

1. **Upload Finetune Data** → See `company.md` endpoint 6
2. **First Chat** → Use this endpoint with simple question
3. **Error Handling** → Implement checks above
4. **Build UI** → Create chat widget/interface
5. **Monitor Usage** → See analytics (Phase 2)

---

## API Response Time

Expected response times:
- **Ping**: < 100ms
- **Query**: 1-5 seconds (depends on prompt length and Groq API)
- **First query**: May be slightly longer (connection setup)

---

## Advanced: Custom System Prompts

Future feature: Customize how AI responds
```json
{
  "prompt": "What are your services?",
  "system_prompt": "You are a friendly sales representative..."  // Coming in Phase 2
}
```

---

## Security Notes

- API key is validated on every request
- Company context is isolated (cannot see other companies' data)
- Finetune data is stored securely on server
- All communication should use HTTPS in production
- No request logging with sensitive data
