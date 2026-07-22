"""Admin API — platform-wide administration.

All routes require a platform admin (Company.is_admin) via require_admin.
Admins are NOT scoped to a path company_id; they act across every company.
"""

from decimal import Decimal

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.admin import service
from app.api.v1.balance.service import get_balance, list_deductions, list_topups
from app.core.database import get_db
from app.core.security import require_admin
from app.schemas.adminSchema import (
    AdminBalanceAdjust,
    AdminCompanyRead,
    AdminCompanyUpdate,
    AdminKhaltiPaymentRead,
    AdminOverview,
    AdminSetRole,
)
from app.schemas.balanceSchema import (
    BalanceDeductRead,
    BalanceTopupRead,
    CompanyBalanceRead,
)
from app.schemas.companyCustomSettings import CompanyCustomSettingsRead
from app.schemas.ticketSchema import TicketRead

router = APIRouter(prefix="/api/v1/admin", tags=["admin"])


# ---------------------------------------------------------------------------
# Overview
# ---------------------------------------------------------------------------


@router.get("/overview", response_model=AdminOverview)
def admin_overview(
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    return AdminOverview(**service.get_overview(db))


# ---------------------------------------------------------------------------
# Companies
# ---------------------------------------------------------------------------


@router.get("/companies", response_model=list[AdminCompanyRead])
def admin_list_companies(
    search: str | None = None,
    limit: int = 50,
    offset: int = 0,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    return service.list_companies(db, search=search, limit=limit, offset=offset)


@router.get("/companies/{company_id}", response_model=AdminCompanyRead)
def admin_get_company(
    company_id: int,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        return service.get_company(db, company_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.put("/companies/{company_id}", response_model=AdminCompanyRead)
def admin_update_company(
    company_id: int,
    payload: AdminCompanyUpdate,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        return service.update_company(db, company_id, payload)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.put("/companies/{company_id}/role", response_model=AdminCompanyRead)
def admin_set_company_role(
    company_id: int,
    payload: AdminSetRole,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        return service.set_company_role(db, company_id, payload.is_admin)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.delete("/companies/{company_id}", status_code=status.HTTP_204_NO_CONTENT)
def admin_delete_company(
    company_id: int,
    admin_id: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        service.delete_company(db, company_id, admin_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


# ---------------------------------------------------------------------------
# Balance
# ---------------------------------------------------------------------------


@router.get("/companies/{company_id}/balance", response_model=CompanyBalanceRead)
def admin_get_balance(
    company_id: int,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    return CompanyBalanceRead(
        company_id=company_id, balance=get_balance(db, company_id), currency="USD"
    )


@router.post("/companies/{company_id}/balance/adjust", response_model=CompanyBalanceRead)
def admin_adjust_balance(
    company_id: int,
    payload: AdminBalanceAdjust,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        new_balance = service.adjust_balance(db, company_id, payload.amount, payload.reason)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err
    return CompanyBalanceRead(company_id=company_id, balance=new_balance, currency="USD")


@router.get("/companies/{company_id}/topups", response_model=list[BalanceTopupRead])
def admin_list_topups(
    company_id: int,
    limit: int = 50,
    offset: int = 0,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    return [
        BalanceTopupRead.model_validate(r)
        for r in list_topups(db, company_id, limit=limit, offset=offset)
    ]


@router.get("/companies/{company_id}/deductions", response_model=list[BalanceDeductRead])
def admin_list_deductions(
    company_id: int,
    limit: int = 50,
    offset: int = 0,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    return [
        BalanceDeductRead.model_validate(r)
        for r in list_deductions(db, company_id, limit=limit, offset=offset)
    ]


@router.get("/payments/khalti", response_model=list[AdminKhaltiPaymentRead])
def admin_list_khalti_payments(
    company_id: int | None = None,
    limit: int = 50,
    offset: int = 0,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    return [
        AdminKhaltiPaymentRead.model_validate(p)
        for p in service.list_khalti_payments(db, company_id=company_id, limit=limit, offset=offset)
    ]


# ---------------------------------------------------------------------------
# Tickets
# ---------------------------------------------------------------------------


@router.get("/tickets", response_model=list[TicketRead])
def admin_list_tickets(
    status_filter: str | None = None,
    company_id: int | None = None,
    limit: int = 100,
    offset: int = 0,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        return service.list_all_tickets(
            db, status_filter=status_filter, company_id=company_id, limit=limit, offset=offset
        )
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.get("/tickets/{ticket_id}", response_model=TicketRead)
def admin_get_ticket(
    ticket_id: int,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        return service.get_ticket(db, ticket_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.put("/tickets/{ticket_id}", response_model=TicketRead)
def admin_update_ticket(
    ticket_id: int,
    payload: dict,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    status_value = payload.get("status")
    if not status_value:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST, detail="Missing 'status' in body."
        )
    try:
        return service.update_ticket(db, ticket_id, status_value)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.delete("/tickets/{ticket_id}", status_code=status.HTTP_204_NO_CONTENT)
def admin_delete_ticket(
    ticket_id: int,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        service.delete_ticket(db, ticket_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


# ---------------------------------------------------------------------------
# API keys
# ---------------------------------------------------------------------------


@router.get("/apikeys")
def admin_list_api_keys(
    company_id: int | None = None,
    limit: int = 100,
    offset: int = 0,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    keys = service.list_all_api_keys(db, company_id=company_id, limit=limit, offset=offset)
    return [
        {
            "id": k.id,
            "company_id": k.company_id,
            "name": k.name,
            "key_preview": k.key_preview,
            "status": k.status,
            "expiry_date": k.expiry_date,
            "last_used_at": k.last_used_at,
            "created_at": k.created_at,
        }
        for k in keys
    ]


@router.post("/apikeys/{api_key_id}/revoke")
def admin_revoke_api_key(
    api_key_id: int,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    try:
        key = service.revoke_api_key(db, api_key_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err
    return {"id": key.id, "status": key.status}


# ---------------------------------------------------------------------------
# Settings (read-only admin view)
# ---------------------------------------------------------------------------


@router.get("/companies/{company_id}/settings", response_model=CompanyCustomSettingsRead)
def admin_get_settings(
    company_id: int,
    _: int = Depends(require_admin),
    db: Session = Depends(get_db),
):
    settings = service.get_company_settings(db, company_id)
    if settings is None:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND, detail="Settings not found for this company."
        )
    return settings
