# Perai Backend - Application Flow Documentation

## System Overview Flowchart

```
┌─────────────────────────────────────────────────────────────────┐
│                    PERAI BACKEND SYSTEM                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────────┐  │
│  │  Frontend    │  │  External    │  │  AI Integration     │  │
│  │  (React)     │  │  Services    │  │  (Groq API)         │  │
│  └──────┬───────┘  └──────┬───────┘  └────────┬────────────┘  │
│         │                 │                    │                │
│         └─────────────────┼────────────────────┘                │
│                           │                                     │
│                    ┌──────▼──────┐                              │
│                    │  FastAPI    │                              │
│                    │  Server     │                              │
│                    │ :8000       │                              │
│                    └──────┬──────┘                              │
│                           │                                     │
│         ┌─────────────────┼─────────────────┐                  │
│         │                 │                 │                  │
│    ┌────▼────┐      ┌─────▼────┐    ┌────┬┴────┐              │
│    │   Auth  │      │  Company │    │  Routers │              │
│    │ Service │      │ Services │    │          │              │
│    └────┬────┘      └─────┬────┘    ├──────────┤              │
│         │                 │         │• APIKey  │              │
│         │                 │         │• Chat    │              │
│    ┌────▼────────────────┬┴────┐    │• Ticket  │              │
│    │                     │     │    │• Files   │              │
│    │   SQLAlchemy ORM    │     │    └──────────┘              │
│    │                     │     │                              │
│    └──────────┬──────────┴─┬───┘                              │
│               │            │                                  │
│         ┌─────▼────────────▼────┐                             │
│         │  Alembic Migrations   │                             │
│         └────────┬──────────────┘                             │
│                  │                                            │
│         ┌────────▼──────────────┐                             │
│         │  PostgreSQL 17.6      │                             │
│         │  (Supabase)           │                             │
│         │                       │                             │
│         │ • company             │                             │
│         │ • company_finetune    │                             │
│         │ • api_key             │                             │
│         │ • ticket              │                             │
│         │ • ticket_opened       │                             │
│         └───────────────────────┘                             │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              File Storage                                │  │
│  │  /backend/storage/companies/{id}/logo                   │  │
│  │  /backend/storage/companies/{id}/content                │  │
│  │  /backend/app/core/finetune/rag/companies/{id}/         │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Authentication Flow

```
┌────────────────────────────────────────────────────────────────────┐
│                    AUTHENTICATION FLOW                              │
└────────────────────────────────────────────────────────────────────┘

╔═══════════════════════════════════════════════════════════════════╗
║                    REGISTRATION FLOW                               ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client                 Frontend                Backend              Database
     │                          │                      │                    │
     │  1. Enter Details        │                      │                    │
     │──────────────────────────►                      │                    │
     │                          │                      │                    │
     │                    2. POST /auth/register       │                    │
     │                          │─────────────────────►│                    │
     │                          │                      │                    │
     │                          │            3. Hash Password               │
     │                          │            (PBKDF2-SHA256)              │
     │                          │                      │                    │
     │                          │            4. Insert Company             │
     │                          │                      ├───────────────────►│
     │                          │                      │◄───────────────────┤
     │                          │            5. Generate Model Name         │
     │                          │            (perai-{name_lowercase})      │
     │                          │                      │                    │
     │                          │            6. Return Company Object       │
     │                          │◄─────────────────────┤                    │
     │                 7. Display Success              │                    │
     │◄──────────────────────────                      │                    │
     │                          │                      │                    │
     │ 8. Save Company ID & Email                      │                    │
     │                          │                      │                    │

