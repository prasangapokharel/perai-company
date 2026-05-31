# Ticket Management Endpoints

Support ticket system for tracking company issues and support requests.

---

## Ticket System Overview

```
Features:
- Create support tickets with issue text
- Categorize tickets (payment, technical, general)
- Track ticket status (open, closed)
- Monitor ticket open/close history
- Get statistics by category and status
- Full CRUD operations
```

---

## Data Models

### Ticket
```python
{
  "id": 1,
  "company_id": 1,
  "issue": "Cannot process credit card payment",
  "category": "payment",  # payment, technical, general
  "status": "open",       # open, closed
  "created_at": "2026-05-31T10:28:57.664354",
  "updated_at": "2026-05-31T10:28:57.664357",
  "ticket_opened_records": [...]  # History
}
```

### TicketOpened (History)
```python
{
  "id": 1,
  "company_id": 1,
  "ticket_id": 1,
  "opened_at": "2026-05-31T10:28:57.809742",
  "closed_at": "2026-05-31T10:29:01.906446",  # null if still open
  "created_at": "2026-05-31T10:28:57.664354",
  "updated_at": "2026-05-31T10:28:57.664357"
}
```

---

## 1. Create Ticket

Create a new support ticket.

### Endpoint
```
POST /api/v1/company/{company_id}/tickets
```

### Headers
```
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Request Body
```json
{
  "issue": "Unable to process credit card payment",
  "category": "payment"
}
```

### Request Fields
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `issue` | string | Yes | Description of the issue |
| `category` | string | No | `payment`, `technical`, or `general` (default: general) |

### Response (201 Created)
```json
{
  "id": 9,
  "company_id": 1,
  "issue": "Unable to process credit card payment",
  "category": "payment",
  "status": "open",
  "created_at": "2026-05-31T10:28:57.664354",
  "updated_at": "2026-05-31T10:28:57.664357",
  "ticket_opened_records": []
}
```

### Example cURL
```bash
curl -X POST http://localhost:8000/api/v1/company/1/tickets \
  -H "Content-Type: application/json" \
  -d '{
    "issue": "Cannot process Visa payment",
    "category": "payment"
  }'
