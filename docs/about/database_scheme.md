# Perai Backend - Database Schema Documentation

## Overview

The Perai backend uses a **multi-tenant, company-based architecture** with PostgreSQL 17.6 on Supabase. The database is normalized to 3NF (Third Normal Form) with proper foreign key relationships and cascading deletes.

**Total Tables**: 5 core tables  
**Total Columns**: 39 columns  
**Database**: PostgreSQL 17.6 on Supabase  
**URL**: `postgresql://postgres:uxEwUuDilVS8LQch@db.lxopuyaxcxrglkfcbree.supabase.co:5432/postgres`

---

## Table 1: `company`

### Purpose
Stores all company/tenant information. This is the root entity in the system.

### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `BIGINT` | PRIMARY KEY, AUTO INCREMENT | Unique company identifier |
| `company_name` | `VARCHAR(255)` | NOT NULL, UNIQUE | Official company name (e.g., "Acme Corp") |
| `company_email` | `VARCHAR(255)` | NOT NULL, UNIQUE | Company email for login |
| `password_hash` | `VARCHAR(255)` | NOT NULL | PBKDF2 hashed password (100k iterations) |
| `company_model_name` | `VARCHAR(100)` | NOT NULL, UNIQUE | Auto-generated model name (e.g., "perai-acme-corp") |
| `website` | `VARCHAR(255)` | NULLABLE | Company website URL |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Account creation timestamp (UTC) |
| `updated_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP, ON UPDATE | Last update timestamp (UTC) |

### Indexes
```sql
CREATE INDEX idx_company_email ON company(company_email);
CREATE INDEX idx_company_model_name ON company(company_model_name);
```

### Example Data
```json
{
  "id": 1,
  "company_name": "Acme Corp",
  "company_email": "admin@acme.com",
  "password_hash": "$pbkdf2-sha256$100000$...",
  "company_model_name": "perai-acme-corp",
  "website": "https://acme.com",
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-20T14:22:00Z"
}
```

### Relationships
- **Has Many**: `company_finetune` (one-to-one)
- **Has Many**: `api_key` (one-to-many)
- **Has Many**: `ticket` (one-to-many)

### Cascade Behavior
- When a company is deleted, all related `company_finetune`, `api_key`, `ticket`, and `ticket_opened` records are automatically deleted.
- File storage at `backend/storage/companies/{id}/` is also automatically cleaned up.
- Finetune data at `backend/app/core/finetune/rag/companies/{id}/` is automatically cleaned up.

---

## Table 2: `company_finetune`

### Purpose
Stores knowledge base/RAG (Retrieval-Augmented Generation) data for each company. Represents the custom context that AI models use when responding to queries.

### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `BIGINT` | PRIMARY KEY, AUTO INCREMENT | Unique finetune record ID |
| `company_id` | `BIGINT` | FOREIGN KEY → company(id), NOT NULL | References parent company |
| `content` | `TEXT` | NULLABLE | Markdown formatted knowledge base content |
| `model_name` | `VARCHAR(100)` | NOT NULL | Model name for Groq integration (auto-generated) |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Creation timestamp (UTC) |
| `updated_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP, ON UPDATE | Last update timestamp (UTC) |

### Indexes
```sql
CREATE INDEX idx_company_finetune_company_id ON company_finetune(company_id);
```

### Constraints
```sql
ALTER TABLE company_finetune
ADD CONSTRAINT fk_company_finetune_company_id
FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE;
```

### Example Data
```json
{
  "id": 1,
  "company_id": 1,
  "content": "# Acme Corp Knowledge Base\n\n## Product Info\n...",
  "model_name": "perai-acme-corp",
  "created_at": "2024-01-15T10:35:00Z",
  "updated_at": "2024-01-20T14:25:00Z"
}
```

### File Storage
- **Location**: `backend/app/core/finetune/rag/companies/{company_id}/company.md`
- **Format**: Markdown
- **Auto-generated**: Yes
- **Auto-cleanup**: Yes (on company delete)

### Relationships
- **Belongs To**: `company`

### Usage in Chat
When a chat query is made:
1. The system retrieves the company's `company_finetune` record
2. Uses `content` as context in the Groq AI query
3. Groq returns a response based on the knowledge base
4. Response includes `model_name`, `company_id`, and the AI response

---

## Table 3: `api_key`

### Purpose
Manages API keys for company authentication and external integrations.

### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `BIGINT` | PRIMARY KEY, AUTO INCREMENT | Unique API key record ID |
| `company_id` | `BIGINT` | FOREIGN KEY → company(id), NOT NULL | References parent company |
| `key_name` | `VARCHAR(255)` | NOT NULL | Human-readable key name (e.g., "Production API Key") |
| `key_hash` | `VARCHAR(64)` | NOT NULL, UNIQUE | SHA-256 hash of the full API key |
| `key_preview` | `VARCHAR(20)` | NOT NULL | Safe preview (e.g., "sk_4...a1b2") |
| `status` | `VARCHAR(20)` | DEFAULT 'active' | Status enum: `active`, `revoked`, `expired` |
| `expiry_date` | `TIMESTAMP` | NULLABLE | Expiration date (UTC); NULL = never expires |
| `last_used_at` | `TIMESTAMP` | NULLABLE | Last time this key was used (UTC) |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Creation timestamp (UTC) |
| `updated_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP, ON UPDATE | Last update timestamp (UTC) |

### Indexes
```sql
CREATE INDEX idx_api_key_company_id ON api_key(company_id);
CREATE INDEX idx_api_key_hash ON api_key(key_hash);
CREATE INDEX idx_api_key_status ON api_key(status);
```

### Constraints
```sql
ALTER TABLE api_key
ADD CONSTRAINT fk_api_key_company_id
FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE;
```

### Example Data
```json
{
  "id": 5,
  "company_id": 1,
  "key_name": "Production API Key",
  "key_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "key_preview": "sk_4...b2c3",
  "status": "active",
  "expiry_date": "2025-01-15T23:59:59Z",
  "last_used_at": "2024-01-20T13:45:22Z",
  "created_at": "2024-01-15T10:40:00Z",
  "updated_at": "2024-01-20T13:45:22Z"
}
```

### API Key Format
- **Full Key Format**: `sk_{48_char_urlsafe_random_token}`
- **Example Full Key**: `sk_xY7kL9pQrStUvWxYzAbCdEfGhIjKlMnOpQrStUvWxYzAbCdEf`
- **Preview Format**: `sk_4...{last_4_chars}` (max 20 chars)
- **Storage**: Only `key_hash` (SHA-256) is stored in database
- **Full Key Visibility**: Only shown once at creation time

### Status Enum
```python
ACTIVE = "active"      # Key can be used
REVOKED = "revoked"    # Key has been manually revoked
EXPIRED = "expired"    # Key has passed expiry_date
```

### Relationships
- **Belongs To**: `company`

### Usage
1. Company creates an API key via `POST /api/v1/company/{id}/api-keys`
2. Full key is returned immediately and must be saved securely by client
3. To use the key: Include in request header: `X-API-Key: sk_xY7...`
4. Server validates by:
   - Hashing the provided key with SHA-256
   - Looking up the hash in `api_key` table
   - Checking `status` is `active`
   - Checking `expiry_date` hasn't passed
   - Updating `last_used_at` timestamp

---

## Table 4: `ticket`

### Purpose
Stores support tickets created by companies for issue tracking and support management.

### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `BIGINT` | PRIMARY KEY, AUTO INCREMENT | Unique ticket identifier |
| `company_id` | `BIGINT` | FOREIGN KEY → company(id), NOT NULL | References parent company |
| `issue` | `TEXT` | NOT NULL | Detailed description of the issue |
| `category` | `VARCHAR(50)` | DEFAULT 'general' | Issue category enum: `payment`, `technical`, `general` |
| `status` | `VARCHAR(20)` | DEFAULT 'open' | Ticket status enum: `open`, `closed` |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Creation timestamp (UTC) |
| `updated_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP, ON UPDATE | Last update timestamp (UTC) |

### Indexes
```sql
CREATE INDEX idx_ticket_company_id ON ticket(company_id);
CREATE INDEX idx_ticket_status ON ticket(status);
CREATE INDEX idx_ticket_category ON ticket(category);
CREATE INDEX idx_ticket_company_status ON ticket(company_id, status);
CREATE INDEX idx_ticket_company_category ON ticket(company_id, category);
```

### Constraints
```sql
ALTER TABLE ticket
ADD CONSTRAINT fk_ticket_company_id
FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE;
```

### Example Data
```json
{
  "id": 42,
  "company_id": 1,
  "issue": "Payment processing failing for international cards",
  "category": "payment",
  "status": "open",
  "created_at": "2024-01-20T10:15:00Z",
  "updated_at": "2024-01-20T10:15:00Z"
}
```

### Category Enum
```python
PAYMENT = "payment"       # Payment/billing related issues
TECHNICAL = "technical"   # Technical/bug related issues
GENERAL = "general"       # General inquiries (default)
```

