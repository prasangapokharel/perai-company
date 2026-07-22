# Chapter 3: System Analysis and Design

## 3.1 System Analysis

### 3.1.1 Requirement Analysis

#### i. Functional Requirements

**Table 3.1: Functional Requirements**

| Req. ID | Requirement | Description |
|---------|-------------|-------------|
| FR-01 | Company Registration | A company can register by providing company name, email, and password. The system creates a company account, issues a starting balance, and auto-generates a default API key. |
| FR-02 | Company Login | A company authenticates using email and password. On success, the system returns a signed JWT access token valid for 60 minutes. |
| FR-03 | Knowledge Base Upload | A company uploads JSONL records (one JSON object per line: question/answer, title/content, or text). The system validates each line, normalizes records, and writes them to a per-company RAG file in append or replace mode. |
| FR-04 | AI Chat (Streaming) | An end user sends a text message with a valid API key. The system retrieves relevant knowledge (BM25 + exact match), builds a prompt incorporating company tone and language settings, calls the LLM, and streams tokens back to the client using Server-Sent Events. |
| FR-05 | AI Chat (Non-Streaming) | Same as FR-04 but returns the complete response as a single JSON object, with optional base64-encoded WAV audio (TTS). |
| FR-06 | Company Settings | A company configures language (english/nepali), tone (formal/casual/friendly/professional), and maximum reply tokens (50–1000). |
| FR-07 | API Key Management | A company creates, lists, revokes, and deletes API keys. Each key has a name, preview (first 4 and last 4 chars), SHA-256 hash, status (active/revoked), and expiry date. |
| FR-08 | Balance Query | A company views its current USD credit balance. |
| FR-09 | Balance Top-up (Manual) | A company admin can add free credits in development mode. |
| FR-10 | Khalti Payment Initiate | A company selects a credit package (e.g., $5, $10, $25) and initiates a Khalti ePayment. The system converts USD to NPR paisa, calls Khalti's initiate API, stores the payment record, and returns a payment URL for browser redirect. |
| FR-11 | Khalti Payment Verify | After Khalti redirects the user back with a `pidx`, the system calls Khalti's lookup API to verify the payment status. If status is *Completed* and the paid amount matches, the USD balance is credited exactly once (idempotent). |
| FR-12 | Usage History | A company views per-request deduction history (amount, tokens, model, timestamp). |
| FR-13 | Top-up History | A company views a list of all balance top-ups with amounts and references. |
| FR-14 | Chat Session History | A company views all chat sessions with message logs, token usage, and timestamps. |
| FR-15 | Support Ticket Create | A company creates a support ticket with an issue description and category (payment/technical/general). |
| FR-16 | Support Ticket Track | A company views ticket status (open/closed) and open/close history. |
| FR-17 | Chat Widget Embed | A company copies an HTML snippet to embed a branded chat widget on its website. |
| FR-18 | Company Dashboard | An overview page shows current balance, model status, active API keys, and recent activity. |

**Use Case Diagram**

**Figure 3.1: Use Case Diagram**

```mermaid
flowchart LR
    CA((Company\nAdmin))
    EU((End User /\nWebsite Visitor))
    KH((Khalti\nGateway))
    LLM((Groq LLM\nProvider))

    subgraph Perai [Perai Platform]
        UC01([Register])
        UC02([Login])
        UC03([Upload Knowledge Base])
        UC04([Configure Settings])
        UC05([Manage API Keys])
        UC06([View Balance & Usage])
        UC07([Initiate Khalti Payment])
        UC08([Verify Khalti Payment])
        UC09([Open Support Ticket])
        UC10([Embed Chat Widget])
        UC11([Chat with AI - Streaming])
        UC12([Chat with AI - Non-Streaming])
        UC13([Receive TTS Audio Reply])
    end

    CA --> UC01
    CA --> UC02
    CA --> UC03
    CA --> UC04
    CA --> UC05
    CA --> UC06
    CA --> UC07
    CA --> UC08
    CA --> UC09
    CA --> UC10

    EU --> UC11
    EU --> UC12
    EU --> UC13

    UC07 --> KH
    UC08 --> KH
    UC11 --> LLM
    UC12 --> LLM
```

**Use Case Descriptions**

**Table 3.3: Use Case Description – Register/Login**

| Field | Description |
|-------|-------------|
| Use Case Name | Register and Login |
| Actor | Company Admin |
| Precondition | No account exists for the email address. |
| Main Flow | 1. Admin fills registration form (company name, email, password). 2. System validates inputs. 3. System hashes password using bcrypt. 4. System creates company row, balance row (with starting credit), settings row, and default API key. 5. System redirects to login. 6. Admin enters email and password. 7. System verifies password hash. 8. System issues JWT. 9. Admin is redirected to dashboard. |
| Alternate Flow | Duplicate email → 400 error. Weak password (<8 chars) → validation error. Wrong password → 401 error. |
| Postcondition | Company account created; JWT issued; dashboard accessible. |

