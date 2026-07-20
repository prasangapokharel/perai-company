# Chapter 5 — Implementation and Testing

## 5.1 Tools and Technologies

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Backend | Python 3, **FastAPI** | REST API, dependency-injected auth, streaming responses |
| ORM / DB | **SQLAlchemy**, **Alembic**, **PostgreSQL** (SQLite for tests) | Data persistence and versioned migrations |
| Frontend | **Next.js 16** (React, TypeScript), **shadcn/ui**, Tailwind CSS | Company dashboard and chat widget |
| AI | **Groq API** (Llama 3.3 70B) | LLM inference with key-rotation fallback |
| Retrieval | File-based **BM25** + exact ID/name matching | Vectorless RAG over per-company JSONL knowledge |
| TTS | **Supertonic** (ONNX) | Optional WAV audio replies |
| Payment | **Khalti ePayment API v2** | NPR payment for USD credit top-ups |
| Auth | **JWT** (dashboard), hashed **API keys** (integrations), bcrypt | Security |
| Testing | **pytest**, Starlette TestClient, monkeypatch | Automated end-to-end API tests |
| Tooling | Git/GitHub, ESLint, Prettier, mypy, uvicorn | Quality and delivery |

## 5.2 Module Implementation Overview

| Module | Key files (backend) | Notes |
|--------|--------------------|-------|
| Auth | `app/api/v1/auth/` | Register, login (JWT), `auth/me`; default API key bootstrap |
| Knowledge base | `app/api/v1/company/` + `app/core/finetune/` | JSONL parse/normalize, append/replace, per-company RAG file |
| Chat | `app/api/v1/chat/` | Reserve → retrieve → prompt → stream → finalize pipeline |
| Billing | `app/api/v1/balance/service.py` | `reserve_balance`, `finalize_deduction`, `topup_balance` (row-locked) |
| Khalti | `app/api/v1/companyBalance/khalti_service.py` | `initiate_payment`, `verify_payment` (idempotent crediting) |
| API keys | `app/api/v1/apikey/` | SHA-hashed storage, preview, revoke, expiry |
| Tickets | `app/api/v1/ticket/` | CRUD + open/close history |

### Khalti Integration (implementation detail)

```text
1. POST /api/v1/companyBalance/{id}/khalti/initiate  {amount: 5}
   → converts USD → NPR paisa (configurable rate, default 140)
   → calls {KHALTI_BASE_URL}/epayment/initiate/
   → stores khalti_payment(pidx, status=Initiated)
   → returns payment_url  (browser redirects there)

2. Khalti redirects back to {FRONTEND_URL}/balance?pidx=...

3. POST /api/v1/companyBalance/{id}/khalti/verify  {pidx}
   → calls {KHALTI_BASE_URL}/epayment/lookup/
   → status Completed + amount match → topup_balance(reference="khalti:"+pidx)
   → unique reference makes crediting exactly-once (idempotent)
```

Configuration (`backend/.env`):

```env
KHALTI_SECRET_KEY=<secret key from Khalti merchant dashboard>
KHALTI_BASE_URL=https://dev.khalti.com/api/v2   # sandbox; use a2z.khalti.com for live
KHALTI_USD_TO_NPR=140
```

## 5.3 Testing

Testing was performed at three levels: automated API tests (pytest), manual sandbox payment
testing, and static type checking (`mypy` backend, `tsc` frontend).

### 5.3.1 Test Case Table

| TC | Description | Input | Expected Result | Actual | Status |
|----|-------------|-------|----------------|--------|--------|
| TC01 | Company registration | Valid name/email/password | 201, company created, starting balance issued | As expected | Pass |
| TC02 | Duplicate registration | Existing email | 400 error | As expected | Pass |
| TC03 | Login | Valid credentials | 200, JWT + company profile | As expected | Pass |
| TC04 | Login wrong password | Invalid password | 401 error | As expected | Pass |
| TC05 | Finetune fetch, fresh account | GET /finetune before upload | 404 → UI shows "No knowledge base yet" empty state | As expected | Pass |
| TC06 | JSONL upload | Valid Q/A lines, append mode | 200, records live for retrieval | As expected | Pass |
| TC07 | Chat query | Message + valid API key | Streamed grounded reply; deduction recorded | As expected | Pass |
| TC08 | Chat with invalid key | Bad X-API-Key | 401 error, no charge | As expected | Pass |
| TC09 | Cross-company access | Company A token on Company B resource | 403 Forbidden | As expected | Pass |
| TC10 | Khalti initiate | amount = 5 USD | 200, `pidx` + `payment_url`, NPR 700.00 (70000 paisa) | As expected | Pass |
| TC11 | Khalti verify — completed | pidx of completed payment | Balance +$5, top-up row `khalti:<pidx>` | As expected | Pass |
| TC12 | Khalti verify — repeated | Same pidx verified twice | Second call credits **nothing** (idempotent) | As expected | Pass |
| TC13 | Khalti verify — pending | pidx of unpaid payment | Status `Pending`, balance unchanged | As expected | Pass |
| TC14 | Khalti amount mismatch | Lookup amount ≠ initiated amount | 400, payment flagged `AmountMismatch`, no credit | As expected | Pass |
| TC15 | Khalti unknown pidx | Random pidx | 400 "Unknown payment reference" | As expected | Pass |
| TC16 | Khalti cross-company verify | Company A verifying Company B's pidx | 403 Forbidden | As expected | Pass |

### 5.3.2 Automated Test Suite

The Khalti flow is covered by `backend/testing/e2e/test_khalti.py`, which mocks the Khalti
gateway (`httpx.post`) so the complete → credit → idempotency path runs deterministically
without a real payment:

```text
$ python -m pytest testing/e2e/test_khalti.py -v
test_khalti_initiate_returns_payment_url        PASSED
test_khalti_verify_completed_credits_once       PASSED
test_khalti_verify_pending_does_not_credit      PASSED
test_khalti_verify_amount_mismatch_rejected     PASSED
test_khalti_verify_unknown_pidx_rejected        PASSED
test_khalti_other_company_cannot_verify         PASSED
====================== 6 passed ======================
```

Live-sandbox behaviour (initiate against `dev.khalti.com`, redirect to
`test-pay.khalti.com`, lookup returning `Pending` before payment) was additionally verified
manually with the sandbox merchant key and Khalti's documented test wallet
(mobile `9800000000`–`9800000005`, MPIN `1111`, OTP `987654`).

### 5.3.3 Static Verification

- `npx tsc --noEmit` — frontend compiles with zero TypeScript errors.
- All 14 protected dashboard pages render HTTP 200 behind the auth middleware.
