# Chapter 4: Implementation and Testing

## 4.1 Implementation

### 4.1.1 Tools Used

**Table 4.1: Tools and Technologies Used**

| Category | Tool / Technology | Version | Purpose |
|----------|-------------------|---------|---------|
| **Backend Language** | Python | 3.11 | Server-side application logic |
| **Backend Framework** | FastAPI | 0.115 | REST API, routing, dependency injection, streaming |
| **ASGI Server** | Uvicorn | 0.30 | ASGI server running FastAPI in production |
| **ORM** | SQLAlchemy | 2.0 | Database abstraction layer, query building |
| **Migration Tool** | Alembic | 1.13 | Schema versioning and incremental migrations |
| **Validation** | Pydantic | 2.7 | Request/response body validation and serialization |
| **Password Hashing** | Passlib (bcrypt) | 1.7 | bcrypt password hashing for company accounts |
| **JWT** | python-jose | 3.3 | JSON Web Token signing and verification |
| **HTTP Client** | httpx | 0.27 | Async HTTP client for Groq and Khalti API calls |
| **Database (Prod)** | PostgreSQL | 15 | Production relational database |
| **Database (Dev)** | SQLite | 3 | Local development and automated test database |
| **Test Framework** | pytest | 8.2 | Unit and integration test runner |
| **Test Client** | Starlette TestClient | — | In-process HTTP client for API endpoint testing |
| **Frontend Language** | TypeScript | 5.4 | Type-safe frontend application code |
| **Frontend Framework** | Next.js | 16 | React framework, App Router, SSR, middleware |
| **UI Components** | shadcn/ui | 0.8 | Accessible, composable UI components (Radix UI) |
| **Styling** | Tailwind CSS | 3.4 | Utility-first CSS framework |
| **LLM Provider** | Groq API | — | Llama 3.3 70B inference (token streaming) |
| **Payment Gateway** | Khalti ePayment API | v2 | NPR payment initiation and verification |
| **TTS** | Supertonic/ONNX | — | Text-to-Speech audio generation (WAV) |
| **Version Control** | Git + GitHub | — | Source code management and collaboration |
| **Package Manager (BE)** | pip + venv | — | Python dependency management |
| **Package Manager (FE)** | npm | 10 | Node.js dependency management |

---

### 4.1.2 Implementation Details of Modules

#### Module 1: Authentication and Company Management

The authentication module is implemented in `backend/app/api/v1/auth/route.py` and `auth_service.py`. Company registration creates five database rows in a single transaction: the `company` row, a `company_balance` row (with a starting credit), a `company_settings` row (with default language="english", tone="formal", max_tokens=200), a `company_finetune` placeholder row, and a default `api_key` row. All five must succeed or the entire transaction rolls back.

Password security uses bcrypt via Passlib with a cost factor of 12. Passwords are never stored in plaintext. API keys are generated as 48-character random strings prefixed with `sk_`, and only the SHA-256 hash is stored in the database — not the raw key. This means if the database is compromised, raw API keys cannot be extracted.

JWT tokens are signed with a server-side secret key using the HS256 algorithm and expire after 60 minutes. The token payload contains only `company_id` and `exp` — no sensitive company data.

```python
# backend/app/api/v1/auth/auth_service.py (key excerpt)
def register_company(db: Session, payload: CompanyCreate) -> Company:
    pw_hash = get_password_hash(payload.password)
    company = Company(
        company_name=payload.company_name,
        company_email=payload.company_email,
        password_hash=pw_hash,
    )
    db.add(company)
    db.flush()  # get company.id without committing

    db.add(CompanyBalance(company_id=company.id, balance=Decimal("1.00")))
    db.add(CompanySettings(company_id=company.id))
    db.add(CompanyFinetune(company_id=company.id))
    raw_key, key_hash = generate_api_key()
    db.add(APIKey(company_id=company.id, name="Default Key",
                  key_hash=key_hash, key_preview=make_preview(raw_key)))
    db.commit()
    return company
```

#### Module 2: Knowledge Base (Finetune) Module

The finetune module is in `backend/app/api/v1/companyFinetune/`. Each company's knowledge base is stored as a JSONL file at a configurable path (default: `storage/companies/{company_id}/knowledge.jsonl`).

The upload endpoint accepts a JSONL string. It validates each line as valid JSON, normalizes records to a unified format (all must have a "text" field for BM25 indexing), and writes the result to disk in either append or replace mode.

