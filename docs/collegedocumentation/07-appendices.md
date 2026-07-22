# Appendices

## Appendix A: Key Source Code Listings

### A.1 Khalti Payment Service

```python
# backend/app/api/v1/companyBalance/khalti_service.py

from decimal import Decimal
from uuid import uuid4
import httpx
from sqlalchemy.orm import Session

from app.core.config.config import settings
from app.models.khalti_payment import KhaltiPayment
from app.models.balanceTopup import BalanceTopup
from app.api.v1.companyBalance.balance_service import topup_balance, get_balance


def usd_to_npr_paisa(amount_usd: Decimal) -> int:
    """Convert USD amount to NPR paisa using configured exchange rate."""
    rate = Decimal(str(settings.KHALTI_USD_TO_NPR))
    paisa = amount_usd * rate * 100
    return int(paisa.quantize(Decimal("1")))


def initiate_payment(db: Session, company_id: int, amount_usd: Decimal) -> KhaltiPayment:
    """Initiate a Khalti hosted payment and store the payment record."""
    amount_paisa = usd_to_npr_paisa(amount_usd)
    purchase_order_id = str(uuid4())

    response = httpx.post(
        f"{settings.KHALTI_BASE_URL}/epayment/initiate/",
        headers={"Authorization": f"Key {settings.KHALTI_SECRET_KEY}"},
        json={
            "return_url": f"{settings.FRONTEND_URL}/balance",
            "website_url": settings.FRONTEND_URL,
            "amount": amount_paisa,
            "purchase_order_id": purchase_order_id,
            "purchase_order_name": f"Perai Credits ${amount_usd}",
        },
        timeout=15,
    )
    response.raise_for_status()
    data = response.json()

    payment = KhaltiPayment(
        company_id=company_id,
        pidx=data["pidx"],
        amount_usd=amount_usd,
        amount_npr_paisa=amount_paisa,
        status="Initiated",
    )
    db.add(payment)
    db.commit()
    db.refresh(payment)
    payment.payment_url = data.get("payment_url", "")
    return payment


def verify_payment(db: Session, company_id: int, pidx: str) -> KhaltiPayment:
    """Verify a Khalti payment and idempotently credit company balance."""
    payment = (
        db.query(KhaltiPayment)
        .filter(KhaltiPayment.pidx == pidx, KhaltiPayment.company_id == company_id)
        .first()
    )
    if not payment:
        raise ValueError(f"Unknown payment reference: {pidx}")

    # Idempotency check: already credited?
    reference = f"khalti:{pidx}"
    already_credited = (
        db.query(BalanceTopup)
        .filter(BalanceTopup.reference == reference)
        .first()
    )
    if already_credited:
        payment.status = "Completed"
        db.commit()
        return payment

    # Call Khalti lookup API
    response = httpx.post(
        f"{settings.KHALTI_BASE_URL}/epayment/lookup/",
        headers={"Authorization": f"Key {settings.KHALTI_SECRET_KEY}"},
        json={"pidx": pidx},
        timeout=15,
    )
    response.raise_for_status()
    data = response.json()

    status = data.get("status", "Unknown")
    payment.transaction_id = data.get("transaction_id")
    payment.status = status

    if status != "Completed":
        db.commit()
        return payment

    # Amount verification
    if data.get("total_amount") != payment.amount_npr_paisa:
        payment.status = "AmountMismatch"
        db.commit()
        raise ValueError("Paid amount does not match the initiated amount")

    # Credit balance (within same transaction as topup row)
    topup_balance(db, company_id, payment.amount_usd, reference=reference)
    db.commit()
    return payment
```

### A.2 BM25 RAG Retrieval

```python
# backend/app/api/v1/companyFinetune/rag_service.py (simplified)

import json
from rank_bm25 import BM25Okapi


def retrieve_context(query: str, knowledge_path: str, top_k: int = 3) -> list[str]:
    """Retrieve top-K relevant records from company knowledge file using BM25."""
    try:
        with open(knowledge_path, "r", encoding="utf-8") as f:
            docs = [json.loads(line) for line in f if line.strip()]
    except (FileNotFoundError, json.JSONDecodeError):
        return []

    if not docs:
        return []

    texts = [doc.get("text", "") for doc in docs]
    tokenized = [t.lower().split() for t in texts]
    bm25 = BM25Okapi(tokenized)
    query_tokens = query.lower().split()
    scores = bm25.get_scores(query_tokens)

    ranked = sorted(
        [(i, s) for i, s in enumerate(scores) if s > 0],
        key=lambda x: x[1],
        reverse=True,
    )
    return [texts[i] for i, _ in ranked[:top_k]]
```

### A.3 Reserve-Then-Finalize Balance Service

```python
# backend/app/api/v1/companyBalance/balance_service.py (key functions)

from decimal import Decimal
from sqlalchemy.orm import Session
from app.models.companyBalance import CompanyBalance

COST_PER_TOKEN = Decimal("0.000002")  # $0.000002 per token


def reserve_balance(db: Session, company_id: int, estimated_tokens: int) -> Decimal:
    """Reserve estimated cost. Returns reserved amount. Raises 402 if insufficient."""
    estimated_cost = COST_PER_TOKEN * estimated_tokens
    balance = (
        db.query(CompanyBalance)
        .filter(CompanyBalance.company_id == company_id)
        .with_for_update()
        .first()
    )
    if balance.balance < estimated_cost:
        raise InsufficientBalanceError()
    balance.balance -= estimated_cost
    db.flush()
    return estimated_cost


def finalize_balance(db: Session, company_id: int, reserved: Decimal,
                     actual_tokens: int) -> Decimal:
    """Adjust balance for actual vs estimated cost. Refund or deduct difference."""
    actual_cost = COST_PER_TOKEN * actual_tokens
    difference = reserved - actual_cost  # positive = over-reserved (refund)
    if difference != 0:
        balance = (
            db.query(CompanyBalance)
            .filter(CompanyBalance.company_id == company_id)
            .with_for_update()
            .first()
        )
        balance.balance += difference
        db.flush()
    return actual_cost
```