**Table 3.4: Use Case Description – Khalti Top-up**

| Field | Description |
|-------|-------------|
| Use Case Name | Balance Top-up via Khalti |
| Actor | Company Admin |
| Precondition | Company is logged in. Khalti secret key configured on server. |
| Main Flow | 1. Admin selects a credit package amount (e.g., $5 = NPR 700). 2. System calls `POST /epayment/initiate/` with NPR paisa amount. 3. Khalti returns `pidx` + `payment_url`. 4. System stores `khalti_payment` row (status=Initiated). 5. Browser redirects to Khalti payment page. 6. Admin completes payment using Khalti wallet or bank. 7. Khalti redirects browser to `/balance?pidx=<pidx>`. 8. Frontend calls `POST /companyBalance/{id}/khalti/verify`. 9. System calls Khalti lookup API. 10. If status=Completed and amount matches, system credits balance once and stores `balance_topup` row with reference `khalti:<pidx>`. 11. New balance shown. |
| Alternate Flow | Payment cancelled/pending → status shown, no credit. Amount mismatch → payment flagged, no credit, error shown. Verify called twice → second call is no-op (idempotent). |
| Postcondition | Balance increased by package amount exactly once; top-up appears in history. |

**Table 3.5: Use Case Description – AI Chat**

| Field | Description |
|-------|-------------|
| Use Case Name | Chat with AI Assistant |
| Actor | End User (via widget or direct API call) |
| Precondition | Valid active API key with `X-API-Key` header. Company has sufficient balance. |
| Main Flow | 1. User sends message to `POST /company/{id}/chat/query`. 2. System validates API key (hash lookup). 3. System reserves estimated credits from balance. 4. System retrieves top-K records from company's RAG file (BM25). 5. System builds prompt with company tone, language, max tokens, and retrieved context. 6. System calls Groq LLM (with fallback to backup API keys on failure). 7. System returns streamed or complete response. 8. System finalizes cost: refunds unused reserved credits or deducts extra. 9. System logs chat_message and balance_deduct rows. |
| Alternate Flow | Insufficient balance → 402 error, no LLM call. Invalid key → 401 error. LLM provider failure → fallback keys tried; if all fail, reservation released and error returned. |
| Postcondition | AI reply delivered; deduction logged; session history updated. |

#### ii. Non-Functional Requirements

**Table 3.2: Non-Functional Requirements**

| Req. ID | Category | Requirement |
|---------|----------|-------------|
| NFR-01 | Security | Passwords stored as bcrypt hashes. API keys stored as SHA-256 hashes. JWT signed with secret key. Per-company resource isolation enforced at the application layer (403 on cross-company access). |
| NFR-02 | Performance | Vectorless BM25 retrieval completes in <10ms for knowledge bases up to 5,000 records. LLM streaming begins within 1 second of request. |
| NFR-03 | Reliability | Khalti payment crediting is idempotent: a payment verified twice is credited exactly once. LLM API key rotation provides fallback if primary key fails. |
| NFR-04 | Scalability | Stateless REST API supports horizontal scaling. Row-level multi-tenancy allows unlimited companies on one database instance. |
| NFR-05 | Usability | Dashboard UI is responsive (mobile + desktop), using accessible shadcn/ui components. Empty states shown when no data exists (no raw 404 errors in UI). |
| NFR-06 | Maintainability | Layered architecture (route → service → ORM model). Database schema versioned with Alembic migrations. TypeScript types enforced across frontend codebase. |
| NFR-07 | Availability | API rate limiting (60 requests/minute for chat, 120/minute default) prevents abuse and ensures availability for all tenants. |
| NFR-08 | Portability | SQLAlchemy ORM abstracts the database engine; system runs on PostgreSQL (production) and SQLite (local development/testing) with no code changes. |

---

### 3.1.2 Feasibility Analysis

**Table 3.6: Feasibility Analysis Summary**

| Dimension | Assessment | Conclusion |
|-----------|-----------|------------|
| Technical | All technologies are free, open-source, and well-documented. LLM inference is via hosted API (no GPU required). Vectorless RAG eliminates the need for a vector database. | **Feasible** |
| Operational | Company staff need no AI knowledge. The only required actions are uploading a JSONL file (sample provided) and copying a widget snippet. Dashboard is self-service. | **Feasible** |
| Economic | No infrastructure cost for the student developer. Variable costs (Groq API, Khalti transaction fee) are passed to tenants via the credit billing system. | **Feasible** |
| Schedule | The modular incremental approach (7 modules) fits within one academic semester. Each module is independently deployable and testable. | **Feasible** |

