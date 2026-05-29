"""Company authentication endpoints."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.auth.service import (
    register_company,
    login_company,
    get_company_by_id,
)
from app.core.database import get_db
from app.models.company import Company
from app.schemas.companySchema import CompanyCreate, CompanyRead, CompanyLogin, CompanyLoginResponse

router = APIRouter(prefix="/api/v1/auth", tags=["auth"])


@router.post("/register", response_model=CompanyRead, status_code=status.HTTP_201_CREATED)
def register(
    company_data: CompanyCreate,
    db: Session = Depends(get_db)
) -> CompanyRead:
    """
    Register a new company.
    
    - **company_name**: Unique company name
    - **company_email**: Unique company email
    - **password**: Company password (will be hashed)
    - **logo**: Optional company logo URL
    - **website**: Optional company website
    """
    company = register_company(db, company_data)
    return CompanyRead.from_orm(company)


@router.post("/login", response_model=CompanyLoginResponse)
def login(
    credentials: CompanyLogin,
    db: Session = Depends(get_db)
) -> CompanyLoginResponse:
    """
    Login company with email and password.
    
    Returns company details and a note that API keys should be used for requests.
    """
    company = login_company(db, credentials.email, credentials.password)
    
    return CompanyLoginResponse(
        message="Login successful. Use X-API-Key header for API requests.",
        company=CompanyRead.from_orm(company),
        api_key_instruction="Create an API key from /api/v1/apikey/create endpoint"
    )


@router.get("/verify/{company_id}", response_model=CompanyRead)
def verify_company(
    company_id: int,
    db: Session = Depends(get_db)
) -> CompanyRead:
    """
    Verify company exists (internal endpoint).
    """
    company = get_company_by_id(db, company_id)
    
    if not company:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Company not found"
        )
    
    return CompanyRead.from_orm(company)