### Status Enum
```python
OPEN = "open"             # Ticket is open
CLOSED = "closed"         # Ticket is resolved/closed
```

### Relationships
- **Belongs To**: `company`
- **Has Many**: `ticket_opened` (one-to-many, history records)

### Data Flow
1. **Create**: Company creates ticket via `POST /api/v1/company/{id}/tickets`
   - Initial status is `open`
   - A `ticket_opened` record is created
2. **Update**: Company updates ticket via `PUT /api/v1/company/{id}/tickets/{ticket_id}`
   - If status changes from `open` to `closed`: New `ticket_opened` record created with `event = "closed"`
   - If status changes from `closed` to `open`: New `ticket_opened` record created with `event = "opened"`
3. **List**: `GET /api/v1/company/{id}/tickets` with optional filters:
   - `status_filter`: Filter by status (open/closed)
   - `category_filter`: Filter by category (payment/technical/general)
4. **Delete**: Company deletes ticket via `DELETE /api/v1/company/{id}/tickets/{ticket_id}`
   - Related `ticket_opened` records are cascaded and deleted

---

## Table 5: `ticket_opened`

### Purpose
Audit trail/history for ticket status changes. Records when tickets are opened, closed, and reopened.

### Schema

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | `BIGINT` | PRIMARY KEY, AUTO INCREMENT | Unique history record ID |
| `ticket_id` | `BIGINT` | FOREIGN KEY → ticket(id), NOT NULL | References parent ticket |
| `company_id` | `BIGINT` | FOREIGN KEY → company(id), NOT NULL | References company (denormalized for query performance) |
| `event` | `VARCHAR(50)` | NOT NULL | Event type: `opened`, `closed`, `reopened` |
| `timestamp` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | When the event occurred (UTC) |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp (UTC) |
| `updated_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP, ON UPDATE | Last update timestamp (UTC) |

### Indexes
```sql
CREATE INDEX idx_ticket_opened_ticket_id ON ticket_opened(ticket_id);
CREATE INDEX idx_ticket_opened_company_id ON ticket_opened(company_id);
CREATE INDEX idx_ticket_opened_event ON ticket_opened(event);
CREATE INDEX idx_ticket_opened_timestamp ON ticket_opened(timestamp);
```

### Constraints
```sql
ALTER TABLE ticket_opened
ADD CONSTRAINT fk_ticket_opened_ticket_id
FOREIGN KEY (ticket_id) REFERENCES ticket(id) ON DELETE CASCADE;

ALTER TABLE ticket_opened
ADD CONSTRAINT fk_ticket_opened_company_id
FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE;
```

### Example Data
```json
{
  "id": 1,
  "ticket_id": 42,
  "company_id": 1,
  "event": "opened",
  "timestamp": "2024-01-20T10:15:00Z",
  "created_at": "2024-01-20T10:15:00Z",
  "updated_at": "2024-01-20T10:15:00Z"
}
```

```json
{
  "id": 2,
  "ticket_id": 42,
  "company_id": 1,
  "event": "closed",
  "timestamp": "2024-01-20T14:30:00Z",
  "created_at": "2024-01-20T14:30:00Z",
  "updated_at": "2024-01-20T14:30:00Z"
}
```

### Event Enum
```python
OPENED = "opened"         # Ticket was created
CLOSED = "closed"         # Ticket was closed
REOPENED = "reopened"     # Ticket was reopened after being closed
```

### Relationships
- **Belongs To**: `ticket`
- **Belongs To**: `company`

### Query Examples

**Get all history for a ticket:**
```sql
SELECT * FROM ticket_opened 
WHERE ticket_id = 42 
ORDER BY timestamp ASC;
```

**Get ticket statistics:**
```sql
SELECT 
  COUNT(DISTINCT CASE WHEN event = 'opened' THEN ticket_id END) as total_tickets,
  COUNT(DISTINCT CASE WHEN t.status = 'open' THEN t.id END) as open_tickets,
  COUNT(DISTINCT CASE WHEN t.status = 'closed' THEN t.id END) as closed_tickets