╔═══════════════════════════════════════════════════════════════════╗
║                     LOGIN FLOW                                    ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client                 Frontend                Backend              Database
     │                          │                      │                    │
     │  1. Enter Email & Pass    │                      │                    │
     │──────────────────────────►                      │                    │
     │                          │                      │                    │
     │                    2. POST /auth/login          │                    │
     │                          │─────────────────────►│                    │
     │                          │                      │                    │
     │                          │            3. Find Company by Email      │
     │                          │                      ├───────────────────►│
     │                          │                      │◄───────────────────┤
     │                          │                      │                    │
     │                          │            4. Verify Password             │
     │                          │            (PBKDF2-SHA256 check)         │
     │                          │                      │                    │
     │                 ┌─────────┼──────────┐          │                    │
     │                 │         │          │          │                    │
     │          ✓ Match    │    ✗ No Match          │                    │
     │                 │         │          │          │                    │
     │         5. Return Company   5. Return Error  │                    │
     │         Data + Company ID   (401)             │                    │
     │◄──────────┤              │          │          │                    │
     │           └──────────────────────────┘          │                    │
     │                          │                      │                    │
     │ 6. Save Company ID, Email, & Store API Key     │                    │
     │                          │                      │                    │

╔═══════════════════════════════════════════════════════════════════╗
║                  VERIFICATION FLOW                                 ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client                 Frontend                Backend              Database
     │                          │                      │                    │
     │                    1. GET /auth/verify/{id}     │                    │
     │                          │─────────────────────►│                    │
     │                          │                      │                    │
     │                          │            2. Find Company by ID         │
     │                          │                      ├───────────────────►│
     │                          │                      │◄───────────────────┤
     │                          │                      │                    │
     │                          │            3. Return Company Data         │
     │                          │◄─────────────────────┤                    │
     │                 4. Display Company Info         │                    │
     │◄──────────────────────────                      │                    │
```

---

## API Key Management Flow

```
┌────────────────────────────────────────────────────────────────────┐
│                   API KEY MANAGEMENT FLOW                           │
└────────────────────────────────────────────────────────────────────┘

