"""Company authentication service."""

from typing import Optional

from sqlalchemy.orm import Session
from fastapi import HTTPException, status

from app.utils.password import hash_password, verify_password
from app.models.company import Company
from app.schemas.companySchema import CompanyCreate
from app.api.v1.balance.service import create_initial_balance


def register_company(db: Session, company_data: CompanyCreate) -> Company:
    """Register a new company."""
    existing = (
        db.query(Company)
        .filter(
            (Company.company_email == company_data.company_email)
            | (Company.company_name == company_data.company_name)
        )
        .first()
    )
    if existing:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Company email or name already registered.",
        )

    db_company = Company(
        company_name=company_data.company_name,
        company_email=company_data.company_email,
        password_hash=hash_password(company_data.password),
        logo=company_data.logo,
        website=company_data.website,
    )
    db.add(db_company)
    db.flush()
    create_initial_balance(db, db_company.id)
    db.commit()
    db.refresh(db_company)
    return db_company


def login_company(db: Session, company_email: str, password: str) -> Company:
    """Authenticate company and return the ORM object."""
    company = db.query(Company).filter(Company.company_email == company_email).first()
    if not company or not verify_password(password, company.password_hash):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid email or password.",
        )
    return company


def get_company_by_id(db: Session, company_id: int) -> Optional[Company]:
    return db.query(Company).filter(Company.id == company_id).first()
