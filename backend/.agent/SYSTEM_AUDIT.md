# PERAI System Audit

**Last Updated:** June 12, 2026
**Audit Scope:** Backend (FastAPI) + Frontend (Next.js) — Full Stack
**Status:** ⚠️ NOT PRODUCTION READY — Critical issues found

---

## 1. Executive Summary

Perai is a multi-tenant AI platform (FastAPI + Next.js 16) with Groq LLM chat, RAG finetuning, API keys, tickets, usage tracking, and a company dashboard. The feature skeleton is largely complete, but **critical security gaps, broken migrations, and several broken/incomplete frontend pages** prevent production deployment.

---

## 2. Architecture Overview

```
flowchart TB
    subgraph Frontend["Next.js 16 Frontend"]
        Pages[App Router Pages]
        Session[sessionStorage Auth]
        Services[Service Layer]
    end

    subgraph Backend["FastAPI Backend"]
        Routes[API Routes /api/v1/]
        ServicesB[Business Services]
        ORM[SQLAlchemy Models]
        RAG[RAG / Finetune]
        Groq[Groq LLM]
    end

    subgraph Storage
        PG[(PostgreSQL / Supabase)]
        FS[Local File Storage]
    end

    Pages --> Session
    Pages --> Services
    Services -->|fetch + X-API-Key header| Routes
    Routes --> ServicesB
    ServicesB --> ORM
    ServicesB --> RAG
    ServicesB --> Groq
    ORM --> PG
    RAG --> FS
    ServicesB --> FS
```

---

## 3. Backend Audit

### 3.1 Infrastructure

| Component | Status | Details |
|-----------|--------|---------|
| Database Engine | ✅ | PostgreSQL 17.6 on Supabase |
| SQLAlchemy | ✅ | v2.x, connection pooling, `get_db` dependency |
| FastAPI | ✅ | Async, lifespan startup, auto OpenAPI docs |
| Groq LLM | ✅ | `llama-3.3-70b-versatile`, streaming SSE |
| RAG/Finetune | ✅ | BM25 retrieval, per-company markdown files |
| Alembic | ⚠️ | Migrations broken — see section 3.4 |
| CORS | ❌ | `allow_origins=["*"]` + `allow_credentials=True` (invalid combo) |
| Auth Middleware | ❌ | No global middleware; most routes unprotected |

### 3.2 API Endpoints

Base URL: `http://localhost:8000`

| Method | Path | Auth | Status |
|--------|------|------|--------|
| `GET` | `/` | — | Health check |
| **Auth** | | | |
| `POST` | `/api/v1/auth/register` | No | ✅ |
| `POST` | `/api/v1/auth/login` | No | ✅ |
| `GET` | `/api/v1/auth/verify/{company_id}` | No | ✅ |
| **Company** | | | |
| `POST` | `/api/v1/company` | ❌ None | Duplicate of register |
| `GET` | `/api/v1/company` | ❌ None | Lists ALL companies — data leak |
| `GET` | `/api/v1/company/{id}` | ❌ None | ✅ functional |
| `PUT` | `/api/v1/company/{id}` | ❌ None | ✅ functional |
| `DELETE` | `/api/v1/company/{id}` | ❌ None | ✅ functional |
| `POST/GET/DELETE` | `/api/v1/company/{id}/finetune` | ❌ None | ✅ functional |
| **Dashboard** | | | |
| `GET` | `/api/v1/company/{id}/dashboard` | ❌ None | ✅ functional |
| **API Keys** | | | |
| `POST/GET` | `/api/v1/company/{id}/api-keys` | ❌ None | Anyone can create keys for any company |
| `GET/PUT/DELETE` | `/api/v1/company/{id}/api-keys/{key_id}` | ❌ None | ✅ functional |
| `POST` | `/api/v1/company/{id}/api-keys/{key_id}/revoke` | ❌ None | ✅ functional |
| **Chat** | | | |
| `POST` | `/api/v1/company/{id}/chat/stream` | ❌ None | SSE streaming |
| `POST` | `/api/v1/company/{id}/chat/query` | ❌ None | Non-streaming + usage tracking |
| `POST` | `/api/v1/company/{id}/prompt/preview` | ❌ None | System prompt preview |
| `GET` | `/api/v1/company/{id}/chat/ping` | No | ✅ |
| **Tickets** | | | |
| `POST/GET` | `/api/v1/company/{id}/tickets` | ❌ None | ✅ functional |
| `GET/PUT/DELETE` | `/api/v1/company/{id}/tickets/{ticket_id}` | ❌ None | ✅ functional |
| `GET` | `/api/v1/company/{id}/tickets/{ticket_id}/history` | ❌ None | ✅ functional |
| `GET` | `/api/v1/company/{id}/tickets-stats` | ❌ None | ✅ functional |
| **Company Settings** | | | |
| `POST/GET/PUT/DELETE` | `/api/v1/company/{id}/settings` | ✅ `X-API-Key` | ✅ functional |
| **Files** | | | |
| `POST` | `/api/v1/files/companies/{id}/logo` | ⚠️ Broken | `verify_api_key` returns `int`, code expects `dict` — **crashes** |
| `GET` | `/api/v1/files/companies/{id}/logo` | ❌ None | ✅ functional |
| `POST` | `/api/v1/files/companies/{id}/content` | ⚠️ Broken | Same crash as logo upload |
| `GET` | `/api/v1/files/companies/{id}/list` | ⚠️ Broken | Same crash |
| **Company Requests** | | | |
| `GET` | `/api/v1/company/{id}/requests` | ❌ None | ✅ functional |

