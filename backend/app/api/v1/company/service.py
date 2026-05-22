"""Company business logic."""

from __future__ import annotations

import hashlib
import hmac
from secrets import token_bytes

from sqlalchemy.orm import Session

from app.core.finetune.rag.main import (
    delete_training_file_for_company,
    save_training_file_for_company,
)
from app.models.company import Company, CompanyFinetune
from app.schemas.companySchema import CompanyCreate, CompanyFinetuneUpload, CompanyUpdate


def hash_password(password: str) -> str:
    salt = token_bytes(16)
    digest = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, 200000)
    return f"{salt.hex()}${digest.hex()}"


def verify_password(password: str, hashed: str) -> bool:
    salt_hex, digest_hex = hashed.split("$", 1)
    salt = bytes.fromhex(salt_hex)
    candidate = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, 200000)
    return hmac.compare_digest(candidate.hex(), digest_hex)


def list_companies(db: Session) -> list[Company]:
    return db.query(Company).order_by(Company.id.desc()).all()


def get_company(db: Session, company_id: int) -> Company:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if not company:
        raise ValueError("Company not found")
    return company


def create_company(db: Session, payload: CompanyCreate) -> Company:
    exists = (
        db.query(Company)
        .filter(
            (Company.company_name == payload.company_name)
            | (Company.company_email == payload.company_email)
        )
        .first()
    )
    if exists:
        raise ValueError("Company name or email already exists")

    company = Company(
        company_name=payload.company_name,
        company_email=payload.company_email,
        password_hash=hash_password(payload.password),
        logo=payload.logo,
        website=payload.website,
    )
    db.add(company)
    db.commit()
    db.refresh(company)
    return company


def update_company(db: Session, company_id: int, payload: CompanyUpdate) -> Company:
    company = get_company(db, company_id)

    if payload.company_name is not None:
        company.company_name = payload.company_name
    if payload.company_email is not None:
        company.company_email = payload.company_email
    if payload.password is not None:
        company.password_hash = hash_password(payload.password)
    if payload.logo is not None:
        company.logo = payload.logo
    if payload.website is not None:
        company.website = payload.website

    db.commit()
    db.refresh(company)
    return company


def delete_company(db: Session, company_id: int) -> None:
    get_company(db, company_id)
    delete_training_file_for_company(company_id)
    db.query(CompanyFinetune).filter(CompanyFinetune.company_id == company_id).delete()
    db.query(Company).filter(Company.id == company_id).delete()
    db.commit()


def upsert_company_finetune(
    db: Session,
    company_id: int,
    payload: CompanyFinetuneUpload,
) -> CompanyFinetune:
    company = get_company(db, company_id)
    path = save_training_file_for_company(company.id, payload.content)
    
    # Generate model name as perai-{company_name}
    model_name = f"perai-{company.company_name.lower().replace(' ', '-')}"

    finetune = (
        db.query(CompanyFinetune)
        .filter(CompanyFinetune.company_id == company.id)
        .one_or_none()
    )
    if finetune is None:
        finetune = CompanyFinetune(
            company_id=company.id,
            company_model_name=model_name,
            rag_company_path=str(path.resolve()),
        )
        db.add(finetune)
    else:
        finetune.company_model_name = model_name
        finetune.rag_company_path = str(path.resolve())

    db.commit()
    db.refresh(finetune)
    return finetune


def get_company_finetune(db: Session, company_id: int) -> CompanyFinetune:
    finetune = (
        db.query(CompanyFinetune)
        .filter(CompanyFinetune.company_id == company_id)
        .one_or_none()
    )
    if not finetune:
        raise ValueError("Company finetune not found")
    return finetune


def get_company_model_name(db: Session, company_id: int) -> str:
    """Get the model name for a company."""
    finetune = get_company_finetune(db, company_id)
    return finetune.company_model_name


def delete_company_finetune(db: Session, company_id: int) -> None:
    get_company(db, company_id)
    delete_training_file_for_company(company_id)
    db.query(CompanyFinetune).filter(CompanyFinetune.company_id == company_id).delete()
    db.commit()