#### i. Technical Feasibility

Perai uses entirely proven, production-ready technologies:
- **FastAPI** (Python) — used in production at Uber, Netflix, Microsoft.
- **Next.js** — powers Vercel, TikTok, Hulu dashboards.
- **PostgreSQL** — the world's most advanced open-source relational database.
- **Groq Llama 3.3 70B** — publicly available inference API with documented pricing and rate limits.
- **Khalti ePayment API v2** — in production use by numerous Nepali e-commerce and educational platforms.

The vectorless BM25 approach is technically simpler than standard RAG — it requires only standard file I/O and a text-processing library, well within Python's standard capabilities.

#### ii. Operational Feasibility

The system is designed for non-technical company administrators. The dashboard walkthrough is:
1. Register → automatic setup (no configuration required).
2. Finetune page → upload JSONL file → knowledge instantly live.
3. Widget page → copy one line of HTML → paste into company website.
4. Balance page → select package → pay via Khalti → credits added automatically.

All complex operations (hashing, JWT, LLM calls, RAG retrieval, billing) are handled invisibly by the backend.

#### iii. Economic Feasibility

**Development cost:** Zero (student project using free tools and open-source software). **Operational cost (per company):** Approximately $0.0006–0.002 per chat request (Groq token cost). **Revenue model:** Companies purchase prepaid credits at a margin above cost. **Payment processing:** Khalti charges approximately 2% per transaction. This model is economically self-sustaining once deployed.

#### iv. Schedule Feasibility

The project was completed within one academic semester using the following timeline:

| Week | Activity |
|------|----------|
| 1–2 | Requirement gathering, system design |
| 3–4 | Authentication and company management (Increment 1–2) |
| 5–6 | Knowledge base and RAG (Increment 3) |
| 7–8 | AI chat with metering (Increment 4) |
| 9–10 | Billing and Khalti integration (Increment 5) |
| 11–12 | Tickets and dashboard frontend (Increment 6–7) |
| 13–14 | Testing, documentation, and report writing |

---

### 3.1.3 Object Modelling using Class and Object Diagrams

**Figure 3.2: Class Diagram**

```mermaid
classDiagram
    class Company {
        +int id
        +String company_name
        +String company_email
        +String password_hash
        +String logo
        +String website
        +DateTime created_at
        +DateTime updated_at
        +register(name, email, password) Company
        +login(email, password) JWT
        +update(data) Company
    }

    class APIKey {
        +int id
        +int company_id
        +String name
        +String key_hash
        +String key_preview
        +String status
        +DateTime expiry_date
        +DateTime last_used_at
        +create(company_id, name) APIKey
        +revoke() void
        +validate(raw_key) bool
    }

    class CompanyFinetune {
        +int id
        +int company_id
        +String company_model_name
        +String rag_company_path
        +String content
        +DateTime created_at
        +DateTime updated_at
        +upload(jsonl_content, mode) CompanyFinetune
        +retrieve_context(query, top_k) List~String~
    }

    class CompanySettings {
        +int id
        +int company_id
        +String language
        +String tone
        +int max_tokens
        +DateTime created_at
        +update(language, tone, max_tokens) CompanySettings
    }

    class CompanyBalance {
        +int id
        +int company_id
        +Decimal balance
        +DateTime updated_at
        +reserve(amount) Decimal
        +release(amount) void
        +finalize(reserved, actual) BalanceDeduct
        +topup(amount, reference) BalanceTopup
    }

    class BalanceTopup {
        +int id
        +int company_id
        +Decimal amount
        +String reference
        +DateTime created_at
    }

    class BalanceDeduct {
        +int id
        +int company_id
        +int chat_message_id
        +String session_id
        +Decimal amount
        +int token_consume
        +String model_name
        +DateTime created_at
    }

    class ChatMessage {
        +int id
        +int company_id
        +String session_id
        +String conversation
        +String review
        +String ip
        +int token_consume
        +String model_name
        +DateTime created_at
        +log(prompt, reply, tokens) ChatMessage
    }

    class KhaltiPayment {
        +int id
        +int company_id
        +String pidx
        +Decimal amount_usd
        +int amount_npr_paisa
        +String status
        +String transaction_id
        +DateTime created_at
        +initiate(amount_usd) KhaltiPayment
        +verify(pidx) KhaltiPayment
    }

    class Ticket {
        +int id
        +int company_id
        +String issue
        +String category
        +String status
        +DateTime created_at
        +create(issue, category) Ticket
        +close() void
    }

    class TicketOpened {
        +int id
        +int company_id
        +int ticket_id
        +DateTime opened_at
        +DateTime closed_at
    }

    Company "1" --> "0..*" APIKey : owns
    Company "1" --> "0..1" CompanyFinetune : has
    Company "1" --> "0..1" CompanySettings : configures
    Company "1" --> "0..1" CompanyBalance : holds
    Company "1" --> "0..*" BalanceTopup : receives
    Company "1" --> "0..*" BalanceDeduct : charged
    Company "1" --> "0..*" ChatMessage : logs
    Company "1" --> "0..*" KhaltiPayment : pays via
    Company "1" --> "0..*" Ticket : opens
    Ticket "1" --> "0..*" TicketOpened : has history
    ChatMessage "1" --> "0..1" BalanceDeduct : priced by
```

