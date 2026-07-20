# Chapter 3 — System Design

## 3.1 System Architecture

Perai follows a **three-tier architecture** with clearly separated presentation, application,
and data layers. External services (LLM provider and payment gateway) are integrated at the
application layer.

```mermaid
flowchart TB
    subgraph P [Presentation Tier]
        W[Company Dashboard<br/>Next.js + shadcn/ui]
        WD[Embeddable Chat Widget]
        EXT[3rd-party Integrations<br/>REST + X-API-Key]
    end

    subgraph A [Application Tier — FastAPI]
        AUTH[Auth Module<br/>JWT + bcrypt]
        KB[Finetune Module<br/>JSONL upload + RAG store]
        CHAT[Chat Module<br/>prompt build + streaming]
        BILL[Billing Module<br/>reserve/deduct/top-up]
        KHM[Khalti Module<br/>initiate + verify]
        TIC[Ticket Module]
        KEY[API Key Module]
    end

    subgraph D [Data Tier]
        PG[(PostgreSQL<br/>SQLAlchemy + Alembic)]
        FS[/Per-company RAG files<br/>BM25 keyword index/]
    end

    subgraph X [External Services]
        GROQ[Groq LLM API]
        KHALTI[Khalti ePayment API]
        TTS[Supertonic TTS<br/>ONNX, optional]
    end

    W --> AUTH & KB & BILL & TIC & KEY
    WD --> CHAT
    EXT --> CHAT & KB
    CHAT --> KB
    CHAT --> BILL
    CHAT --> GROQ
    CHAT --> TTS
    BILL --> KHM --> KHALTI
    AUTH & KB & CHAT & BILL & TIC & KEY --> PG
    KB --> FS
```

## 3.2 System Flowcharts

### 3.2.1 Company Registration and Login Flow

```mermaid
flowchart TD
    S([Start]) --> R[Company fills registration form]
    R --> V{Input valid?}
    V -- No --> RE[Show validation error] --> R
    V -- Yes --> H[Hash password - bcrypt]
    H --> C[Create company row]
    C --> B[Create starting balance account]
    B --> K[Issue default API key]
    K --> L[Login: verify email + password]
    L --> OK{Credentials<br/>correct?}
    OK -- No --> LE[Show login error] --> L
    OK -- Yes --> J[Issue JWT access token]
    J --> D[Redirect to dashboard]
    D --> E([End])
```

### 3.2.2 Knowledge Base (Finetune) Upload Flow

```mermaid
flowchart TD
    S([Start]) --> U[Admin uploads JSONL file]
    U --> P{Each line valid<br/>JSON object?}
    P -- No --> ER[Reject with line error] --> U
    P -- Yes --> M{Mode?}
    M -- Replace --> DEL[Delete old knowledge file]
    M -- Append --> KEEP[Keep existing records]
    DEL --> WFile[Write normalized records<br/>to company RAG file]
    KEEP --> WFile
    WFile --> UP[Upsert company_finetune row<br/>model name + file path]
    UP --> OKC[Knowledge instantly live<br/>for chat retrieval]
    OKC --> E([End])
```

### 3.2.3 AI Chat Request Flow (with metering)

```mermaid
flowchart TD
    S([Start]) --> RQ[Receive chat message<br/>with X-API-Key]
    RQ --> AK{API key valid<br/>and active?}
    AK -- No --> E401[401 Unauthorized] --> X([End])
    AK -- Yes --> RES{Reserve estimated<br/>credits OK?}
    RES -- No --> E402[Insufficient balance error] --> X
    RES -- Yes --> RET[Retrieve top-K knowledge<br/>BM25 + exact match]
    RET --> PB[Build prompt:<br/>tone + language + context]
    PB --> LLM[Call Groq LLM<br/>stream tokens to client]
    LLM --> OKQ{LLM call<br/>succeeded?}
    OKQ -- No --> REL[Release reservation] --> EFail[Return error] --> X
    OKQ -- Yes --> FIN[Finalize: refund unused or<br/>deduct extra credits]
    FIN --> LOG[Log chat_message +<br/>balance_deduct rows]
    LOG --> AUD{Audio requested?}
    AUD -- Yes --> T[Generate TTS WAV<br/>return base64]
    AUD -- No --> RESP[Return reply]
    T --> RESP
    RESP --> X
```

### 3.2.4 Khalti Balance Top-up Flowchart

```mermaid
flowchart TD
    S([Start]) --> SEL[Admin selects credit package<br/>e.g. USD 5 / 10 / 25]
    SEL --> INIT[Backend: POST epayment/initiate<br/>amount converted USD → NPR paisa]
    INIT --> ROW[Store khalti_payment row<br/>pidx, amount, status=Initiated]
    ROW --> REDIR[Redirect browser to<br/>Khalti payment_url]
    REDIR --> PAY{User completes<br/>payment on Khalti?}
    PAY -- Cancels --> BACK1[Redirect back] --> VER
    PAY -- Pays --> BACK2[Redirect back with pidx] --> VER
    VER[Backend: POST epayment/lookup<br/>with pidx]
    VER --> ST{Lookup status}
    ST -- Completed --> AMT{Paid amount ==<br/>initiated amount?}
    ST -- Pending/Expired/<br/>Canceled --> NOC[No credit<br/>show status] --> E([End])
    AMT -- No --> MIS[Flag AmountMismatch<br/>no credit] --> E
    AMT -- Yes --> DUP{Already credited<br/>for this pidx?}
    DUP -- Yes --> SKIP[Skip crediting<br/>idempotent] --> SHOW
    DUP -- No --> CR[Credit USD balance +<br/>record balance_topup<br/>reference = khalti:pidx] --> SHOW
    SHOW[Show new balance and<br/>top-up in history] --> E
```

