"""Admin service — platform-wide administration logic.

Admins act across ALL companies. These functions are not scoped to a single
company_id (unlike the per-company services), so they must only ever be reached
through the require_admin dependency.
"""

from decimal import Decimal

from sqlalchemy import func
from sqlalchemy.orm import Session

from app.api.v1.balance.service import get_balance, topup_balance
from app.models.balance import CompanyBalance
from app.models.balance_deduct import BalanceDeduct
from app.models.balance_topup import BalanceTopup
from app.models.company import APIKey, Company
from app.models.companySettings import CompanySettings
from app.models.khalti_payment import KhaltiPayment
from app.models.ticket import Ticket, TicketStatus
from app.utils.password import hash_password


# ---------------------------------------------------------------------------
# Overview
# ---------------------------------------------------------------------------


def get_overview(db: Session) -> dict:
    total_companies = db.query(func.count(Company.id)).scalar() or 0
    admin_companies = (
        db.query(func.count(Company.id)).filter(Company.is_admin.is_(True)).scalar() or 0
    )
    total_balance = db.query(func.coalesce(func.sum(CompanyBalance.balance), 0)).scalar() or 0
    total_topups = db.query(func.coalesce(func.sum(BalanceTopup.amount), 0)).scalar() or 0
    total_deducted = db.query(func.coalesce(func.sum(BalanceDeduct.amount), 0)).scalar() or 0
    total_tickets = db.query(func.count(Ticket.id)).scalar() or 0
    open_tickets = (
        db.query(func.count(Ticket.id)).filter(Ticket.status == TicketStatus.open).scalar() or 0
    )
    total_api_keys = db.query(func.count(APIKey.id)).scalar() or 0
    active_api_keys = (
        db.query(func.count(APIKey.id)).filter(APIKey.status == "active").scalar() or 0
    )
    total_khalti = db.query(func.count(KhaltiPayment.id)).scalar() or 0
    completed_khalti = (
        db.query(func.count(KhaltiPayment.id))
        .filter(KhaltiPayment.status == "Completed")
        .scalar()
        or 0
    )

    return {
        "total_companies": total_companies,
        "admin_companies": admin_companies,
        "total_balance": Decimal(str(total_balance)),
        "total_topups": Decimal(str(total_topups)),
        "total_deducted": Decimal(str(total_deducted)),
        "total_tickets": total_tickets,
        "open_tickets": open_tickets,
        "total_api_keys": total_api_keys,
        "active_api_keys": active_api_keys,
        "total_khalti_payments": total_khalti,
        "completed_khalti_payments": completed_khalti,
    }


# ---------------------------------------------------------------------------
# Companies
# ---------------------------------------------------------------------------


def _company_row(db: Session, company: Company) -> dict:
    return {
        "id": company.id,
        "company_name": company.company_name,
        "company_email": company.company_email,
        "logo": company.logo,
        "website": company.website,
        "is_admin": bool(company.is_admin),
        "balance": get_balance(db, company.id),
        "created_at": company.created_at,
        "updated_at": company.updated_at,
    }


def list_companies(
    db: Session,
    search: str | None = None,
    limit: int = 50,
    offset: int = 0,
) -> list[dict]:
    query = db.query(Company)
    if search:
        like = f"%{search}%"
        query = query.filter(
            (Company.company_name.ilike(like)) | (Company.company_email.ilike(like))
        )
    companies = (
        query.order_by(Company.created_at.desc())
        .offset(offset)
        .limit(min(limit, 200))
        .all()
    )
    return [_company_row(db, c) for c in companies]


def get_company(db: Session, company_id: int) -> dict:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if company is None:
        raise ValueError("Company not found")
    return _company_row(db, company)


def update_company(db: Session, company_id: int, payload) -> dict:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if company is None:
        raise ValueError("Company not found")

    if payload.company_name is not None:
        company.company_name = payload.company_name
    if payload.company_email is not None:
        company.company_email = payload.company_email
    if payload.password is not None:
        company.password_hash = hash_password(payload.password)
    if payload.logo is not None:
        company.logo = payload.logo
    if payload.website is not None:
        company.website = payload.website

    db.commit()
    db.refresh(company)
    return _company_row(db, company)


def set_company_role(db: Session, company_id: int, is_admin: bool) -> dict:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if company is None:
        raise ValueError("Company not found")
    company.is_admin = is_admin
    db.commit()
    db.refresh(company)
    return _company_row(db, company)