```

### Example JavaScript
```javascript
async function createTicket(companyId, issue, category = "general") {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/tickets`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      issue,
      category
    })
  });

  return await response.json();
}

// Usage
const ticket = await createTicket(1, "API endpoint returns 500", "technical");
console.log('Ticket created:', ticket.id);
```

---

## 2. List Tickets

Get all tickets for a company with optional filtering.

### Endpoint
```
GET /api/v1/company/{company_id}/tickets
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Query Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `status_filter` | string | Filter by status: `open` or `closed` |
| `category_filter` | string | Filter by category: `payment`, `technical`, or `general` |

### Response (200 OK)
```json
[
  {
    "id": 11,
    "company_id": 1,
    "issue": "Need help with account setup",
    "category": "general",
    "status": "open",
    "created_at": "2026-05-31T10:28:57.664354",
    "updated_at": "2026-05-31T10:28:57.664357",
    "ticket_opened_records": []
  },
  {
    "id": 10,
    "company_id": 1,
    "issue": "API returns 500 error",
    "category": "technical",
    "status": "open",
    "created_at": "2026-05-31T10:28:57.664354",
    "updated_at": "2026-05-31T10:28:57.664357",
    "ticket_opened_records": []
  }
]
```

### Example cURL
```bash
# Get all tickets
curl -X GET http://localhost:8000/api/v1/company/1/tickets

# Filter by status
curl -X GET "http://localhost:8000/api/v1/company/1/tickets?status_filter=open"

# Filter by category
curl -X GET "http://localhost:8000/api/v1/company/1/tickets?category_filter=technical"
```

### Example JavaScript
```javascript
async function listTickets(companyId, statusFilter = null, categoryFilter = null) {
  const params = new URLSearchParams();
  if (statusFilter) params.append('status_filter', statusFilter);
  if (categoryFilter) params.append('category_filter', categoryFilter);

  const query = params.toString() ? `?${params.toString()}` : '';
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/tickets${query}`);
  
  return await response.json();
}

// Usage
const openTickets = await listTickets(1, 'open');
const technicalTickets = await listTickets(1, null, 'technical');
```

---

## 3. Get Ticket Details

Retrieve a specific ticket.

### Endpoint
```
GET /api/v1/company/{company_id}/tickets/{ticket_id}
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `ticket_id` | integer | Yes |

### Response (200 OK)
```json
{
  "id": 9,
  "company_id": 1,
  "issue": "Unable to process credit card payment",
  "category": "payment",
  "status": "open",
  "created_at": "2026-05-31T10:28:57.664354",
  "updated_at": "2026-05-31T10:28:57.664357",
  "ticket_opened_records": []
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/tickets/9
```

### Example JavaScript
```javascript
async function getTicket(companyId, ticketId) {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/tickets/${ticketId}`);
  return await response.json();
}

const ticket = await getTicket(1, 9);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `404` | `"Ticket not found"` | Ticket ID doesn't exist |

---

## 4. Update Ticket

Update ticket issue, category, or status.

### Endpoint
```
PUT /api/v1/company/{company_id}/tickets/{ticket_id}
```

### Headers
```
Content-Type: application/json
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `ticket_id` | integer | Yes |

### Request Body (all fields optional)
```json
{
  "issue": "Updated issue description",
  "category": "technical",
  "status": "closed"
}
```

### Response (200 OK)
```json
{
  "id": 9,
  "company_id": 1,
  "issue": "Updated issue description",
  "category": "technical",
  "status": "closed",
  "created_at": "2026-05-31T10:28:57.664354",
  "updated_at": "2026-05-31T10:29:01.906446",
  "ticket_opened_records": []
}
```

### Example cURL
```bash
# Update issue text
curl -X PUT http://localhost:8000/api/v1/company/1/tickets/9 \
  -H "Content-Type: application/json" \
  -d '{"issue": "New description"}'

# Close ticket
curl -X PUT http://localhost:8000/api/v1/company/1/tickets/9 \
  -H "Content-Type: application/json" \
  -d '{"status": "closed"}'
```

### Example JavaScript
```javascript
async function updateTicket(companyId, ticketId, updates) {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/tickets/${ticketId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(updates)
  });

  return await response.json();
}

// Usage
await updateTicket(1, 9, { status: 'closed' });
await updateTicket(1, 9, { category: 'technical' });
```

---

## 5. Delete Ticket

Delete a ticket permanently.

### Endpoint
```
DELETE /api/v1/company/{company_id}/tickets/{ticket_id}
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `ticket_id` | integer | Yes |

### Response (204 No Content)
```
(empty)
```

### Example cURL
```bash
curl -X DELETE http://localhost:8000/api/v1/company/1/tickets/10
```

### Example JavaScript
```javascript
async function deleteTicket(companyId, ticketId) {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/tickets/${ticketId}`, {
    method: 'DELETE'
  });

  return response.status === 204;
}

const success = await deleteTicket(1, 10);
```

### Errors
| Status | Error | Cause |
|--------|-------|-------|
| `404` | `"Ticket not found"` | Ticket doesn't exist |

---

## 6. Get Ticket History

Retrieve the open/close history for a ticket.

### Endpoint
```
GET /api/v1/company/{company_id}/tickets/{ticket_id}/history
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |
| `ticket_id` | integer | Yes |

### Response (200 OK)
```json
{
  "ticket_id": 9,
  "company_id": 1,
  "records": [
    {
      "id": 1,
      "company_id": 1,
      "ticket_id": 9,
      "opened_at": "2026-05-31T10:28:57.809742",
      "closed_at": "2026-05-31T10:29:01.906446",
      "created_at": "2026-05-31T10:28:57.664354",
      "updated_at": "2026-05-31T10:29:01.906446"
    }
  ]
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/tickets/9/history
```

### Example JavaScript
```javascript
async function getTicketHistory(companyId, ticketId) {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/tickets/${ticketId}/history`);
  return await response.json();
}

const history = await getTicketHistory(1, 9);
history.records.forEach(record => {
  console.log(`Opened: ${record.opened_at}, Closed: ${record.closed_at}`);
});
```

---

## 7. Get Ticket Statistics

Get summary statistics for company tickets.

### Endpoint
```
GET /api/v1/company/{company_id}/tickets-stats
```

### Path Parameters
| Parameter | Type | Required |
|-----------|------|----------|
| `company_id` | integer | Yes |

### Response (200 OK)
```json
{
  "company_id": 1,
  "total": 3,
  "open": 2,
  "closed": 1,
  "by_category": {
    "payment": 1,
    "technical": 1,
    "general": 1
  }
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/v1/company/1/tickets-stats
```

### Example JavaScript
```javascript
async function getTicketStats(companyId) {
  const response = await fetch(`http://localhost:8000/api/v1/company/${companyId}/tickets-stats`);
  return await response.json();
}

const stats = await getTicketStats(1);
console.log(`Total tickets: ${stats.total}`);
console.log(`Open: ${stats.open}, Closed: ${stats.closed}`);
console.log('By category:', stats.by_category);
```

---

## Ticket Lifecycle

```
1. CREATE TICKET
   - User submits issue
   - Category: payment/technical/general
   - Status: open (default)
   - Recorded in ticket_opened table
        │
        ├─→ UPDATE TICKET (change issue, category)
        │   - Text can be edited
        │   - Category can be changed
        │
        └─→ CLOSE TICKET (status: closed)
            - Updates ticket_opened.closed_at
            - Ticket record persists
            
2. REOPEN TICKET (if needed)
   - Set status back to open
   - Can close/reopen multiple times
   
3. DELETE TICKET
   - Permanently removes ticket
   - History records deleted (cascade)
   - Unrecoverable
```

---

## Category Guide

| Category | Use Case | Examples |
|----------|----------|----------|
| `payment` | Billing and payment issues | Card declined, invoice problems, refunds |
| `technical` | System and feature issues | API errors, bugs, performance, crashes |
| `general` | General inquiries | Account setup, documentation, features |

---

## Status Values

| Status | Meaning |
|--------|---------|
| `open` | Active ticket, awaiting resolution |
| `closed` | Resolved or closed ticket |

---

## Frontend Integration Example

```javascript
class TicketManager {
  constructor(companyId) {
    this.companyId = companyId;
    this.baseUrl = `http://localhost:8000/api/v1/company/${companyId}`;
  }

  async createTicket(issue, category = 'general') {
    const response = await fetch(`${this.baseUrl}/tickets`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ issue, category })
    });
    return await response.json();
  }

  async listTickets(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await fetch(`${this.baseUrl}/tickets?${params}`);
    return await response.json();
  }

  async getTicket(ticketId) {
    const response = await fetch(`${this.baseUrl}/tickets/${ticketId}`);
    return await response.json();
  }

  async updateTicket(ticketId, updates) {
    const response = await fetch(`${this.baseUrl}/tickets/${ticketId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(updates)
    });
    return await response.json();
  }

  async closeTicket(ticketId) {
    return this.updateTicket(ticketId, { status: 'closed' });
  }

  async deleteTicket(ticketId) {
    await fetch(`${this.baseUrl}/tickets/${ticketId}`, { method: 'DELETE' });
  }

  async getHistory(ticketId) {
    const response = await fetch(`${this.baseUrl}/tickets/${ticketId}/history`);
    return await response.json();
  }

  async getStats() {
    const response = await fetch(`${this.baseUrl}/tickets-stats`);
    return await response.json();
  }
}

// Usage
const tickets = new TicketManager(1);
const newTicket = await tickets.createTicket('Cannot login', 'technical');
const allTickets = await tickets.listTickets({ status_filter: 'open' });
const stats = await tickets.getStats();
```

---

## Error Handling

```javascript
async function handleTicketOperation(operation) {
  try {
    const result = await operation();
    return { success: true, data: result };
  } catch (error) {
    if (error.response?.status === 404) {
      return { success: false, error: 'Ticket not found' };
    }
    if (error.response?.status === 400) {
      return { success: false, error: 'Invalid request' };
    }
    return { success: false, error: 'Unknown error' };
  }
}
```

---

## Next Steps

1. **Create tickets** → Use endpoint 1
2. **List and filter** → Use endpoint 2
3. **View and update** → Use endpoints 3-4
4. **Track history** → Use endpoint 6
5. **Monitor stats** → Use endpoint 7
6. **Close or delete** → Use endpoints 4-5