### 3.3 Database Models

| Model | Table | Key Fields | Status |
|-------|-------|------------|--------|
| `Company` | `company` | id, name, email, password_hash, logo, website | ✅ |
| `CompanyFinetune` | `company_finetune` | id, company_id (unique FK), company_model_name, rag_company_path | ✅ |
| `APIKey` | `api_key` | id, company_id, name, key_hash, key_preview, status, expiry_date | ✅ |
| `Ticket` | `ticket` | id, company_id, issue, category (enum), status (enum) | ⚠️ Migration broken |
| `TicketOpened` | `ticket_opened` | id, company_id, ticket_id, opened_at, closed_at | ⚠️ Migration broken |
| `CompanySettings` | `company_settings` | id, company_id (unique FK), language, tone, max_tokens | ⚠️ No migration |
| `CompanyRequest` | `company_requests` | id, company_id, token_consume, balance_deducted, ip, date | ✅ |
| `ChatMessage` | `chat_message` | id, company_id, session_id, conversation, review | ⚠️ No API routes |

**Model export gap:** `app/models/__init__.py` only exports `ChatMessage`, `Company`, `CompanyFinetune`, `CompanyRequest` — missing `Ticket`, `CompanySettings`, `APIKey`.

### 3.4 Alembic Migration Status

Migration chain: `0001` → `0002` → `f920af34db03` → `886ccfbe74e8` → `fix_ticket_defaults` → `4a625b86562b` → `20260605_company_requests` → `b9033b3a73a9`

| Migration | Issue |
|-----------|-------|
| `886ccfbe74e8_create_ticket_tables.py` | ❌ Body is just `pass` — creates nothing |
| `fix_ticket_defaults.py` | ❌ Alters columns on tables that don't exist |
| `4a625b86562b_create_company_settings_table.py` | ❌ **DROPS ticket and ticket_opened tables** (badly named) |
| `company_settings` | ❌ No migration exists at all |

**Result:** Running `alembic upgrade head` on a fresh DB drops ticket tables and never creates `company_settings`. Only `init_db()` `create_all` creates these tables, which conflicts with Alembic state tracking.

### 3.5 Core Utilities

| Module | Status | Notes |
|--------|--------|-------|
| `database.py` | ⚠️ | `init_db()` calls `create_all()` on startup — bypasses Alembic |
| `security.py` | ⚠️ | `verify_api_key` creates its own DB session when used as `Depends`, closes in `finally` — fragile |
| `api_key_utils.py` | ⚠️ | SHA-256 only, no per-key salt |
| `file_storage.py` | ❌ | Hardcoded absolute path `/home/prasanga/.../storage/` |
| `auth/service.py` | ❌ | Password format: `salt:hex(digest)` (100k iterations) |
| `company/service.py` | ❌ | Password format: `salt$hex(digest)` (200k iterations) — **incompatible** |
| `exceptions.py` | ❌ | Empty stub |
| `middleware.py` | ❌ | Empty stub |

---

## 4. Frontend Audit

### 4.1 Pages & Routes

| Route | Purpose | Status |
|-------|---------|--------|
| `/` | Root | ❌ Stub — no redirect to login/dashboard |
| `/login` | Company login | ✅ |
| `/register` | Company signup | ✅ |
| `/dashboard` | Usage metrics, credits, API keys | ✅ |
| `/chat` | AI chat (non-streaming) | ✅ |
| `/models` | Company model name | ✅ basic |
| `/finetune` | View/upload knowledge base | ✅ |
| `/settings` | AI tone/language/tokens | ✅ |
| `/api` | API key CRUD | ✅ |
| `/ticket` | Ticket list | ⚠️ No auth redirect |
| `/ticket/create` | Create ticket | ✅ |
| `/ticket/[id]` | Ticket detail | ✅ |
| `/profile` | Company profile + logo upload | ✅ |
| `/analytics` | Usage analytics | ❌ Empty file |
| `/config` | Legacy finetune config | ❌ Broken imports (deleted components) |