**Figure 3.3: Object Diagram – Company and Balance**

```mermaid
classDiagram
    class company_1 {
        id = 1
        company_name = "ABC School"
        company_email = "admin@abcschool.edu.np"
        language = "nepali"
        tone = "formal"
    }

    class balance_1 {
        id = 1
        company_id = 1
        balance = 24.50 USD
        updated_at = "2026-07-20 10:30"
    }

    class topup_1 {
        id = 3
        company_id = 1
        amount = 5.00
        reference = "khalti:kfmNu4SaBT"
        created_at = "2026-07-20 09:15"
    }

    class deduct_1 {
        id = 14
        company_id = 1
        amount = 0.000480
        token_consume = 220
        model_name = "llama-3.3-70b"
    }

    company_1 --> balance_1 : holds
    balance_1 --> topup_1 : credited by
    balance_1 --> deduct_1 : debited by
```

---

### 3.1.4 Dynamic Modelling using State and Sequence Diagrams

**Figure 3.4: State Diagram – Khalti Payment**

```mermaid
stateDiagram-v2
    [*] --> Initiated : Admin selects package\n& calls initiate API
    Initiated --> Pending : Khalti processes\npayment request
    Pending --> Completed : User pays\nsuccessfully
    Pending --> Expired : Payment not\ncompleted in time
    Pending --> Canceled : User cancels\nthe payment
    Completed --> Credited : System verifies\n& credits balance
    Credited --> [*]
    Expired --> [*]
    Canceled --> [*]
    Completed --> AmountMismatch : Paid amount ≠\ninitiated amount
    AmountMismatch --> [*]
```

**Figure 3.5: State Diagram – Chat Session**

```mermaid
stateDiagram-v2
    [*] --> Idle : API key validated
    Idle --> Reserving : Message received
    Reserving --> InsufficientBalance : Balance < estimated cost
    Reserving --> Retrieving : Credits reserved
    InsufficientBalance --> [*] : Return 402 error
    Retrieving --> Building : Context retrieved\nfrom RAG file
    Building --> Generating : Prompt built with\ntone + context
    Generating --> Streaming : LLM generates tokens
    Streaming --> Finalizing : All tokens received
    Finalizing --> Logged : Deduction finalized,\nmessage saved
    Logged --> [*]
    Generating --> Error : LLM provider\nfailure
    Error --> Released : Reservation released
    Released --> [*] : Return error
```

**Figure 3.6: Sequence Diagram – Company Registration and Login**

```mermaid
sequenceDiagram
    actor Admin
    participant UI as Next.js Dashboard
    participant API as FastAPI Backend
    participant DB as Database

    Admin->>UI: Fill registration form
    UI->>API: POST /api/v1/auth/register
    API->>API: Validate inputs
    API->>API: Hash password (bcrypt)
    API->>DB: INSERT company row
    API->>DB: INSERT company_balance (starting credits)
    API->>DB: INSERT company_settings (defaults)
    API->>DB: INSERT api_key (default key)
    API-->>UI: 201 Company created
    UI-->>Admin: Redirect to /login

    Admin->>UI: Enter email + password
    UI->>API: POST /api/v1/auth/login
    API->>DB: SELECT company WHERE email=?
    API->>API: Verify password hash
    API->>API: Sign JWT (company_id, exp)
    API-->>UI: 200 {access_token, company}
    UI->>UI: Save session to localStorage
    UI->>UI: Set auth cookie (perai_auth=1)
    UI-->>Admin: Redirect to /dashboard
```

**Figure 3.7: Sequence Diagram – AI Chat Request (Streaming)**