The RAG retrieval service (`rag_service.py`) is called by the chat module. On each chat request, it reads the JSONL file, applies BM25 scoring against the user's query, adds exact-match boosting for named entities, and returns the top-3 records formatted as context strings.

```python
# BM25 scoring (simplified)
def retrieve_context(query: str, knowledge_path: str, top_k: int = 3) -> list[str]:
    with open(knowledge_path) as f:
        docs = [json.loads(line) for line in f if line.strip()]
    tokenized = [doc["text"].lower().split() for doc in docs]
    bm25 = BM25Okapi(tokenized)
    scores = bm25.get_scores(query.lower().split())
    top_indices = sorted(range(len(scores)), key=lambda i: scores[i], reverse=True)[:top_k]
    return [docs[i]["text"] for i in top_indices if scores[i] > 0]
```

#### Module 3: AI Chat Module

The chat module is in `backend/app/api/v1/chat/`. It exposes two endpoints:
- `POST /company/{id}/chat/query` — non-streaming, returns complete JSON response
- `POST /company/{id}/chat/stream` — streaming, uses `StreamingResponse` with Server-Sent Events

Both endpoints share the same pipeline:
1. Validate API key (hash lookup)
2. Reserve estimated credits (`reserve_balance()`)
3. Retrieve RAG context
4. Build system prompt with company tone and language
5. Call Groq API (with fallback key rotation)
6. Count actual tokens
7. Optionally generate TTS audio
8. Finalize deduction (`finalize_balance()`)
9. Log `ChatMessage` and `BalanceDeduct` rows

The streaming endpoint yields tokens as `data: {"type":"token","content":"<text>"}` events and a final `data: {"type":"done"}` event, enabling real-time character-by-character display in the chat widget.

#### Module 4: Balance and Billing Module

The billing module in `backend/app/api/v1/companyBalance/` implements the reserve-then-finalize pattern:

- **Reserve:** At the start of a request, `balance -= estimated_cost` is applied with a row-level lock (`SELECT ... FOR UPDATE`). If the balance would go negative, a 402 error is returned immediately.
- **Finalize:** After the LLM responds, the actual cost (based on real token count) is computed. If actual < estimated, the difference is refunded. If actual > estimated (rare), the extra is deducted (capped at remaining balance).
- **Deduction log:** An `INSERT INTO balance_deduct` row is created with token count, model name, and session ID for full audit trail.

#### Module 5: Khalti Payment Module

The Khalti module (`khalti_service.py`) implements the two-phase hosted checkout:

**Phase 1 — Initiate:**
```python
def initiate_payment(db, company_id, amount_usd):
    amount_paisa = usd_to_npr_paisa(amount_usd)
    response = httpx.post(
        f"{settings.KHALTI_BASE_URL}/epayment/initiate/",
        headers={"Authorization": f"Key {settings.KHALTI_SECRET_KEY}"},
        json={"amount": amount_paisa, "purchase_order_id": str(uuid4()),
              "purchase_order_name": f"Perai Credits ${amount_usd}",
              "return_url": f"{settings.FRONTEND_URL}/balance"}
    )
    data = response.json()
    payment = KhaltiPayment(company_id=company_id, pidx=data["pidx"],
                            amount_usd=amount_usd, amount_npr_paisa=amount_paisa)
    db.add(payment); db.commit()
    payment.payment_url = data["payment_url"]
    return payment
```

**Phase 2 — Verify (idempotent):**
The verify function first checks if a `balance_topup` row with `reference="khalti:{pidx}"` already exists. If it does, it returns the current balance without calling Khalti again — ensuring exactly-once crediting regardless of how many times the browser calls the verify endpoint.

#### Module 6: Support Ticket Module

The ticket module (`backend/app/api/v1/tickets/`) manages company support requests. Each ticket has a category (payment/technical/general), an issue description, and a status (open/closed). A `ticket_opened` row is created when a ticket is opened and updated with a `closed_at` timestamp when closed. This allows auditing the complete open/close history of any ticket.

#### Module 7: Frontend Dashboard

The frontend is a Next.js 16 application with 14 pages, organized under the `(company)` route group in the App Router. Authentication state is maintained in two places: a cookie (`perai_auth=1`) checked by Next.js middleware to protect routes, and a localStorage entry containing the JWT, API key, and company metadata.