### 4.2 Auth Flow

```
POST /auth/login
  → Backend returns Company object (no JWT/token)
  → Frontend stores { companyId, apiKey: "" } in sessionStorage
  → Redirect to /api to create API key
  → API key returned once, stored in sessionStorage as { apiKey: "sk_..." }
  → All subsequent API calls send X-API-Key header
  → Backend mostly ignores the key (only companySettings verifies it)
```

**Gaps:**
- No JWT or server-side sessions
- No Next.js middleware for route protection
- Each page does its own `sessionStorage` check (inconsistent)
- API key in `sessionStorage` is XSS-accessible
- `/config` uses `localStorage` instead of `sessionStorage` (legacy inconsistency)

### 4.3 State Management

| Layer | Status |
|-------|--------|
| `store/index.ts` | ❌ Empty |
| `features/dashboard/` | ❌ Empty stubs |
| `features/user/` | ❌ Empty stubs |
| `features/auth/hooks.ts` | ✅ sessionStorage wrapper |
| Per-page `useState`/`useEffect` | ✅ Used throughout |

### 4.4 Services

| Service | Status |
|---------|--------|
| `auth.service.ts` | ✅ |
| `company.service.ts` | ✅ |
| `api-key.service.ts` | ✅ |
| `chat.service.ts` | ⚠️ `streamChat` incorrectly expects JSON; backend sends SSE |
| `ticket.service.ts` | ✅ |
| `companySettings.service.ts` | ✅ |
| `company/dashboard.ts` | ✅ |
| `file/uploadProfile.ts` | ✅ (backend upload endpoints broken) |
| `user.service.ts` | ❌ Types only, no implementation |

---

## 5. Critical Findings

### 🔴 Security (Must Fix Before Any Deployment)

1. **~90% of endpoints have no authentication** — Anyone can list all companies, delete companies, create API keys, run chat (Groq cost), read all usage data.
2. **`GET /api/v1/company` exposes all tenant data** — No pagination, no auth.
3. **API key creation unauthenticated** — Attacker can create keys for any `company_id`.
4. **Incompatible password hashing** — `auth/service.py` and `company/service.py` use different formats and iteration counts. Passwords may be unverifiable after updates.
5. **`CORS allow_origins=["*"]` + `allow_credentials=True`** — Browsers reject this; also insecure.
6. **File upload endpoints crash** — `verify_api_key` returns `int` but `files/route.py` treats it as `dict` → `TypeError` on every upload attempt.
7. **SHA-256 key hashing, no salt** — Fast to brute-force if DB is leaked.
8. **No rate limiting** — Login, registration, chat endpoints unlimited.
9. **Hardcoded absolute filesystem path** in `file_storage.py` — Breaks in any other environment.
10. **Chat/query has no auth** — Unlimited LLM API usage by anyone with a valid `company_id`.

### 🔴 Database (Must Fix)

11. **Migration `4a625b86562b` drops ticket tables** — Misnamed; contains `DROP TABLE` for `ticket` and `ticket_opened`.
12. **`company_settings` has no migration** — Only created via `init_db()` `create_all`.
13. **Ticket migration `886ccfbe74e8` is empty** (`pass`) — Tickets only exist via `create_all`.
14. **`init_db()` conflicts with Alembic** — Running `create_all` on startup can diverge DB state from migration tracking.
15. **`alembic/env.py` incomplete model imports** — Autogenerate misses several tables.

### 🟡 Code Quality

16. **`CompanyRead.from_orm()`** — Deprecated in Pydantic v2; use `model_validate`.
17. **`verify_api_key` Depends pattern broken** — Creates own session when called as a dependency.
18. **Duplicate registration paths** — `/auth/register` and `POST /api/v1/company`.
19. **N+1 in dashboard** — Loads all company_requests into memory instead of SQL aggregation.
20. **Mixed datetime handling** — Some models use `datetime.utcnow` (naive), others `func.now()` (TZ-aware).
21. **Empty stubs** — `exceptions.py`, `middleware.py`, `constants.py`, `store/index.ts`, `features/dashboard/*`, `features/user/*`.
22. **`ChatMessage` model has no API routes** — Migration + model + schema exist, no endpoints.

### 🟡 Frontend

23. **`/analytics` page empty** — `analyticsTable.tsx` also empty.
24. **`/config` page broken** — Imports deleted components (`settingsForm`, `promptPreview`).
25. **Root `/` is a stub** — Doesn't redirect to login or dashboard.
26. **Chat streaming not wired** — Backend has SSE endpoint; `streamChat` service incorrectly expects JSON.
27. **Dashboard shows `Invalid Date`** for API keys without an expiry date.
28. **Inconsistent auth guards** — Ticket list doesn't redirect unauthenticated users.
29. **No `error.tsx` / `loading.tsx` / `not-found.tsx`** in app routes.
30. **Widespread `any` types** — Dashboard, profile, chat session state.