```mermaid
sequenceDiagram
    actor User
    participant Widget as Chat Widget
    participant API as FastAPI Backend
    participant RAG as RAG Service
    participant LLM as Groq LLM API
    participant DB as Database

    User->>Widget: Type message & submit
    Widget->>API: POST /company/{id}/chat/stream\n(X-API-Key header)
    API->>DB: SELECT api_key WHERE hash=SHA256(key)
    DB-->>API: Key valid, company_id returned
    API->>DB: UPDATE balance (reserve estimated credits)
    API->>RAG: retrieve_context(message, top_k=3)
    RAG->>RAG: BM25 rank knowledge file
    RAG-->>API: Top-K matching records
    API->>API: Build prompt (tone + language + context)
    API->>LLM: POST /chat/completions (stream=true)
    loop Stream tokens
        LLM-->>API: Token chunk
        API-->>Widget: data: {"type":"token","content":"..."}
    end
    LLM-->>API: [DONE]
    API->>DB: INSERT chat_message row
    API->>DB: UPDATE balance (finalize deduction)
    API->>DB: INSERT balance_deduct row
    API-->>Widget: data: {"type":"done"}
    Widget-->>User: Display complete reply
```

**Figure 3.8: Sequence Diagram – Khalti Balance Top-up**

```mermaid
sequenceDiagram
    actor Admin
    participant UI as Balance Page
    participant API as FastAPI Backend
    participant DB as Database
    participant Khalti as Khalti Gateway

    Admin->>UI: Select $5 package & click Pay
    UI->>API: POST /companyBalance/{id}/khalti/initiate\n{amount: 5}
    API->>API: Convert USD to NPR paisa\n(5 * 140 * 100 = 70000 paisa)
    API->>Khalti: POST /epayment/initiate/\n{amount:70000, return_url:...}
    Khalti-->>API: {pidx:"kfmNu...", payment_url:"https://..."}
    API->>DB: INSERT khalti_payment (pidx, status=Initiated)
    API-->>UI: {pidx, payment_url, amount_npr_paisa:70000}
    UI->>UI: Redirect browser to payment_url

    Admin->>Khalti: Complete payment (wallet/bank)
    Khalti->>UI: Redirect to /balance?pidx=kfmNu...

    UI->>API: POST /companyBalance/{id}/khalti/verify\n{pidx:"kfmNu..."}
    API->>DB: SELECT khalti_payment WHERE pidx=?
    API->>DB: SELECT balance_topup WHERE reference="khalti:kfmNu..." (idempotency check)
    alt Not yet credited
        API->>Khalti: POST /epayment/lookup/\n{pidx:"kfmNu..."}
        Khalti-->>API: {status:"Completed", total_amount:70000}
        API->>API: Verify total_amount == amount_npr_paisa
        API->>DB: UPDATE company_balance (+5.00 USD)
        API->>DB: INSERT balance_topup (amount=5, reference="khalti:kfmNu...")
        API->>DB: UPDATE khalti_payment (status=Completed)
    else Already credited
        API->>API: Skip crediting (idempotent)
    end
    API-->>UI: {status:"Completed", balance:29.50}
    UI-->>Admin: Show updated balance & success message
```

---

### 3.1.5 Process Modelling using Activity Diagrams

**Figure 3.9: Activity Diagram – Knowledge Base Upload**

```mermaid
flowchart TD
    S([Start]) --> A[Admin opens Finetune page]
    A --> B[Select JSONL file from disk]
    B --> C{Choose mode}
    C -- Append --> D[Keep existing knowledge]
    C -- Replace --> E[Delete existing knowledge file]
    D --> F[Parse JSONL: validate each line]
    E --> F
    F --> G{Any invalid\nJSON line?}
    G -- Yes --> H[Return error with line number]
    H --> B
    G -- No --> I[Normalize records:\nquestion/answer,\ntitle/content, or text]
    I --> J[Write normalized records\nto per-company RAG file]
    J --> K[Upsert company_finetune row\nmodel name + file path]
    K --> L[Knowledge base instantly\navailable for retrieval]
    L --> E2([End])
```

**Figure 3.10: Activity Diagram – Complete Chat Request Flow**

```mermaid
flowchart TD
    S([Start]) --> A[Receive chat message\n+ X-API-Key header]
    A --> B[Hash the API key\nSHA-256]
    B --> C{Key found in DB\n& status=active?}
    C -- No --> ERR1[Return 401 Unauthorized]
    ERR1 --> X([End])
    C -- Yes --> D{Key expired?}
    D -- Yes --> ERR2[Return 401 Key expired]
    ERR2 --> X
    D -- No --> E[Estimate token cost\nfrom message length]
    E --> F{Balance >=\nestimated cost?}
    F -- No --> ERR3[Return 402\nInsufficient Balance]
    ERR3 --> X
    F -- Yes --> G[Reserve estimated\ncredits - row lock]
    G --> H[Retrieve top-K records\nBM25 ranking]
    H --> I[Load company settings\nlanguage, tone, max_tokens]
    I --> J[Build system prompt\nwith tone + language]
    J --> K[Append retrieved context\nto prompt]
    K --> L[Call Groq LLM\nwith fallback key rotation]
    L --> M{LLM call\nsucceeded?}
    M -- No --> N[Release reservation]
    N --> ERR4[Return 503 LLM Error]
    ERR4 --> X
    M -- Yes --> O{Streaming\nrequested?}
    O -- Yes --> P[Stream tokens\nvia SSE to client]
    O -- No --> Q[Wait for complete\nresponse]
    P --> R[Count actual tokens used]
    Q --> R
    R --> S2{Audio\nrequested?}
    S2 -- Yes --> T[Generate TTS WAV\nencode as base64]
    S2 -- No --> U[Skip TTS]
    T --> V[Finalize deduction:\nrefund or deduct difference]
    U --> V
    V --> W[INSERT chat_message row]
    W --> Y[INSERT balance_deduct row]
    Y --> Z[Return response to client]
    Z --> X
```