## 3.3 Data Flow Diagrams

### 3.3.1 DFD Level 0 (Context Diagram)

```mermaid
flowchart LR
    CO[Company Admin] -- "registration, login,<br/>JSONL upload, settings,<br/>top-up request" --> SYS((0<br/>Perai<br/>Platform))
    SYS -- "JWT, dashboard data,<br/>balance, payment URL" --> CO

    EU[End User] -- "chat message" --> SYS
    SYS -- "AI reply / audio" --> EU

    KH[Khalti Gateway] -- "payment status<br/>(lookup response)" --> SYS
    SYS -- "initiate payment<br/>(amount, return URL)" --> KH

    LLM[Groq LLM API] -- "generated tokens" --> SYS
    SYS -- "prompt + context" --> LLM
```

### 3.3.2 DFD Level 1

```mermaid
flowchart TB
    CO[Company Admin]
    EU[End User]
    KH[Khalti Gateway]
    LLM[Groq LLM]

    P1((1.0<br/>Authentication))
    P2((2.0<br/>Knowledge Base<br/>Management))
    P3((3.0<br/>Chat<br/>Processing))
    P4((4.0<br/>Billing &<br/>Metering))
    P5((5.0<br/>Khalti<br/>Payment))
    P6((6.0<br/>Ticket<br/>Management))

    D1[(D1 company)]
    D2[(D2 api_key)]
    D3[(D3 company_finetune<br/>+ RAG files)]
    D4[(D4 chat_message)]
    D5[(D5 company_balance<br/>/ topup / deduct)]
    D6[(D6 khalti_payment)]
    D7[(D7 ticket)]

    CO -- credentials --> P1
    P1 -- verify/store --> D1
    P1 -- issue default key --> D2
    P1 -- JWT --> CO

    CO -- JSONL records --> P2
    P2 -- write records --> D3
    P2 -- upload result --> CO

    EU -- message + API key --> P3
    P3 -- validate key --> D2
    P3 -- retrieve context --> D3
    P3 -- prompt --> LLM
    LLM -- reply tokens --> P3
    P3 -- log message --> D4
    P3 -- reserve/finalize --> P4
    P3 -- AI reply --> EU

    P4 -- read/update balance --> D5

    CO -- select package --> P5
    P5 -- initiate --> KH
    KH -- payment status --> P5
    P5 -- payment record --> D6
    P5 -- credit on success --> P4
    P5 -- new balance --> CO

    CO -- issue/reply --> P6
    P6 -- store ticket --> D7
    P6 -- status updates --> CO
```

### 3.3.3 DFD Level 2 — Process 3.0 (Chat Processing)

```mermaid
flowchart TB
    EU[End User]
    LLM[Groq LLM]

    P31((3.1<br/>Validate<br/>API Key))
    P32((3.2<br/>Reserve<br/>Credits))
    P33((3.3<br/>Retrieve<br/>Context BM25))
    P34((3.4<br/>Build Prompt<br/>tone + language))
    P35((3.5<br/>Generate &<br/>Stream Reply))
    P36((3.6<br/>Finalize Cost<br/>& Log))

    D2[(D2 api_key)]
    D3[(D3 RAG files)]
    D8[(D8 company_settings)]
    D4[(D4 chat_message)]
    D5[(D5 balance tables)]

    EU -- message + key --> P31
    P31 -- key hash lookup --> D2
    P31 -- company id --> P32
    P32 -- reserve --> D5
    P32 -- ok --> P33
    P33 -- top-K records --> D3
    P33 -- context --> P34
    P34 -- tone/language/max tokens --> D8
    P34 -- final prompt --> P35
    P35 -- prompt --> LLM
    LLM -- tokens --> P35
    P35 -- streamed reply --> EU
    P35 -- usage counts --> P36
    P36 -- refund/deduct --> D5
    P36 -- store message + cost --> D4
```

### 3.3.4 DFD Level 2 — Process 5.0 (Khalti Payment)

```mermaid
flowchart TB
    CO[Company Admin]
    KH[Khalti Gateway]

    P51((5.1<br/>Initiate<br/>Payment))
    P52((5.2<br/>Redirect &<br/>Collect pidx))
    P53((5.3<br/>Verify via<br/>Lookup API))
    P54((5.4<br/>Idempotent<br/>Credit))

    D6[(D6 khalti_payment)]
    D5[(D5 company_balance<br/>+ balance_topup)]

    CO -- package amount USD --> P51
    P51 -- "USD → NPR paisa, initiate" --> KH
    KH -- pidx + payment_url --> P51
    P51 -- store Initiated row --> D6
    P51 -- payment_url --> P52
    P52 -- redirect --> CO
    CO -- returns with pidx --> P53
    P53 -- lookup pidx --> KH
    KH -- status + amount --> P53
    P53 -- update status --> D6
    P53 -- "Completed + amount OK" --> P54
    P54 -- check reference khalti:pidx --> D5
    P54 -- credit once + topup row --> D5
    P54 -- new balance --> CO
```

## 3.4 Interface Design (Dashboard Pages)

| Page | Purpose |
|------|---------|
| `/login`, `/register` | Authentication |
| `/dashboard` | Overview: balance, model status, API keys, recent activity |
| `/finetune` | Knowledge base upload (JSONL) and current finetune details |
| `/models` | Company model name and status |
| `/chat` | In-dashboard test chat |
| `/sessions` | Chat session history |
| `/balance` | Balance, credit packages, **Khalti payment**, top-up history |
| `/usages` | Token/credit consumption records |
| `/integration` | Code snippets (TypeScript / Python / cURL) |
| `/widget` | Embeddable widget snippet generator |
| `/api` | API key management |
| `/settings` | Tone, language, max tokens |
| `/ticket` | Support tickets |