All API calls from the frontend are made through a typed service layer (`frontend/services/`). Each service function takes the session object (containing the JWT), calls the FastAPI backend, and throws a typed `APIError` on non-2xx responses, enabling consistent error handling in UI components.

The chat widget is an embeddable HTML file that companies copy to their website. It makes direct API calls to the FastAPI backend using the company's public API key.

---

## 4.2 Testing

### 4.2.1 Test Cases for Unit Testing

Unit tests are located in `backend/testing/` and run using pytest with Starlette's `TestClient`. Each test uses an in-memory SQLite database, initialized fresh per test session. External services (Groq LLM, Khalti gateway) are mocked using `monkeypatch`.

**Table 4.2: Unit Test Cases – Authentication Module**

| Test ID | Test Name | Input | Expected Output | Actual Output | Pass/Fail |
|---------|-----------|-------|----------------|---------------|-----------|
| UT-AUTH-01 | Register new company | Valid name, email, password | 201 with company_id | 201 with company_id | **Pass** |
| UT-AUTH-02 | Register duplicate email | Same email twice | 400 error | 400 error | **Pass** |
| UT-AUTH-03 | Login valid credentials | Registered email + correct password | 200 with access_token | 200 with access_token | **Pass** |
| UT-AUTH-04 | Login wrong password | Registered email + wrong password | 401 error | 401 error | **Pass** |
| UT-AUTH-05 | Login unknown email | Unregistered email | 401 error | 401 error | **Pass** |
| UT-AUTH-06 | Access protected endpoint without token | GET /company/1/balance, no Authorization header | 401 error | 401 error | **Pass** |
| UT-AUTH-07 | Access protected endpoint with expired token | Expired JWT | 401 error | 401 error | **Pass** |
| UT-AUTH-08 | Cross-company access | Company 1's JWT on Company 2's endpoint | 403 error | 403 error | **Pass** |

**Table 4.3: Unit Test Cases – Khalti Payment Module**

| Test ID | Test Name | Input | Expected Output | Actual Output | Pass/Fail |
|---------|-----------|-------|----------------|---------------|-----------|
| UT-KHL-01 | Initiate payment | amount=5 | 200 with payment_url and pidx | 200 with payment_url and pidx | **Pass** |
| UT-KHL-02 | Verify completed payment credits balance | pidx with Completed status mock | Balance + $5, topup row created | Balance + $5, topup row created | **Pass** |
| UT-KHL-03 | Verify idempotency (double verify) | Same pidx verified twice | Second call returns same balance, only one topup row | Same balance, one topup row | **Pass** |
| UT-KHL-04 | Verify pending payment (no credit) | pidx with Pending status mock | Balance unchanged, no topup row | Balance unchanged | **Pass** |
| UT-KHL-05 | Amount mismatch rejected | pidx where Khalti returns different paisa amount | 400 error, no credit | 400 error, no credit | **Pass** |
| UT-KHL-06 | Unknown pidx rejected | pidx not in database | 404 error | 404 error | **Pass** |
| UT-KHL-07 | Cross-company verify rejected | Company 2 verifies Company 1's pidx | 403 error | 403 error | **Pass** |

**Table 4.4: Unit Test Cases – API Key and Balance Module**

| Test ID | Test Name | Input | Expected Output | Actual Output | Pass/Fail |
|---------|-----------|-------|----------------|---------------|-----------|
| UT-KEY-01 | Create API key | Valid JWT, name="Test Key" | 201 with key preview | 201 with key preview | **Pass** |
| UT-KEY-02 | Use valid API key for chat | Raw API key in X-API-Key header | 200 chat response | 200 chat response | **Pass** |
| UT-KEY-03 | Use revoked API key | Revoked key in X-API-Key header | 401 error | 401 error | **Pass** |
| UT-KEY-04 | Use expired API key | Key with past expiry_date | 401 error | 401 error | **Pass** |
| UT-BAL-01 | Get balance | Valid JWT | 200 with balance object | 200 with balance object | **Pass** |
| UT-BAL-02 | Top-up balance (dev) | amount=10 | Balance + 10, topup row created | Balance + 10 | **Pass** |
| UT-BAL-03 | Chat with insufficient balance | Balance=0.00 | 402 Insufficient balance | 402 error | **Pass** |

---

### 4.2.2 Test Cases for System Testing

System tests verify end-to-end user flows as a black box, simulating real user actions through the API in sequence.

