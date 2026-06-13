from decimal import Decimal

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.balance.service import get_balance, list_topups, topup_balance
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.balanceSchema import BalanceTopupCreate, BalanceTopupRead, CompanyBalanceRead

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