╔═══════════════════════════════════════════════════════════════════╗
║                 KEY GENERATION FLOW                                ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client                 Frontend                Backend              Database
     │                          │                      │                    │
     │  1. Click "Generate Key"  │                      │                    │
     │──────────────────────────►                      │                    │
     │                          │                      │                    │
     │                   2. POST /api-keys             │                    │
     │                   (with key_name)               │                    │
     │                          │─────────────────────►│                    │
     │                          │                      │                    │
     │                          │      3. Generate Random Token             │
     │                          │      (48 chars, urlsafe)                  │
     │                          │      sk_xY7kL9p...                        │
     │                          │                      │                    │
     │                          │      4. Hash Token (SHA-256)              │
     │                          │      e3b0c44298fc1c14...                 │
     │                          │                      │                    │
     │                          │      5. Create Preview                    │
     │                          │      sk_4...b2c3                          │
     │                          │                      │                    │
     │                          │      6. Insert into api_key               │
     │                          │      (hash & preview stored)              │
     │                          │                      ├───────────────────►│
     │                          │                      │◄───────────────────┤
     │                          │                      │                    │
     │         7. Return Full Key (ONCE!)              │                    │
     │         sk_xY7kL9p...                           │                    │
     │◄──────────────────────────                      │                    │
     │                          │                      │                    │
     │ 8. SAVE KEY SECURELY!                           │                    │
     │ (Won't be shown again)                          │                    │
     │                          │                      │                    │

╔═══════════════════════════════════════════════════════════════════╗
║                 KEY VALIDATION FLOW                                ║
╚═══════════════════════════════════════════════════════════════════╝

Client                 Backend                       Database
  │                      │                                │
  │ 1. Make Request      │                                │
  │ Header: X-API-Key: sk_xY7...                          │
  │─────────────────────►│                                │
  │                      │                                │
  │                      │ 2. Extract Key from Header     │
  │                      │ sk_xY7kL9p...                  │
  │                      │                                │
  │                      │ 3. Hash the Key (SHA-256)      │
  │                      │ e3b0c44298fc1c14...            │
  │                      │                                │
  │                      │ 4. Lookup Hash in api_key      │
  │                      ├───────────────────────────────►│
  │                      │◄───────────────────────────────┤
  │                      │                                │
  │              ┌──────┬┴──────┐                         │
  │              │      │       │                         │
  │            Hash  Status  Expiry                       │
  │            Found?  OK?   Valid?                       │
  │              │      │       │                         │
  │            All Yes? │       │                         │
  │              │      │       │                         │
  │         ┌────┴──────┴───────┘                         │
  │         │                                             │
  │    ✓ ALL OK              ✗ FAILED                    │
  │    5. Update last_used_at                 5. Return 401         │
  │       (timestamp)                          Unauthorized         │
  │       6. Process Request                                        │
  │       7. Return 200 OK                                          │
  │◄──────────────────────────────────────                         │
  │                      │                                │
```

---

## Chat Flow with Finetune Context

```
┌────────────────────────────────────────────────────────────────────┐
│               CHAT QUERY WITH FINETUNE FLOW                         │
└────────────────────────────────────────────────────────────────────┘

User/Client         Frontend            Backend                Database/Storage
     │                  │                  │                        │
     │  1. Type Message  │                  │                        │
     │──────────────────►                  │                        │
     │                  │                  │                        │
     │           2. POST /chat/query        │                        │
     │           {message, api_key}         │                        │
     │                  │─────────────────►│                        │
     │                  │                  │                        │
     │                  │       3. Validate API Key                 │
     │                  │       (SHA-256 hash lookup)               │
     │                  │                  ├───────────────────────►│
     │                  │                  │◄───────────────────────┤
     │                  │                  │                        │
     │                  │       4. Get Company Data                 │
     │                  │       & company_id                        │
     │                  │                  ├───────────────────────►│
     │                  │                  │◄───────────────────────┤
     │                  │                  │                        │
     │                  │       5. Get Finetune Content             │
     │                  │       (company_finetune.content)          │
     │                  │                  ├───────────────────────►│
     │                  │                  │◄───────────────────────┤
     │                  │                  │                        │
     │                  │       6. Retrieve from Disk               │
     │                  │       /backend/app/core/finetune/         │
     │                  │        rag/companies/{id}/company.md      │
     │                  │                  ├───────────────────────►│
     │                  │                  │ (file system)          │
     │                  │                  │◄───────────────────────┤
     │                  │                  │                        │
     │                  │       7. Build Prompt with Context        │
     │                  │       "Here is company knowledge:         │
     │                  │        {finetune_content}                 │
     │                  │        User asks: {message}"              │
     │                  │                  │                        │
     │                  │       8. Call Groq AI API                 │
     │                  │       (model: groq-model from env)        │
     │                  │                  │                        │
     │                  │                  ├──────────► Groq API    │
     │                  │                  │◄──────────             │
     │                  │                  │                        │
     │                  │       9. Get Response from Groq           │
     │                  │       "Answer: ..."                       │
     │                  │                  │                        │
     │         10. Return Chat Response      │                      │
     │         {                            │                      │
     │           model_name,                │                      │
     │           company_id,                │                      │
     │           response                   │                      │
     │         }                            │                      │
     │◄─────────────────────────────────────┤                      │
     │                  │                  │                      │
     │ 11. Display Response                 │                      │
     │                  │                  │                      │
```

---

## Ticket Management Flow

```
┌────────────────────────────────────────────────────────────────────┐
│               TICKET MANAGEMENT FLOW                                │
└────────────────────────────────────────────────────────────────────┘

╔═══════════════════════════════════════════════════════════════════╗
║              CREATE TICKET FLOW                                    ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client         Frontend            Backend             Database
     │                  │                  │                    │
     │ 1. Fill Ticket    │                  │                    │
     │    Form           │                  │                    │
     │──────────────────►│                  │                    │
     │                  │                  │                    │
     │           2. POST /tickets           │                    │
     │           {issue, category, api_key} │                    │
     │                  │─────────────────►│                    │
     │                  │                  │                    │
     │                  │      3. Validate API Key               │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │                  │      4. Insert Ticket                 │
     │                  │      (status='open')                  │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │                  │      5. Insert ticket_opened Record   │
     │                  │      (event='opened')                 │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │         6. Return Ticket Data        │                    │
     │◄──────────────────────────────────────                    │
     │                  │                  │                    │
     │ 7. Show Success & ID                 │                    │
     │                  │                  │                    │

╔═══════════════════════════════════════════════════════════════════╗
║              UPDATE TICKET FLOW                                    ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client         Frontend            Backend             Database
     │                  │                  │                    │
     │ 1. Update Ticket  │                  │                    │
     │    (close it)     │                  │                    │
     │──────────────────►│                  │                    │
     │                  │                  │                    │
     │    2. PUT /tickets/{id}              │                    │
     │    {status:'closed', api_key}        │                    │
     │                  │─────────────────►│                    │
     │                  │                  │                    │
     │                  │      3. Validate API Key               │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │                  │      4. Check Status Change            │
     │                  │      open → closed?                    │
     │                  │                  │                    │
     │                  │      5. Update Ticket                 │
     │                  │      (status='closed')                │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │                  │      6. Insert ticket_opened Record   │
     │                  │      (event='closed')                 │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │         7. Return Updated Ticket     │                    │
     │◄──────────────────────────────────────                    │
     │                  │                  │                    │

╔═══════════════════════════════════════════════════════════════════╗
║              LIST & FILTER TICKETS FLOW                            ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client         Frontend            Backend             Database
     │                  │                  │                    │
     │ 1. Click "Show    │                  │                    │
     │    Open Tickets"  │                  │                    │
     │──────────────────►│                  │                    │
     │                  │                  │                    │
     │    2. GET /tickets?status_filter=open                    │
     │                  │─────────────────►│                    │
     │                  │                  │                    │
     │                  │      3. Validate API Key               │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │                  │      4. Query Tickets WHERE            │
     │                  │      status = 'open' AND               │
     │                  │      company_id = X                    │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │         5. Return Array of Tickets   │                    │
     │◄──────────────────────────────────────                    │
     │                  │                  │                    │
     │ 6. Display Filtered List             │                    │
     │                  │                  │                    │

╔═══════════════════════════════════════════════════════════════════╗
║              GET STATISTICS FLOW                                   ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client         Frontend            Backend             Database
     │                  │                  │                    │
     │ 1. Click "Stats"  │                  │                    │
     │──────────────────►                  │                    │
     │                  │                  │                    │
     │    2. GET /tickets-stats             │                    │
     │                  │─────────────────►│                    │
     │                  │                  │                    │
     │                  │      3. Validate API Key               │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │                  │      4. Query Statistics               │
     │                  │      COUNT(*),                         │
     │                  │      COUNT OPEN,                       │
     │                  │      COUNT by CATEGORY                 │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │         5. Return Stats              │                    │
     │         {                            │                    │
     │           total: 42,                 │                    │
     │           open: 15,                  │                    │
     │           closed: 27,                │                    │
     │           by_category: {...}        │                    │
     │         }                            │                    │
     │◄──────────────────────────────────────                    │
     │                  │                  │                    │
     │ 6. Display Dashboard                 │                    │
     │                  │                  │                    │

╔═══════════════════════════════════════════════════════════════════╗
║              GET TICKET HISTORY FLOW                               ║
╚═══════════════════════════════════════════════════════════════════╝

User/Client         Frontend            Backend             Database
     │                  │                  │                    │
     │ 1. Click "View    │                  │                    │
     │    History"       │                  │                    │
     │──────────────────►│                  │                    │
     │                  │                  │                    │
     │ 2. GET /tickets/{id}/history         │                    │
     │                  │─────────────────►│                    │
     │                  │                  │                    │
     │                  │      3. Validate API Key               │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │                  │      4. Query ticket_opened WHERE      │
     │                  │      ticket_id = X ORDER BY timestamp  │
     │                  │                  ├───────────────────►│
     │                  │                  │◄───────────────────┤
     │                  │                  │                    │
     │         5. Return History Array      │                    │
     │         [                            │                    │
     │           {event: 'opened', ...},    │                    │
     │           {event: 'closed', ...},    │                    │
     │           ...                        │                    │
     │         ]                            │                    │
     │◄──────────────────────────────────────                    │
     │                  │                  │                    │
     │ 6. Display Timeline                  │                    │
     │                  │                  │                    │
```

---

## Company CRUD Flow

```
┌────────────────────────────────────────────────────────────────────┐
│                 COMPANY CRUD FLOW                                   │
└────────────────────────────────────────────────────────────────────┘

CREATE (Registration already shown above)

READ - Get Company Details
┌─────────────────────────────────────────┐
│ GET /api/v1/company/{company_id}        │
│ Header: X-API-Key: sk_...               │
└──────────────┬──────────────────────────┘
               │
        1. Validate API Key
        2. Verify company_id matches API key owner
        3. Query database
        4. Return company data
        5. (200) Return: {id, name, email, website, ...}

UPDATE - Modify Company
┌─────────────────────────────────────────┐
│ PUT /api/v1/company/{company_id}        │
│ Header: X-API-Key: sk_...               │
│ Body: {website, company_name, ...}      │
└──────────────┬──────────────────────────┘
               │
        1. Validate API Key
        2. Verify permissions
        3. Update database record
        4. Return updated company
        5. (200) Return: Updated Company

DELETE - Remove Company
┌─────────────────────────────────────────┐
│ DELETE /api/v1/company/{company_id}     │
│ Header: X-API-Key: sk_...               │
└──────────────┬──────────────────────────┘
               │
        1. Validate API Key
        2. Verify permissions
        3. Delete company (CASCADE):
           • company_finetune → deleted
           • api_key → deleted
           • ticket → deleted
           • ticket_opened → deleted (via cascade)
        4. Delete storage:
           • /storage/companies/{id}/
           • /app/core/finetune/rag/companies/{id}/
        5. (204) No Content
```

---

## File Management Flow

```
┌────────────────────────────────────────────────────────────────────┐
│                FILE MANAGEMENT FLOW                                 │
└────────────────────────────────────────────────────────────────────┘

LOGO UPLOAD
┌──────────────────────────────────────────┐
│ POST /api/v1/company/{id}/files/logo     │
│ Form-Data:                               │
│   • file: image.png (PNG/JPEG/GIF/WebP)  │
│   • X-API-Key: sk_...                    │
└──────────────┬───────────────────────────┘
               │
        1. Validate API Key
        2. Validate MIME type (PNG/JPEG/GIF/WebP)
        3. Sanitize filename
        4. Save to /storage/companies/{id}/logo/
        5. Store filename in database
        6. (200) Return: {filename, url, size, uploaded_at}

LOGO DOWNLOAD
┌──────────────────────────────────────────┐
│ GET /api/v1/company/{id}/files/logo      │
│ Header: X-API-Key: sk_...                │
└──────────────┬───────────────────────────┘
               │
        1. Validate API Key
        2. Check /storage/companies/{id}/logo/
        3. Return file to client
        4. (200) File content or (404) Not found

CONTENT FILE UPLOAD
┌──────────────────────────────────────────────────┐
│ POST /api/v1/company/{id}/files/content          │
│ Form-Data:                                       │
│   • file: document.pdf (PDF/DOCX/TXT/ZIP)        │
│   • X-API-Key: sk_...                            │
└──────────────┬───────────────────────────────────┘
               │
        1. Validate API Key
        2. Validate MIME type (PDF/DOCX/TXT/ZIP)
        3. Sanitize filename
        4. Save to /storage/companies/{id}/content/
        5. Store metadata in database
        6. (201) Return: {filename, url, size, uploaded_at}

CONTENT FILE DOWNLOAD
┌──────────────────────────────────────────────────┐
│ GET /api/v1/company/{id}/files/content/{filename}│
│ Header: X-API-Key: sk_...                        │
└──────────────┬───────────────────────────────────┘
               │
        1. Validate API Key
        2. Check /storage/companies/{id}/content/
        3. Return file to client
        4. (200) File content or (404) Not found

LIST FILES
┌──────────────────────────────────────────┐
│ GET /api/v1/company/{id}/files           │
│ Header: X-API-Key: sk_...                │
└──────────────┬───────────────────────────┘
               │
        1. Validate API Key
        2. List all files in /storage/companies/{id}/
        3. Return array with metadata
        4. (200) Return: [{filename, size, type, uploaded_at}, ...]

DELETE FILE
┌──────────────────────────────────────────────────┐
│ DELETE /api/v1/company/{id}/files/{type}/{filename}│
│ Header: X-API-Key: sk_...                        │
└──────────────┬───────────────────────────────────┘
               │
        1. Validate API Key
        2. Delete /storage/companies/{id}/{type}/{filename}
        3. Remove metadata from database
        4. (204) No Content

AUTO-CLEANUP ON COMPANY DELETE
┌──────────────────────────────────────────┐
│ DELETE /api/v1/company/{id}              │
└──────────────┬───────────────────────────┘
               │
        When company is deleted:
        1. Delete /storage/companies/{id}/logo/
        2. Delete /storage/companies/{id}/content/
        3. All files auto-removed
```

---

## Finetune Data Management Flow

```
┌────────────────────────────────────────────────────────────────────┐
│           FINETUNE/RAG DATA MANAGEMENT FLOW                         │
└────────────────────────────────────────────────────────────────────┘

UPLOAD FINETUNE DATA
┌──────────────────────────────────────────────────┐
│ POST /api/v1/company/{id}/finetune               │
│ Header: X-API-Key: sk_...                        │
│ Body: {content: "# Company Knowledge\n..."}      │
└──────────────┬───────────────────────────────────┘
               │
        1. Validate API Key
        2. Extract company_id from key
        3. Validate content (markdown format)
        4. Insert/Update company_finetune record
        5. Auto-generate model_name:
           perai-{company_name_lowercase}
        6. Save content to disk:
           /backend/app/core/finetune/rag/
           companies/{company_id}/company.md
        7. (201/200) Return: {content, model_name, updated_at}

RETRIEVE FINETUNE DATA
┌──────────────────────────────────────────────────┐
│ GET /api/v1/company/{id}/finetune                │
│ Header: X-API-Key: sk_...                        │
└──────────────┬───────────────────────────────────┘
               │
        1. Validate API Key
        2. Query company_finetune WHERE company_id = id
        3. Load from database
        4. Read from disk:
           /backend/app/core/finetune/rag/
           companies/{company_id}/company.md
        5. (200) Return: {content, model_name, created_at, updated_at}

DELETE FINETUNE DATA
┌──────────────────────────────────────────────────┐
│ DELETE /api/v1/company/{id}/finetune             │
│ Header: X-API-Key: sk_...                        │
└──────────────┬───────────────────────────────────┘
               │
        1. Validate API Key
        2. Delete company_finetune record
        3. Delete file:
           /backend/app/core/finetune/rag/
           companies/{company_id}/company.md
        4. (204) No Content

AUTO-CLEANUP ON COMPANY DELETE
        When company is deleted:
        1. company_finetune record deleted (CASCADE)
        2. /backend/app/core/finetune/rag/companies/{id}/
           directory deleted
```

---

## Error Handling Flow

```
┌────────────────────────────────────────────────────────────────────┐
│              ERROR HANDLING FLOW                                    │
└────────────────────────────────────────────────────────────────────┘

REQUEST COMES IN
        │
        ▼
┌──────────────────────────────┐
│ Validate API Key             │
└──────┬───────────────────────┘
       │
       ├─ Invalid Format
       │  └─► (400) Bad Request
       │      {detail: "Invalid API key format"}
       │
       ├─ Key Not Found
       │  └─► (401) Unauthorized
       │      {detail: "Invalid API key"}
       │
       ├─ Key Revoked
       │  └─► (403) Forbidden
       │      {detail: "API key has been revoked"}
       │
       ├─ Key Expired
       │  └─► (403) Forbidden
       │      {detail: "API key has expired"}
       │
       └─ Valid Key
          ▼
┌──────────────────────────────┐
│ Validate Request Body        │
└──────┬───────────────────────┘
       │
       ├─ Invalid JSON
       │  └─► (400) Bad Request
       │      {detail: "Invalid JSON"}
       │
       ├─ Missing Required Field
       │  └─► (422) Unprocessable Entity
       │      {detail: [{...validation_errors}]}
       │
       └─ Valid Body
          ▼
┌──────────────────────────────┐
│ Process Request              │
└──────┬───────────────────────┘
       │
       ├─ Resource Not Found
       │  └─► (404) Not Found
       │      {detail: "Company not found"}
       │
       ├─ Permission Denied
       │  └─► (403) Forbidden
       │      {detail: "Access denied"}
       │
       ├─ Database Error
       │  └─► (500) Internal Server Error
       │      {detail: "Database error occurred"}
       │
       ├─ External API Error (Groq)
       │  └─► (502) Bad Gateway
       │      {detail: "Failed to call AI API"}
       │
       └─ Success
          ▼
┌──────────────────────────────┐
│ Return Response              │
│ (200/201/204)               │
└──────────────────────────────┘
```

---

## Data Validation Flow

```
┌────────────────────────────────────────────────────────────────────┐
│                DATA VALIDATION FLOW                                 │
└────────────────────────────────────────────────────────────────────┘

COMPANY REGISTRATION
  Input: {company_name, company_email, password}
  
  Validations:
    ├─ company_name
    │  ├─ Required: Yes
    │  ├─ Type: String
    │  ├─ Length: 1-255 characters
    │  ├─ Unique: Yes
    │  └─ Pattern: Any characters allowed
    │
    ├─ company_email
    │  ├─ Required: Yes
    │  ├─ Type: Email
    │  ├─ Length: 1-255 characters
    │  ├─ Unique: Yes
    │  ├─ Pattern: Valid email format
    │  └─ Validation: RFC 5322
    │
    └─ password
       ├─ Required: Yes
       ├─ Type: String
       ├─ Length: Min 8 characters
       ├─ Pattern: Any characters allowed
       └─ Storage: PBKDF2-SHA256 hashed

TICKET CREATION
  Input: {issue, category, api_key}
  
  Validations:
    ├─ issue
    │  ├─ Required: Yes
    │  ├─ Type: String
    │  ├─ Length: 1+ characters
    │  └─ Storage: TEXT (unlimited)
    │
    └─ category
       ├─ Required: No (default: "general")
       ├─ Type: String
       ├─ Enum: ["payment", "technical", "general"]
       └─ Default: "general"

API KEY CREATION
  Input: {key_name, expiry_date, api_key}
  
  Validations:
    ├─ key_name
    │  ├─ Required: Yes
    │  ├─ Type: String
    │  └─ Length: 1-255 characters
    │
    └─ expiry_date
       ├─ Required: No
       ├─ Type: DateTime
       ├─ Format: ISO 8601
       └─ Validation: Must be future date

FILE UPLOAD
  Input: {file, api_key}
  
  Validations for Logo:
    ├─ File size: < 5MB
    ├─ MIME type: PNG, JPEG, GIF, WebP
    └─ Filename: Sanitized
  
  Validations for Content:
    ├─ File size: < 50MB
    ├─ MIME type: PDF, DOCX, TXT, ZIP
    └─ Filename: Sanitized
```

---

## Sequence Diagram: Complete Chat Flow

```
┌──────────────────────────────────────────────────────────────────┐
│          COMPLETE CHAT INTERACTION SEQUENCE                       │
└──────────────────────────────────────────────────────────────────┘

Frontend User      FastAPI Backend    Database      File System   Groq API
   │                   │                  │              │            │
   │ 1. User Types     │                  │              │            │
   │    Message        │                  │              │            │
   │ (Company ID = 5)  │                  │              │            │
   ├──────────────────►│                  │              │            │
   │                   │                  │              │            │
   │                   │ 2. Extract API Key               │            │
   │                   │    from X-API-Key header         │            │
   │                   │                  │              │            │
   │                   │ 3. Hash API Key  │              │            │
   │                   │    (SHA-256)     │              │            │
   │                   │                  │              │            │
   │                   │ 4. Lookup Hash   │              │            │
   │                   ├─────────────────►│              │            │
   │                   │◄─────────────────┤              │            │
   │                   │ (Found: API Key ID, Company 5) │            │
   │                   │                  │              │            │
   │                   │ 5. Get Company Data             │            │
   │                   ├─────────────────►│              │            │
   │                   │◄─────────────────┤              │            │
   │                   │ (Company: {id:5, name:"Acme"}) │            │
   │                   │                  │              │            │
   │                   │ 6. Get Finetune Record         │            │
   │                   ├─────────────────►│              │            │
   │                   │◄─────────────────┤              │            │
   │                   │ (Finetune: {model_name:"perai-│            │
   │                   │             acme"})            │            │
   │                   │                  │              │            │
   │                   │ 7. Read Knowledge Base from Disk│            │
   │                   ├──────────────────────────────► │            │
   │                   │◄──────────────────────────────┤ │            │
   │                   │ (Content: "# Acme Knowledge...")│            │
   │                   │                  │              │            │
   │                   │ 8. Build Prompt:                │            │
   │                   │    "Context: {content}          │            │
   │                   │     User: {message}"            │            │
   │                   │                  │              │            │
   │                   │ 9. Call Groq API                │            │
   │                   ├────────────────────────────────────────────►│
   │                   │                  │              │            │
   │                   │                  │              │            │ 10. Process
   │                   │                  │              │            │     with LLM
   │                   │                  │              │            │
   │                   │◄────────────────────────────────────────────┤
   │                   │ (Response: "Based on your knowledge base...")│
   │                   │                  │              │            │
   │                   │ 11. Return Chat Response:        │            │
   │                   │     {model_name: "perai-acme",   │            │
   │                   │      company_id: 5,              │            │
   │                   │      response: "..."}            │            │
   │◄──────────────────┤                  │              │            │
   │                   │                  │              │            │
   │ 12. Display                          │              │            │
   │     Response                         │              │            │
   │                   │                  │              │            │
```

---

## System State Transitions

```
┌────────────────────────────────────────────────────────────────────┐
│              API KEY STATE TRANSITIONS                              │
└────────────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │   CREATED    │
                    │  (active)    │
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
         [REVOKE]    [EXPIRY DATE]  [USE]
              │            │            │
              ▼            ▼            ▼
         ┌────────┐   ┌────────┐   [ACTIVE]
         │REVOKED │   │EXPIRED │   (continues)
         └────────┘   └────────┘


┌────────────────────────────────────────────────────────────────────┐
│              TICKET STATE TRANSITIONS                               │
└────────────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │    OPEN      │
                    │  (created)   │
                    └──────┬───────┘
                           │
            ┌──────────────┼──────────────┐
            │                             │
        [CLOSE]                       [UPDATE]
            │                             │
            ▼                             ▼
        ┌────────┐                   ┌─────────┐
        │ CLOSED │                   │ UPDATED │
        └────────┘                   │ still   │
            │                        │ OPEN    │
            │                        └────┬────┘
            │   ┌──────────────────────────┘
            │   │
            └───┼────────┐
                │        │
            [REOPEN]  [CLOSE]
                │        │
                ▼        ▼
            [OPEN]   [CLOSED]
                     (final)


┌────────────────────────────────────────────────────────────────────┐
│           COMPANY LIFECYCLE                                         │
└────────────────────────────────────────────────────────────────────┘

REGISTRATION          ACTIVE               DELETE
   │                   │                     │
   ▼                   ▼                     ▼
┌──────────┐      ┌──────────┐         ┌─────────┐
│ CREATED  │──────► ACTIVE   │────────► DELETED │
│          │      │ (can use) │        │ (cascade)
└──────────┘      └──────────┘        └─────────┘
                        │
                    ┌───┴───┐
                    │       │
                [UPDATE] [CREATE]
                    │     APIKEYS/
                    │    TICKETS
                    │       │
                    └───┬───┘
                        │
                    [ACTIVE]
```

---

## Summary

This flowchart and system documentation covers:

1. **System Overview** - Architecture and components
2. **Authentication** - Registration, login, verification flows
3. **API Key Management** - Generation and validation flows
4. **Chat Integration** - Groq AI with finetune context
5. **Ticket Management** - CRUD, filtering, history, statistics
6. **Company Management** - CRUD operations and cascading deletes
7. **File Management** - Upload, download, list, delete operations
8. **Finetune Data** - Knowledge base management on disk
9. **Error Handling** - Comprehensive error response flows
10. **Data Validation** - Input validation for all endpoints
11. **Sequence Diagrams** - Complete interaction flows
12. **State Transitions** - API key, ticket, and company states

All flows are synchronized with the database schema and implement proper security, validation, and cascade deletion patterns.
