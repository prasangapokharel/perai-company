"""Company authentication endpoints."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.auth.service import get_company_by_id, login_company, register_company
from app.api.v1.balance.service import get_balance
from app.core.database import get_db
from app.core.security import create_access_token, get_auth_company_id
from app.schemas.companySchema import CompanyCreate, CompanyLogin, CompanyLoginResponse, CompanyRead
from app.schemas.balanceSchema import AuthMeResponse

router = APIRouter(prefix="/api/v1/auth", tags=["auth"])


@router.post("/register", response_model=CompanyRead, status_code=status.HTTP_201_CREATED)
def register(company_data: CompanyCreate, db: Session = Depends(get_db)) -> CompanyRead:
    """Register a new company account."""
    company = register_company(db, company_data)
    return CompanyRead.model_validate(company)


@router.post("/login", response_model=CompanyLoginResponse)
def login(credentials: CompanyLogin, db: Session = Depends(get_db)) -> CompanyLoginResponse:
    """Login and receive a JWT access token.

    Use the token as `Authorization: Bearer <token>` to create your first API key.
    For all other requests use `X-API-Key: <key>`.
    """
    company = login_company(db, credentials.email, credentials.password)
    token = create_access_token(company.id)

    return CompanyLoginResponse(
        access_token=token,
        token_type="bearer",
        company=CompanyRead.model_validate(company),
    )


@router.get("/me", response_model=AuthMeResponse)
def auth_me(
    company_id: int = Depends(get_auth_company_id),
    db: Session = Depends(get_db),
):
    company = get_company_by_id(db, company_id)
    if not company:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Company not found.")
    balance = get_balance(db, company_id)
    return AuthMeResponse(
        company_id=company.id,
        company_name=company.company_name,
        company_email=company.company_email,
        balance=balance,
        currency="USD",
        is_admin=bool(company.is_admin),
    )


@router.get("/verify/{company_id}", response_model=CompanyRead)
def verify_company(company_id: int, db: Session = Depends(get_db)) -> CompanyRead:
    """Verify a company exists (lightweight public check)."""
    company = get_company_by_id(db, company_id)
    if not company:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Company not found.")
    return CompanyRead.model_validate(company)