---

## 3.2 System Design

### 3.2.1 Refinement of Class, Object, State, Sequence and Activity Diagrams

After analysis, the following refinements were applied during system design:

1. **Class refinement:** The `CompanyBalance` class was separated from `Company` to allow balance updates without locking the company record. A `with_for_update()` row lock is applied to `company_balance` during reserve and finalize operations to prevent race conditions under concurrent requests.

2. **KhaltiPayment refinement:** The `reference` field on `BalanceTopup` is the primary idempotency guard (value: `"khalti:<pidx>"`). Before crediting, the system checks for an existing `BalanceTopup` row with this reference — this is faster and more reliable than checking `KhaltiPayment.status` alone, since it is set in the same transaction as the credit.

3. **Chat state refinement:** The streaming and non-streaming paths share the same reservation and finalization logic; only the response delivery mechanism differs (`StreamingResponse` vs. `JSONResponse`).

4. **Sequence refinement:** The API key lookup was moved to the earliest possible point in the chat pipeline (before any DB writes or LLM calls) to fail fast and avoid unnecessary work on invalid requests.

### 3.2.2 Component Diagrams

**Figure 3.11: Component Diagram**

```mermaid
flowchart TB
    subgraph Frontend ["Frontend Component (Next.js)"]
        DASH[Dashboard Pages\n14 React pages]
        MW[Middleware\nAuth cookie guard]
        SVC[Service Layer\nTypeScript API clients]
        WIDGET[Chat Widget\nEmbeddable HTML/JS]
    end

    subgraph Backend ["Backend Component (FastAPI)"]
        ROUTER[API Routers\nv1/* endpoints]
        AUTH_MOD[Auth Module]
        FINETUNE_MOD[Finetune Module]
        CHAT_MOD[Chat Module]
        BILLING_MOD[Billing Module]
        KHALTI_MOD[Khalti Module]
        TICKET_MOD[Ticket Module]
        APIKEY_MOD[API Key Module]
        RAG[RAG Engine\nBM25 + file I/O]
        SEC[Security Layer\nJWT + API key validation]
    end

    subgraph Data ["Data Component"]
        DB[(PostgreSQL\nSQLAlchemy ORM\nAlembic migrations)]
        FILES[/Per-company\nRAG files/]
    end

    subgraph External ["External Services"]
        GROQ[Groq LLM API]
        KHALTI_GW[Khalti ePayment API]
        TTS_SVC[Supertonic TTS]
    end

    DASH --> SVC
    MW --> DASH
    SVC --> ROUTER
    WIDGET --> CHAT_MOD

    ROUTER --> AUTH_MOD & FINETUNE_MOD & CHAT_MOD & BILLING_MOD & KHALTI_MOD & TICKET_MOD & APIKEY_MOD
    CHAT_MOD --> RAG
    CHAT_MOD --> GROQ
    CHAT_MOD --> TTS_SVC
    CHAT_MOD --> BILLING_MOD
    KHALTI_MOD --> KHALTI_GW
    AUTH_MOD & FINETUNE_MOD & BILLING_MOD & KHALTI_MOD & TICKET_MOD & APIKEY_MOD --> DB
    SEC --> ROUTER
    FINETUNE_MOD --> FILES
    RAG --> FILES
```

### 3.2.3 Deployment Diagrams

**Figure 3.12: Deployment Diagram**

