"""Company request tracking service."""

from sqlalchemy.orm import Session

from app.models.companyRequests import CompanyRequest
from app.schemas.companyRequestsSchema import CompanyRequestCreate


def createCompanyRequest(db: Session, payload: CompanyRequestCreate) -> CompanyRequest:
    """Create a company request tracking record."""

    company_request = CompanyRequest(
        company_id=payload.company_id,
        token_consume=payload.token_consume,
        balance_deducted=payload.balance_deducted,
        ip=payload.ip,
    )
    db.add(company_request)
    db.commit()
    db.refresh(company_request)
    return company_request


def listCompanyRequests(db: Session, company_id: int) -> list[CompanyRequest]:
    """List company request tracking records."""

    return (
        db.query(CompanyRequest)
        .filter(CompanyRequest.company_id == company_id)
        .order_by(CompanyRequest.date.desc())
        .all()
    )
