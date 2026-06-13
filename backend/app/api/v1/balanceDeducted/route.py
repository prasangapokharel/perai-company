from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.api.v1.balance.service import list_deductions
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.balanceSchema import BalanceDeductRead

router = APIRouter(prefix="/api/v1/balanceDeducted", tags=["balance-deducted"])


@router.get("/{company_id}", response_model=list[BalanceDeductRead])
def list_balance_deducted(
    company_id: int,
    limit: int = 50,
    offset: int = 0,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    rows = list_deductions(db, company_id, limit=limit, offset=offset)
    return [BalanceDeductRead.model_validate(r) for r in rows]