---

## 6. Recommendations (Priority Order)

| # | Action | Priority |
|---|--------|----------|
| 1 | Add global auth `Depends` on all `/api/v1/company/*` routes | 🔴 Critical |
| 2 | Fix `files/route.py` — `verify_api_key` returns `int`, not `dict` | 🔴 Critical |
| 3 | Unify password hashing — single module, single format | 🔴 Critical |
| 4 | Fix Alembic migrations — rewrite `4a625b86562b`, add ticket + settings migrations | 🔴 Critical |
| 5 | Fix CORS — use explicit origin list, not `*` | 🔴 Critical |
| 6 | Remove or protect `GET /api/v1/company` list endpoint | 🔴 Critical |
| 7 | Remove `create_all()` from production startup lifespan | 🔴 Critical |
| 8 | Add JWT/httpOnly cookie sessions after login | 🟡 High |
| 9 | Add per-key salt to API key hashing | 🟡 High |
| 10 | Add rate limiting (login, register, chat, API key creation) | 🟡 High |
| 11 | Make file storage path configurable via env var | 🟡 High |
| 12 | Fix or delete broken frontend pages (`/config`, `/analytics`, `/`) | 🟡 High |
| 13 | Wire SSE streaming in frontend chat | 🟡 High |
| 14 | Add SQL aggregation in dashboard (avoid loading all rows) | 🟢 Medium |
| 15 | Add Next.js middleware for route protection | 🟢 Medium |
| 16 | Implement `ChatMessage` API routes or remove the model | 🟢 Medium |
| 17 | Replace `from_orm()` with `model_validate()` throughout | 🟢 Medium |
| 18 | Add `error.tsx` / `loading.tsx` to app routes | 🟢 Low |
| 19 | Write tests (zero coverage currently) | 🟢 Low |
| 20 | Create Docker setup and CI/CD pipeline | 🟢 Low |

---

## 7. Phase Checklist

### Phase 1: Security Hardening (Immediate)
- [ ] Add `Depends(verify_api_key)` to all company routes
- [ ] Validate that path `company_id` matches key owner in each route
- [ ] Fix CORS configuration
- [ ] Unify password hashing
- [ ] Fix `files/route.py` type mismatch
- [ ] Remove `GET /api/v1/company` or add admin auth
- [ ] Make storage path configurable

### Phase 2: Database Integrity
- [ ] Rewrite `4a625b86562b` migration to create `company_settings` (not drop tickets)
- [ ] Add proper ticket table migration (`886ccfbe74e8`)
- [ ] Remove `create_all()` from `init_db()` lifespan
- [ ] Fix `alembic/env.py` to import all models
- [ ] Verify migration chain applies cleanly on empty DB

### Phase 3: Auth & Sessions
- [ ] Implement JWT or signed sessions returned at login
- [ ] Add Next.js middleware for route protection
- [ ] Replace `sessionStorage` with httpOnly cookie auth
- [ ] Add rate limiting

### Phase 4: Frontend Fixes
- [ ] Fix or delete `/config` page (broken imports)
- [ ] Implement `/analytics` page
- [ ] Wire root `/` → `/login` redirect
- [ ] Wire SSE streaming in chat
- [ ] Fix `Invalid Date` on API key expiry display
- [ ] Add consistent auth guards across all pages
- [ ] Add `error.tsx` / `loading.tsx` to routes

### Phase 5: API Completeness
- [ ] Add `ChatMessage` API routes or remove model
- [ ] Implement `user.service.ts` logic
- [ ] Replace `from_orm()` with `model_validate()`
- [ ] Fix mixed datetime handling

### Phase 6: Quality & Deployment
- [ ] Write test suite (unit + integration)
- [ ] Add structured logging
- [ ] Create Dockerfile + docker-compose
- [ ] Setup CI/CD

---

## 8. Tech Stack Reference

| Layer | Technology |
|-------|-----------|
| Backend | FastAPI, SQLAlchemy 2.x, Alembic, Pydantic 2, Groq SDK |
| Database | PostgreSQL 17.6 (Supabase) |
| LLM | Groq `llama-3.3-70b-versatile`, streaming SSE |
| RAG | BM25 retrieval, disk-based markdown |
| Auth | Custom SHA-256 API keys (no JWT currently) |
| Frontend | Next.js 16, React 19, Tailwind 4, shadcn/Radix |
| State | sessionStorage (no global store) |
| File Storage | Local filesystem (hardcoded path) |

---

**Audited by:** Cursor Agent
**Date:** June 12, 2026
**Previous Audit:** May 22, 2026 (outdated — overstated readiness)