def delete_company(db: Session, company_id: int, acting_admin_id: int) -> None:
    if company_id == acting_admin_id:
        raise ValueError("You cannot delete your own admin account")
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if company is None:
        raise ValueError("Company not found")
    db.delete(company)
    db.commit()


# ---------------------------------------------------------------------------
# Balance
# ---------------------------------------------------------------------------


def adjust_balance(
    db: Session, company_id: int, amount: Decimal, reason: str | None
) -> Decimal:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if company is None:
        raise ValueError("Company not found")

    reference = f"admin_adjust:{reason}" if reason else "admin_adjust"

    if amount > 0:
        new_balance, _ = topup_balance(db, company_id, amount, reference=reference)
        return new_balance

    if amount < 0:
        row = (
            db.query(CompanyBalance)
            .filter(CompanyBalance.company_id == company_id)
            .with_for_update()
            .one_or_none()
        )
        current = Decimal(str(row.balance)) if row else Decimal("0")
        debit = abs(amount)
        if row is None or current < debit:
            raise ValueError(f"Insufficient balance to debit {debit} (available {current})")
        row.balance = current - debit
        deduct = BalanceDeduct(
            company_id=company_id,
            amount=debit,
            token_consume=0,
            model_name=reference,
        )
        db.add(deduct)
        db.commit()
        db.refresh(row)
        return Decimal(str(row.balance))

    return get_balance(db, company_id)


def list_khalti_payments(
    db: Session,
    company_id: int | None = None,
    limit: int = 50,
    offset: int = 0,
) -> list[KhaltiPayment]:
    query = db.query(KhaltiPayment)
    if company_id is not None:
        query = query.filter(KhaltiPayment.company_id == company_id)
    return (
        query.order_by(KhaltiPayment.created_at.desc())
        .offset(offset)
        .limit(min(limit, 200))
        .all()
    )


# ---------------------------------------------------------------------------
# Tickets
# ---------------------------------------------------------------------------


def list_all_tickets(
    db: Session,
    status_filter: str | None = None,
    company_id: int | None = None,
    limit: int = 100,
    offset: int = 0,
) -> list[Ticket]:
    query = db.query(Ticket)
    if company_id is not None:
        query = query.filter(Ticket.company_id == company_id)
    if status_filter:
        try:
            query = query.filter(Ticket.status == TicketStatus(status_filter))
        except ValueError as err:
            raise ValueError(f"Invalid status filter: {status_filter}") from err
    return (
        query.order_by(Ticket.created_at.desc())
        .offset(offset)
        .limit(min(limit, 200))
        .all()
    )


def get_ticket(db: Session, ticket_id: int) -> Ticket:
    ticket = db.query(Ticket).filter(Ticket.id == ticket_id).one_or_none()
    if ticket is None:
        raise ValueError("Ticket not found")
    return ticket


def update_ticket(db: Session, ticket_id: int, status_value: str) -> Ticket:
    ticket = get_ticket(db, ticket_id)
    try:
        ticket.status = TicketStatus(status_value)
    except ValueError as err:
        raise ValueError(f"Invalid status: {status_value}") from err
    db.commit()
    db.refresh(ticket)
    return ticket


def delete_ticket(db: Session, ticket_id: int) -> None:
    ticket = get_ticket(db, ticket_id)
    db.delete(ticket)
    db.commit()


# ---------------------------------------------------------------------------
# API keys
# ---------------------------------------------------------------------------


def list_all_api_keys(
    db: Session,
    company_id: int | None = None,
    limit: int = 100,
    offset: int = 0,
) -> list[APIKey]:
    query = db.query(APIKey)
    if company_id is not None:
        query = query.filter(APIKey.company_id == company_id)
    return (
        query.order_by(APIKey.created_at.desc())
        .offset(offset)
        .limit(min(limit, 200))
        .all()
    )


def revoke_api_key(db: Session, api_key_id: int) -> APIKey:
    key = db.query(APIKey).filter(APIKey.id == api_key_id).one_or_none()
    if key is None:
        raise ValueError("API key not found")
    key.status = "revoked"
    db.commit()
    db.refresh(key)
    return key


# ---------------------------------------------------------------------------
# Settings
# ---------------------------------------------------------------------------


def get_company_settings(db: Session, company_id: int) -> CompanySettings | None:
    return (
        db.query(CompanySettings)
        .filter(CompanySettings.company_id == company_id)
        .one_or_none()
    )
