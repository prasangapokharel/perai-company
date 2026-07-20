from decimal import Decimal

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.balance.service import get_balance, list_topups, topup_balance
from app.api.v1.companyBalance.khalti_service import (
    KhaltiError,
    initiate_payment,
    verify_payment,
)
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.balanceSchema import (
    BalanceTopupCreate,
    BalanceTopupRead,
    CompanyBalanceRead,
    KhaltiInitiateCreate,
    KhaltiInitiateRead,
    KhaltiVerifyCreate,
    KhaltiVerifyRead,
)

router = APIRouter(prefix="/api/v1/companyBalance", tags=["company-balance"])


@router.get("/{company_id}", response_model=CompanyBalanceRead)
def get_company_balance(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    balance = get_balance(db, company_id)
    return CompanyBalanceRead(company_id=company_id, balance=balance, currency="USD")


@router.post("/{company_id}/topup", response_model=CompanyBalanceRead)
def topup_company_balance(
    company_id: int,
    payload: BalanceTopupCreate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        balance, _ = topup_balance(db, company_id, payload.amount, reference="manual_topup")
        return CompanyBalanceRead(company_id=company_id, balance=balance, currency="USD")
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.post("/{company_id}/khalti/initiate", response_model=KhaltiInitiateRead)
def initiate_khalti_topup(
    company_id: int,
    payload: KhaltiInitiateCreate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        payment = initiate_payment(db, company_id, payload.amount)
        return KhaltiInitiateRead.model_validate(payment)
    except KhaltiError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.post("/{company_id}/khalti/verify", response_model=KhaltiVerifyRead)
def verify_khalti_topup(
    company_id: int,
    payload: KhaltiVerifyCreate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        payment = verify_payment(db, company_id, payload.pidx)
    except KhaltiError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err

    return KhaltiVerifyRead(
        pidx=payload.pidx,
        status=payment.status,
        amount_usd=Decimal(str(payment.amount_usd)),
        balance=get_balance(db, company_id),
        currency="USD",
    )


@router.get("/{company_id}/topups", response_model=list[BalanceTopupRead])
def list_company_topups(
    company_id: int,
    limit: int = 50,
    offset: int = 0,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    rows = list_topups(db, company_id, limit=limit, offset=offset)
    return [BalanceTopupRead.model_validate(r) for r in rows]
