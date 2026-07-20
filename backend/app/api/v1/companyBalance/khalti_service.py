"""Khalti ePayment integration for balance top-ups.

Flow: initiate → user pays on Khalti's hosted page → Khalti redirects back
with a pidx → verify (server-side lookup) → credit USD balance once.
"""

import logging
from decimal import ROUND_HALF_UP, Decimal

import httpx
from sqlalchemy.orm import Session

from app.api.v1.balance.service import topup_balance
from app.core.config.config import (
    FRONTEND_URL,
    KHALTI_BASE_URL,
    KHALTI_SECRET_KEY,
    KHALTI_USD_TO_NPR,
)
from app.models.company import Company
from app.models.balance_topup import BalanceTopup
from app.models.khalti_payment import KhaltiPayment

log = logging.getLogger(__name__)

_USD_TO_NPR = Decimal(KHALTI_USD_TO_NPR)
_TIMEOUT = 30.0


class KhaltiError(ValueError):
    pass


def usd_to_npr_paisa(amount_usd: Decimal) -> int:
    npr = (amount_usd * _USD_TO_NPR).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)
    return int(npr * 100)


def _headers() -> dict[str, str]:
    if not KHALTI_SECRET_KEY:
        raise KhaltiError("Khalti is not configured (KHALTI_SECRET_KEY missing)")
    return {"Authorization": f"Key {KHALTI_SECRET_KEY}", "Content-Type": "application/json"}


def initiate_payment(db: Session, company_id: int, amount_usd: Decimal) -> KhaltiPayment:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if company is None:
        raise KhaltiError("Company not found")

    paisa = usd_to_npr_paisa(amount_usd)
    payload = {
        "return_url": f"{FRONTEND_URL.rstrip('/')}/balance",
        "website_url": FRONTEND_URL,
        "amount": paisa,
        "purchase_order_id": f"topup-{company_id}-{amount_usd}",
        "purchase_order_name": f"Perai credits ${amount_usd}",
        "customer_info": {
            "name": company.company_name,
            "email": company.company_email,
            "phone": "9800000001",
        },
    }

    try:
        response = httpx.post(
            f"{KHALTI_BASE_URL}/epayment/initiate/",
            json=payload,
            headers=_headers(),
            timeout=_TIMEOUT,
        )
    except httpx.HTTPError as err:
        raise KhaltiError(f"Could not reach Khalti: {err}") from err

    data = response.json()
    if response.status_code != 200 or "pidx" not in data:
        detail = data.get("detail") or data.get("error_key") or str(data)
        raise KhaltiError(f"Khalti initiate failed: {detail}")

    payment = KhaltiPayment(
        company_id=company_id,
        pidx=data["pidx"],
        amount_usd=amount_usd,
        amount_npr_paisa=paisa,
        status="Initiated",
    )
    payment.payment_url = data.get("payment_url", "")
    db.add(payment)
    db.commit()
    db.refresh(payment)
    payment.payment_url = data.get("payment_url", "")
    return payment


def verify_payment(db: Session, company_id: int, pidx: str) -> KhaltiPayment:
    payment = db.query(KhaltiPayment).filter(KhaltiPayment.pidx == pidx).one_or_none()
    if payment is None or payment.company_id != company_id:
        raise KhaltiError("Unknown payment reference")

    reference = f"khalti:{pidx}"
    already_credited = (
        db.query(BalanceTopup).filter(BalanceTopup.reference == reference).one_or_none()
    )
    if already_credited:
        payment.status = "Completed"
        db.commit()
        return payment

    try:
        response = httpx.post(
            f"{KHALTI_BASE_URL}/epayment/lookup/",
            json={"pidx": pidx},
            headers=_headers(),
            timeout=_TIMEOUT,
        )
    except httpx.HTTPError as err:
        raise KhaltiError(f"Could not reach Khalti: {err}") from err

    data = response.json()
    if response.status_code != 200:
        detail = data.get("detail") or str(data)
        raise KhaltiError(f"Khalti lookup failed: {detail}")

    status = data.get("status", "Unknown")
    payment.status = status
    payment.transaction_id = data.get("transaction_id")

    if status == "Completed":
        total_amount = int(data.get("total_amount") or 0)
        if total_amount != payment.amount_npr_paisa:
            payment.status = "AmountMismatch"
            db.commit()
            log.warning(
                "Khalti amount mismatch for pidx=%s: expected %s got %s",
                pidx,
                payment.amount_npr_paisa,
                total_amount,
            )
            raise KhaltiError("Paid amount does not match the requested top-up")
        topup_balance(db, company_id, Decimal(str(payment.amount_usd)), reference=reference)

    db.commit()
    db.refresh(payment)
    return payment