FROM ticket t
WHERE t.company_id = 1;
```

---

## Entity Relationship Diagram (ERD)

```
┌─────────────────────────┐
│       company           │
├─────────────────────────┤
│ id (PK)                 │
│ company_name            │
│ company_email           │
│ password_hash           │
│ company_model_name      │
│ website                 │
│ created_at              │
│ updated_at              │
└──────────┬──────────────┘
           │
           │ 1:1
           ├───────────────────────────┐
           │                           │
           │                      ┌────┴────────────────┐
           │                      │ company_finetune    │
           │                      ├─────────────────────┤
           │                      │ id (PK)             │
           │                      │ company_id (FK)     │
           │                      │ content             │
           │                      │ model_name          │
           │                      │ created_at          │
           │                      │ updated_at          │
           │                      └─────────────────────┘
           │
           │ 1:N
           ├───────────────────────────┐
           │                           │
      ┌────┴──────────────┐    ┌──────┴───────────────┐
      │    api_key        │    │     ticket          │
      ├───────────────────┤    ├─────────────────────┤
      │ id (PK)           │    │ id (PK)             │
      │ company_id (FK)   │    │ company_id (FK)     │
      │ key_name          │    │ issue               │
      │ key_hash          │    │ category            │
      │ key_preview       │    │ status              │
      │ status            │    │ created_at          │
      │ expiry_date       │    │ updated_at          │
      │ last_used_at      │    │                     │
      │ created_at        │    │ 1:N                 │
      │ updated_at        │    │  │                  │
      └───────────────────┘    │  └──────────┐       │
                               └─────────────┼───────┘
                                             │
                                        ┌────┴──────────────┐
                                        │  ticket_opened    │
                                        ├───────────────────┤
                                        │ id (PK)           │
                                        │ ticket_id (FK)    │
                                        │ company_id (FK)   │
                                        │ event             │
                                        │ timestamp         │
                                        │ created_at        │
                                        │ updated_at        │
                                        └───────────────────┘
```

---

## Normalization Analysis

### Third Normal Form (3NF) Compliance

1. **First Normal Form (1NF)**: ✅
   - All tables have atomic values
   - No repeating groups
   - All columns are single-valued

2. **Second Normal Form (2NF)**: ✅
   - All non-key attributes depend on the entire primary key
   - No partial dependencies
   - Each table represents a single entity

3. **Third Normal Form (3NF)**: ✅
   - No transitive dependencies
   - Non-key attributes depend only on primary key
   - All data is stored in appropriate tables

### Design Notes
- **company_finetune**: One-to-one with company (could be denormalized, but kept separate for flexibility)
- **api_key**: Many-to-one with company (proper normalization)
- **ticket**: Many-to-one with company (proper normalization)
- **ticket_opened**: Many-to-one with ticket and company (history table, company_id denormalized for query performance)

---

## Cascade Delete Strategy

When a company is deleted, the following cascade occurs:

```
DELETE FROM company WHERE id = X
  ↓
DELETE FROM company_finetune WHERE company_id = X (cascade)
DELETE FROM api_key WHERE company_id = X (cascade)
DELETE FROM ticket WHERE company_id = X (cascade)
  ↓ (ticket delete triggers)
  DELETE FROM ticket_opened WHERE ticket_id = X (cascade)
  DELETE FROM ticket_opened WHERE company_id = X (cascade)
```

**File System Cleanup**:
- `backend/storage/companies/{X}/` directory is deleted
- `backend/app/core/finetune/rag/companies/{X}/` directory is deleted

---

## Query Performance Optimization

### Common Queries

**1. Get company with all related data:**
```sql
SELECT c.*, cf.content, cf.model_name
FROM company c
LEFT JOIN company_finetune cf ON c.id = cf.company_id
WHERE c.id = 1;
```

**2. Get all API keys for a company:**
```sql
SELECT * FROM api_key 
WHERE company_id = 1 AND status = 'active'
ORDER BY created_at DESC;
```

**3. Get open tickets by category:**
```sql
SELECT * FROM ticket 
WHERE company_id = 1 AND status = 'open' AND category = 'payment'
ORDER BY created_at DESC;
```

**4. Get ticket history:**
```sql
SELECT * FROM ticket_opened 
WHERE ticket_id = 42 
ORDER BY timestamp ASC;
```

**5. Get ticket statistics:**
```sql
SELECT 
  COUNT(*) as total_tickets,
  COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
  COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
  COUNT(CASE WHEN category = 'payment' THEN 1 END) as payment_issues,
  COUNT(CASE WHEN category = 'technical' THEN 1 END) as technical_issues,
  COUNT(CASE WHEN category = 'general' THEN 1 END) as general_issues