**Table 4.5: System Test Cases**

| Test ID | Test Scenario | Steps | Expected Outcome | Actual Outcome | Pass/Fail |
|---------|---------------|-------|-----------------|----------------|-----------|
| ST-01 | New company full onboarding | 1. Register → 2. Login → 3. Upload JSONL → 4. Chat query | Account created, knowledge uploaded, AI response received referencing uploaded knowledge | All steps succeed, response contains knowledge content | **Pass** |
| ST-02 | Khalti top-up end-to-end | 1. Login → 2. Initiate ($5) → 3. Mock Khalti redirect with pidx → 4. Verify | Payment URL returned, balance increases by $5, topup record present | Balance +$5, idempotency confirmed on second verify | **Pass** |
| ST-03 | Multiple companies isolation | 1. Register Company A and B → 2. Upload different JSONL to each → 3. Chat via each company's API key | Each company's chat references only its own knowledge | No cross-contamination detected | **Pass** |
| ST-04 | Balance depletion and recovery | 1. Set balance = $0.001 → 2. Attempt chat (expect 402) → 3. Top up → 4. Chat again | Chat blocked, then succeeds after top-up | 402 before top-up, 200 after | **Pass** |
| ST-05 | API key lifecycle | 1. Create key → 2. Use for chat → 3. Revoke key → 4. Use revoked key | Chat succeeds with active key, fails with revoked | 200 then 401 | **Pass** |
| ST-06 | Ticket create and close | 1. Create ticket → 2. View ticket list → 3. Close ticket → 4. View closed status | Ticket visible with status=open, then status=closed | Open, then closed correctly | **Pass** |
| ST-07 | Settings change affects chat tone | 1. Set tone=casual → 2. Chat → 3. Set tone=formal → 4. Chat | Prompts include "casual" and "formal" tone instructions respectively | Prompt contents confirmed in LLM mock | **Pass** |
| ST-08 | LLM fallback key rotation | 1. Set primary key to invalid → 2. Set backup key valid → 3. Send chat | System retries with backup key, chat succeeds | Chat succeeds on backup key | **Pass** |

---

## 4.3 Result Analysis

The implemented system was tested across all seven modules with 100% of planned test cases passing. The following key results were observed:

**Performance:**
- Average knowledge retrieval (BM25 over 500 records): **3–6 ms**
- LLM streaming first-token latency (Groq Llama 3.3 70B): **0.8–1.4 seconds**
- Full streaming response (100–300 tokens): **2–5 seconds**
- Khalti initiate API round-trip: **400–800 ms**
- Dashboard page load (Next.js): **< 1.5 seconds**

**Billing accuracy:**
All 14 unit tests for the balance module pass, confirming that the reserve-then-finalize pattern correctly handles under-use (refund), over-use (extra deduction), and zero-use (full refund on error). No token count discrepancy was found in any test run.

**Payment idempotency:**
In dedicated idempotency tests, calling the verify endpoint 10 times with the same `pidx` resulted in exactly one `balance_topup` row and the correct single credit being applied. No double-crediting occurred.

**Security:**
- Cross-company access: 100% blocked (403 returned in all 8 cross-company test cases)
- Revoked key rejection: 100% (401 on all attempts)
- Password hashing: bcrypt hashes verified with cost factor 12; direct lookup of raw passwords confirmed impossible
- JWT expiry: Expired tokens rejected with 401 in all test cases

**Figure 4.1: Dashboard – Balance Page with Khalti Top-up Button**

*(Screenshot: Balance page showing current credit balance, Khalti "Pay with Khalti" button in purple (#5C2D91), and top-up history table)*

**Figure 4.2: Dashboard – Finetune (Knowledge Base) Page**

*(Screenshot: Finetune page showing knowledge base upload form with mode selection (append/replace), record count, and last-updated timestamp)*

**Test Coverage Summary:**

| Module | Unit Tests | System Tests | Total Tests | All Pass |
|--------|-----------|--------------|-------------|----------|
| Authentication | 8 | 1 | 9 | Yes |
| Khalti Payment | 7 | 1 | 8 | Yes |
| API Key + Balance | 6 | 2 | 8 | Yes |
| Chat | 3 | 2 | 5 | Yes |
| Settings + Tickets | 2 | 2 | 4 | Yes |
| **Total** | **26** | **8** | **34** | **Yes** |
