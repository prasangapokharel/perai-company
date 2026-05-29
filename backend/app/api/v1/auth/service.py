"""Company authentication service."""

from datetime import datetime, timedelta
from typing import Optional
import hashlib
import os

from sqlalchemy.orm import Session
from fastapi import HTTPException, status

from app.models.company import Company
from app.schemas.companySchema import CompanyCreate, CompanyRead


def hash_password(password: str) -> str:
    """Hash password with salt."""
    salt = os.urandom(32)
    pwd_hash = hashlib.pbkdf2_hmac('sha256', password.encode(), salt, 100000)
    return (salt + pwd_hash).hex()


def verify_password(stored_hash: str, password: str) -> bool:
    """Verify password against stored hash."""
    try:
        stored_bytes = bytes.fromhex(stored_hash)
        salt = stored_bytes[:32]
        pwd_hash = hashlib.pbkdf2_hmac('sha256', password.encode(), salt, 100000)
        return pwd_hash == stored_bytes[32:]
    except Exception:
        return False


def register_company(db: Session, company_data: CompanyCreate) -> Company:
    """Register a new company."""
    # Check if company already exists
    existing = db.query(Company).filter(
        (Company.company_email == company_data.company_email) |
        (Company.company_name == company_data.company_name)
    ).first()
    
    if existing:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Company email or name already registered"
        )
    
    # Hash password
    password_hash = hash_password(company_data.password)
    
    # Create company
    db_company = Company(
        company_name=company_data.company_name,
        company_email=company_data.company_email,
        password_hash=password_hash,
        logo=company_data.logo,
        website=company_data.website,
    )
    
    db.add(db_company)
    db.commit()
    db.refresh(db_company)
    
    return db_company


def login_company(db: Session, company_email: str, password: str) -> Company:
    """Authenticate company and return company object."""
    company = db.query(Company).filter(
        Company.company_email == company_email
    ).first()
    
    if not company or not verify_password(company.password_hash, password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid email or password"
        )
    
    return company


def get_company_by_email(db: Session, email: str) -> Optional[Company]:
    """Get company by email."""
    return db.query(Company).filter(Company.company_email == email).first()


def get_company_by_id(db: Session, company_id: int) -> Optional[Company]:
    """Get company by ID."""
    return db.query(Company).filter(Company.id == company_id).first()
