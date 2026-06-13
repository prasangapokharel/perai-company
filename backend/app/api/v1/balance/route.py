from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.api.v1.balance.service import get_balance
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.balanceSchema import CompanyBalanceRead

router = APIRouter(prefix="/api/v1/company", tags=["balance"])


@router.get("/{company_id}/balance", response_model=CompanyBalanceRead)
def get_company_balance(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    balance = get_balance(db, company_id)
    return CompanyBalanceRead(company_id=company_id, balance=balance)