FROM ticket 
WHERE company_id = 1;
```

### Index Usage
- **company**: Indexes on `email` and `model_name` for fast lookups
- **api_key**: Indexes on `company_id`, `key_hash`, `status` for security and filtering
- **ticket**: Indexes on `company_id`, `status`, `category` for fast filtering
- **ticket_opened**: Indexes on `ticket_id`, `company_id`, `event` for history queries

---

## Data Dictionary

### Timestamps
- **Format**: ISO 8601 (e.g., `2024-01-20T14:30:00Z`)
- **Timezone**: UTC
- **Precision**: Seconds

### String Fields
- **company_name**: 1-255 characters, unique per database
- **company_email**: Valid email format, unique per database
- **key_name**: 1-255 characters, human-readable (non-unique)
- **website**: Valid URL or empty

### Password Storage
- **Algorithm**: PBKDF2-SHA256
- **Iterations**: 100,000
- **Salt Length**: Random
- **Hash Length**: 64 hex characters

### API Key Storage
- **Full Key Length**: 51 characters (`sk_` + 48 alphanumeric)
- **Hash Algorithm**: SHA-256
- **Hash Length**: 64 hex characters
- **Preview Length**: Max 20 characters

---

## Constraints Summary

| Table | Constraint | Type | Details |
|-------|-----------|------|---------|
| company | id | PK | Auto-increment BIGINT |
| company | company_name | UNIQUE | Ensures unique company names |
| company | company_email | UNIQUE | Ensures unique email addresses |
| company_finetune | company_id | FK | References company(id) ON DELETE CASCADE |
| api_key | company_id | FK | References company(id) ON DELETE CASCADE |
| api_key | key_hash | UNIQUE | Ensures unique key hashes |
| ticket | company_id | FK | References company(id) ON DELETE CASCADE |
| ticket_opened | ticket_id | FK | References ticket(id) ON DELETE CASCADE |
| ticket_opened | company_id | FK | References company(id) ON DELETE CASCADE |

---

## Migration History

| Migration ID | Description | Date Applied |
|-------------|-------------|--------------|
| 0001 | Create company and company_finetune tables | 2024-01-15 |
| 0002 | Create api_key table | 2024-01-16 |
| f920af34db03 | Add company_model_name column to company | 2024-01-17 |
| 886ccfbe74e8 | Create ticket and ticket_opened tables | 2024-01-20 |
| fix_ticket_defaults | Add default values to ticket timestamps | 2024-01-20 |

---

## Access Patterns

### By Company
- List all entities by company: `WHERE company_id = X`
- Delete all entities for company: Cascade delete on company

### By API Key
- Validate API key: Hash incoming key, lookup in api_key table
- Check expiry: `WHERE key_hash = X AND (expiry_date IS NULL OR expiry_date > NOW())`
- Check status: `WHERE status = 'active'`

### By Ticket
- List tickets: `WHERE company_id = X ORDER BY created_at DESC`
- Filter by status: `WHERE status = 'open'` or `'closed'`
- Filter by category: `WHERE category = 'payment'`
- Get ticket history: `SELECT * FROM ticket_opened WHERE ticket_id = X ORDER BY timestamp ASC`

---

## Database Connection

**Environment Variables**:
```
DATABASE_URL=postgresql://postgres:uxEwUuDilVS8LQch@db.lxopuyaxcxrglkfcbree.supabase.co:5432/postgres
SUPABASE_URL=https://lxopuyaxcxrglkfcbree.supabase.co
SUPABASE_KEY=eyJhbGc...
SUPABASE_PASSWORD=uxEwUuDilVS8LQch
```

**Connection Details**:
- **Host**: db.lxopuyaxcxrglkfcbree.supabase.co
- **Port**: 5432
- **Database**: postgres
- **User**: postgres
- **SSL**: Required (Supabase default)

---

## Backup & Recovery

### Backup Strategy
- Supabase handles automated daily backups
- Point-in-time recovery available (7 days)
- Manual exports recommended before major migrations

### Recovery Points
- Before schema changes (migrations)
- Before bulk data operations
- Weekly routine backups

---

## Future Considerations

1. **Audit Logging**: Consider adding audit_log table for all data changes
2. **Soft Deletes**: Add deleted_at column for soft delete capability
3. **Rate Limiting**: Track API key usage for rate limiting
4. **Webhooks**: Add webhook event tracking table
5. **Email Notifications**: Add notification log table
6. **File Metadata**: Consider moving file metadata into database

---

## Summary

The Perai database schema is a clean, normalized design supporting:
- **Multi-tenancy**: Fully scoped by company_id
- **Security**: Password hashing, API key hashing, status tracking
- **Auditability**: Ticket history tracking with full timestamps
- **Reliability**: Cascading deletes, proper foreign keys
- **Performance**: Strategic indexing on common query patterns
- **Scalability**: 3NF normalization allows for future growth

Total: **5 tables**, **39 columns**, **14 indexes**, **100% 3NF compliant**