```mermaid
flowchart TB
    subgraph Client ["Client Devices"]
        BROWSER[Web Browser\nCompany Admin]
        ENDUSER[End User Browser\nor Mobile]
    end

    subgraph VPS ["Production Server (VPS / Cloud)"]
        subgraph FE_SERVER ["Frontend Node"]
            NEXTJS[Next.js Server\nPort 3000\nNode.js runtime]
        end
        subgraph BE_SERVER ["Backend Node"]
            UVICORN[Uvicorn ASGI Server\nPort 8000\nPython 3.11+]
            FASTAPI_APP[FastAPI Application]
            STATIC[/RAG Knowledge Files\n/storage/companies/]
        end
        subgraph DB_SERVER ["Database Node"]
            POSTGRES[(PostgreSQL 15\nPort 5432)]
        end
    end

    subgraph Cloud_Services ["External Cloud Services"]
        GROQ_API[Groq LLM API\napi.groq.com]
        KHALTI_API[Khalti ePayment\nkhalti.com/api/v2]
    end

    BROWSER -- "HTTPS :443" --> NEXTJS
    ENDUSER -- "HTTPS :443 (widget)" --> UVICORN
    NEXTJS -- "HTTP :8000 (internal)" --> UVICORN
    UVICORN --> FASTAPI_APP
    FASTAPI_APP -- "SQLAlchemy\nTCP :5432" --> POSTGRES
    FASTAPI_APP -- "File I/O" --> STATIC
    FASTAPI_APP -- "HTTPS API call" --> GROQ_API
    FASTAPI_APP -- "HTTPS API call" --> KHALTI_API
```

---

## 3.3 Algorithm Details

### 3.3.1 BM25 Retrieval Algorithm

The knowledge retrieval algorithm used in Perai's RAG engine is described below:

**Input:** User query `Q`, company knowledge file (JSONL), `top_k` (default 3)
**Output:** List of at most `top_k` most relevant knowledge records as strings

```
Algorithm: RetrieveContext(Q, knowledge_file, top_k)
1. Load all records from knowledge_file into corpus C
2. Tokenize each record in C into terms → document term vectors
3. Compute corpus statistics:
   a. avgDL = average document length across C
   b. For each term t: IDF(t) = log((N - df(t) + 0.5) / (df(t) + 0.5) + 1)
      where N = |C| and df(t) = number of documents containing t
4. Tokenize Q into query terms [q1, q2, ..., qm]
5. For each document D in C:
   a. Score(D) = 0
   b. For each query term qi:
      Score(D) += IDF(qi) * (f(qi,D) * (k1+1)) / (f(qi,D) + k1*(1-b+b*|D|/avgDL))
      where k1=1.5, b=0.75
6. Sort C by Score(D) descending
7. Additionally: check each document for exact match of any named entity in Q
   (product name, person name, ID number) → boost to top position
8. Return top_k documents as formatted context strings
```

**Complexity:** O(|C| × |Q|) per query — linear in corpus size and query length, typically <5ms for 1,000-record corpora.

### 3.3.2 Idempotent Credit Algorithm

The Khalti payment crediting algorithm guarantees exactly-once balance updates:

```
Algorithm: VerifyAndCredit(db, company_id, pidx)
1. Fetch khalti_payment WHERE pidx = pidx AND company_id = company_id
   IF not found: raise KhaltiError("Unknown payment reference")

2. reference = "khalti:" + pidx
   existing_topup = SELECT balance_topup WHERE reference = reference
   IF existing_topup IS NOT NULL:
     UPDATE khalti_payment SET status = "Completed"
     RETURN current balance  // idempotent: already credited, skip

3. Call Khalti lookup API: POST /epayment/lookup/ {pidx: pidx}
   status = response.status
   paid_amount = response.total_amount

4. UPDATE khalti_payment SET status = status, transaction_id = response.transaction_id

5. IF status != "Completed": RETURN current balance (no credit)

6. IF paid_amount != khalti_payment.amount_npr_paisa:
   UPDATE khalti_payment SET status = "AmountMismatch"
   RAISE KhaltiError("Paid amount does not match")

7. // Credit exactly once within same DB transaction:
   BEGIN TRANSACTION
     UPDATE company_balance SET balance += amount_usd WHERE company_id = company_id
     INSERT INTO balance_topup (company_id, amount, reference) VALUES (company_id, amount_usd, reference)
     UPDATE khalti_payment SET status = "Completed"
   COMMIT

8. RETURN new balance
```

**Key guarantees:**
- Step 2 prevents double-credit if `verify` is called more than once (network retry, browser back button).
- Steps 5–6 prevent partial crediting on failed or mismatched payments.
- Step 7 uses a single database transaction — if any part fails, the entire credit is rolled back.

### 3.3.3 Database Normalization

**Un-normalized Form (UNF) — flat spreadsheet:**

| company_id | company_name | email | api_keys | balance | topup_history | chat_session | tokens | khalti_pids |
|---|---|---|---|---|---|---|---|---|
| 1 | ABC School | admin@abc.edu.np | "sk_a1,sk_b2" | 24.50 | "5 on Jul-19; 10 on Jul-20" | ab12cd | 220 | "kfmNu,pQr7" |

Problems: multi-valued columns, redundancy, deletion/update/insertion anomalies.

**First Normal Form (1NF):** Each attribute atomic, no repeating groups:

