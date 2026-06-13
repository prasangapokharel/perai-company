"""Company request tracking routes — protected by company ownership."""

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.api.v1.companyRequests.service import listCompanyRequests
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.companyRequestsSchema import CompanyRequestRead

router = APIRouter(prefix="/api/v1/company", tags=["company-requests"])


@router.get("/{company_id}/requests", response_model=list[CompanyRequestRead])
def get_company_requests(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    """Get request/usage history for a company."""
    return listCompanyRequests(db, company_id)