---

## Appendix B: Database Schema (SQL)

```sql
-- Core entity tables
CREATE TABLE company (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    logo VARCHAR(500),
    website VARCHAR(500),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE company_balance (
    id SERIAL PRIMARY KEY,
    company_id INTEGER REFERENCES company(id) ON DELETE CASCADE,
    balance NUMERIC(18, 6) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE company_settings (
    id SERIAL PRIMARY KEY,
    company_id INTEGER REFERENCES company(id) ON DELETE CASCADE,
    language VARCHAR(32) DEFAULT 'english',
    tone VARCHAR(32) DEFAULT 'formal',
    max_tokens INTEGER DEFAULT 200,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE api_key (
    id SERIAL PRIMARY KEY,
    company_id INTEGER REFERENCES company(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    key_hash VARCHAR(64) NOT NULL UNIQUE,
    key_preview VARCHAR(16),
    status VARCHAR(32) DEFAULT 'active',
    expiry_date TIMESTAMP WITH TIME ZONE,
    last_used_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE khalti_payment (
    id SERIAL PRIMARY KEY,
    company_id INTEGER REFERENCES company(id) ON DELETE CASCADE,
    pidx VARCHAR(64) NOT NULL UNIQUE,
    amount_usd NUMERIC(14, 6) NOT NULL,
    amount_npr_paisa INTEGER NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'Initiated',
    transaction_id VARCHAR(128),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE balance_topup (
    id SERIAL PRIMARY KEY,
    company_id INTEGER REFERENCES company(id) ON DELETE CASCADE,
    amount NUMERIC(14, 6) NOT NULL,
    reference VARCHAR(128),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE balance_deduct (
    id SERIAL PRIMARY KEY,
    company_id INTEGER REFERENCES company(id) ON DELETE CASCADE,
    chat_message_id INTEGER,
    session_id VARCHAR(64),
    amount NUMERIC(14, 6) NOT NULL,
    token_consume INTEGER,
    model_name VARCHAR(128),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

---

## Appendix C: API Endpoint Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/auth/register` | None | Register new company |
| POST | `/api/v1/auth/login` | None | Login, returns JWT |
| POST | `/company/{id}/chat/query` | API Key | Non-streaming chat |
| POST | `/company/{id}/chat/stream` | API Key | Streaming chat (SSE) |
| GET | `/api/v1/companyFinetune/{id}` | JWT | Get knowledge base info |
| POST | `/api/v1/companyFinetune/{id}` | JWT | Upload JSONL knowledge base |
| GET | `/api/v1/companyBalance/{id}/balance` | JWT | Get current balance |
| POST | `/api/v1/companyBalance/{id}/topup` | JWT | Add free credits (dev) |
| POST | `/api/v1/companyBalance/{id}/khalti/initiate` | JWT | Initiate Khalti payment |
| POST | `/api/v1/companyBalance/{id}/khalti/verify` | JWT | Verify Khalti payment |
| GET | `/api/v1/companyBalance/{id}/usage` | JWT | Get deduction history |
| GET | `/api/v1/apiKey/{id}` | JWT | List API keys |
| POST | `/api/v1/apiKey/{id}` | JWT | Create API key |
| PUT | `/api/v1/apiKey/{id}/{key_id}/revoke` | JWT | Revoke API key |
| GET | `/api/v1/ticket/{id}` | JWT | List support tickets |
| POST | `/api/v1/ticket/{id}` | JWT | Create support ticket |
| PUT | `/api/v1/ticket/{id}/{ticket_id}` | JWT | Update ticket status |
| GET | `/api/v1/companySettings/{id}` | JWT | Get company settings |
| PUT | `/api/v1/companySettings/{id}` | JWT | Update company settings |

---

## Appendix D: JSONL Knowledge Base Format

Companies upload their knowledge in JSONL format (one JSON object per line). Three formats are accepted:

**Format 1: Question and Answer**
```json
{"question": "What are your working hours?", "answer": "We are open Monday to Friday, 9am to 6pm NPT."}
{"question": "Where are you located?", "answer": "Our main office is at Kathmandu, Nepal."}
```

**Format 2: Title and Content**
```json
{"title": "Return Policy", "content": "Products can be returned within 7 days of purchase with original receipt."}
{"title": "Delivery Policy", "content": "We deliver within Kathmandu Valley in 2-3 business days."}
```

**Format 3: Plain Text**
```json
{"text": "Our school offers morning and evening batches. Morning batch runs 6am-10am, evening batch 4pm-8pm."}
```

All formats are automatically normalized to a unified `text` field for BM25 indexing.

---

## Appendix E: Chat Widget Embed Code

Companies copy and paste the following HTML snippet into their website to embed the Perai chat widget:

```html
<!-- Perai AI Chat Widget -->
<script>
  window.PERAI_CONFIG = {
    companyId: "YOUR_COMPANY_ID",
    apiKey: "sk_YOUR_API_KEY",
    apiUrl: "https://api.perai.app",
    theme: {
      primaryColor: "#0f172a",
      buttonLabel: "Chat with us"
    }
  };
</script>
<script src="https://cdn.perai.app/widget.js" async></script>
```

The widget renders as a floating chat button in the bottom-right corner of the page. Clicking it opens a chat panel that streams AI responses in real-time.