| company_id | company_name | email | api_key | topup_amount | topup_date | session_id | tokens | khalti_pidx |
|---|---|---|---|---|---|---|---|---|
| 1 | ABC School | admin@abc.edu.np | sk_a1 | 5.00 | Jul-19 | ab12cd | 220 | kfmNu |
| 1 | ABC School | admin@abc.edu.np | sk_b2 | 5.00 | Jul-19 | ab12cd | 220 | kfmNu |
| 1 | ABC School | admin@abc.edu.np | sk_a1 | 10.00 | Jul-20 | cd34ef | 310 | pQr7 |

✔ Atomic values. ✘ Company facts repeat on every row (partial and transitive dependencies).

**Second Normal Form (2NF):** Remove partial dependencies (facts that depend on only part of the composite key):

- **company_2nf** (*company_id*, company_name, email, language, tone, balance)
- **api_key_2nf** (*company_id, api_key*, status, expiry_date)
- **topup_2nf** (*company_id, topup_date, seq*, amount, reference)
- **chat_2nf** (*session_id, msg_seq*, company_id, tokens, model)
- **khalti_2nf** (*pidx*, company_id, amount_usd, amount_paisa, status)

✔ No partial dependencies. ✘ In `company_2nf`, `balance` is transitively dependent on `topup` events, not on company identity. `language` and `tone` change independently of `email`.

**Third Normal Form (3NF):** Remove transitive dependencies — separate every independent concern:

| Table | Key | Single concern stored |
|-------|-----|-----------------------|
| **company** | id | Identity and credentials |
| **company_settings** | id (FK company_id) | AI configuration |
| **company_balance** | id (FK company_id) | Current credit state |
| **company_finetune** | id (FK company_id) | Knowledge base metadata |
| **api_key** | id | One API key per row |
| **balance_topup** | id | One credit event per row |
| **balance_deduct** | id | One charge event per row |
| **chat_message** | id | One conversation log |
| **company_requests** | id | One metered request |
| **ticket** | id | One support issue |
| **ticket_opened** | id | One open/close event |
| **khalti_payment** | id (pidx unique) | One gateway attempt |

✔ No repeating groups (1NF). ✔ No partial dependencies (2NF). ✔ No transitive dependencies (3NF). Each fact is stored exactly once.

**ER Diagram:**

```mermaid
erDiagram
    COMPANY ||--o{ API_KEY : owns
    COMPANY ||--|| COMPANY_FINETUNE : has
    COMPANY ||--|| COMPANY_SETTINGS : configures
    COMPANY ||--|| COMPANY_BALANCE : holds
    COMPANY ||--o{ BALANCE_TOPUP : receives
    COMPANY ||--o{ BALANCE_DEDUCT : "is charged"
    COMPANY ||--o{ CHAT_MESSAGE : logs
    COMPANY ||--o{ COMPANY_REQUESTS : makes
    COMPANY ||--o{ TICKET : opens
    COMPANY ||--o{ KHALTI_PAYMENT : "pays via"
    TICKET ||--o{ TICKET_OPENED : "has history"
    CHAT_MESSAGE ||--o{ BALANCE_DEDUCT : "priced by"

    COMPANY {
        int id PK
        varchar company_name
        varchar company_email UK
        varchar password_hash
        varchar logo
        varchar website
        datetime created_at
        datetime updated_at
    }
    API_KEY {
        int id PK
        int company_id FK
        varchar name
        varchar key_hash
        varchar key_preview
        varchar status
        datetime expiry_date
        datetime last_used_at
    }
    COMPANY_FINETUNE {
        int id PK
        int company_id FK
        varchar company_model_name
        varchar rag_company_path
    }
    COMPANY_SETTINGS {
        int id PK
        int company_id FK
        varchar language
        varchar tone
        int max_tokens
    }
    COMPANY_BALANCE {
        int id PK
        int company_id FK
        numeric balance
        datetime updated_at
    }
    BALANCE_TOPUP {
        int id PK
        int company_id FK
        numeric amount
        varchar reference
        datetime created_at
    }
    BALANCE_DEDUCT {
        int id PK
        int company_id FK
        int chat_message_id FK
        varchar session_id
        numeric amount
        int token_consume
        varchar model_name
    }
    CHAT_MESSAGE {
        int id PK
        int company_id FK
        varchar session_id
        text conversation
        int token_consume
        varchar model_name
    }
    TICKET {
        int id PK
        int company_id FK
        text issue
        varchar category
        varchar status
    }
    TICKET_OPENED {
        int id PK
        int company_id FK
        int ticket_id FK
        datetime opened_at
        datetime closed_at
    }
    KHALTI_PAYMENT {
        int id PK
        int company_id FK
        varchar pidx UK
        numeric amount_usd
        int amount_npr_paisa
        varchar status
        varchar transaction_id
    }
```
