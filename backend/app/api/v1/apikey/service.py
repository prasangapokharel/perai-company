"""API Key service for CRUD operations."""

from datetime import datetime
from sqlalchemy.orm import Session
from sqlalchemy.exc import IntegrityError

from app.models.company import APIKey, APIKeyStatus, Company
from app.schemas.companySchema import APIKeyCreate, APIKeyUpdate, APIKeyCreateResponse, APIKeyRead
from app.core.api_key_utils import generate_api_key, hash_api_key, get_key_preview, is_api_key_expired


def create_api_key(db: Session, company_id: int, payload: APIKeyCreate) -> tuple[APIKey, str]:
    """Create a new API key for a company.

    Args:
        db: Database session
        company_id: Company ID
        payload: API key creation payload

    Returns:
        Tuple of (APIKey object, full API key)

    Raises:
        ValueError: If company not found or key creation fails
    """
    # Verify company exists
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise ValueError(f"Company with ID {company_id} not found")

    # Generate API key
    api_key = generate_api_key()
    key_hash = hash_api_key(api_key)
    key_preview = get_key_preview(api_key)

    try:
        # Create API key record
        db_key = APIKey(
            company_id=company_id,
            name=payload.name,
            key_hash=key_hash,
            key_preview=key_preview,
            status=APIKeyStatus.ACTIVE,
            expiry_date=payload.expiry_date,
        )

        db.add(db_key)
        db.commit()
        db.refresh(db_key)

        return db_key, api_key
    except IntegrityError as err:
        db.rollback()
        if "uq_api_key_company_name" in str(err):
            raise ValueError(f"API key with name '{payload.name}' already exists for this company")
        raise ValueError(f"Failed to create API key: {str(err)}")


def list_api_keys(db: Session, company_id: int) -> list[APIKey]:
    """List all API keys for a company.

    Args:
        db: Database session
        company_id: Company ID

    Returns:
        List of APIKey objects

    Raises:
        ValueError: If company not found
    """
    # Verify company exists
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise ValueError(f"Company with ID {company_id} not found")

    return db.query(APIKey).filter(APIKey.company_id == company_id).all()


def get_api_key(db: Session, company_id: int, key_id: int) -> APIKey:
    """Get a specific API key.

    Args:
        db: Database session
        company_id: Company ID
        key_id: API key ID

    Returns:
        APIKey object

    Raises:
        ValueError: If key not found or doesn't belong to company
    """
    api_key = db.query(APIKey).filter(
        APIKey.id == key_id,
        APIKey.company_id == company_id,
    ).first()

    if not api_key:
        raise ValueError(f"API key with ID {key_id} not found for company {company_id}")

    return api_key


def update_api_key(db: Session, company_id: int, key_id: int, payload: APIKeyUpdate) -> APIKey:
    """Update an API key.

    Args:
        db: Database session
        company_id: Company ID
        key_id: API key ID
        payload: Update payload

    Returns:
        Updated APIKey object

    Raises:
        ValueError: If key not found or update fails
    """
    api_key = get_api_key(db, company_id, key_id)

    # Update fields if provided
    if payload.name is not None:
        api_key.name = payload.name
    if payload.expiry_date is not None:
        api_key.expiry_date = payload.expiry_date
    if payload.status is not None:
        api_key.status = payload.status

    api_key.updated_at = datetime.utcnow()

    try:
        db.commit()
        db.refresh(api_key)
        return api_key
    except IntegrityError as err:
        db.rollback()
        raise ValueError(f"Failed to update API key: {str(err)}")


def delete_api_key(db: Session, company_id: int, key_id: int) -> None:
    """Delete an API key (soft delete via revocation).

    Args:
        db: Database session
        company_id: Company ID
        key_id: API key ID

    Raises:
        ValueError: If key not found
    """
    api_key = get_api_key(db, company_id, key_id)

    db.delete(api_key)
    db.commit()


def revoke_api_key(db: Session, company_id: int, key_id: int) -> APIKey:
    """Revoke an API key (mark as revoked instead of deleting).

    Args:
        db: Database session
        company_id: Company ID
        key_id: API key ID

    Returns:
        Updated APIKey object

    Raises:
        ValueError: If key not found
    """
    api_key = get_api_key(db, company_id, key_id)

    api_key.status = APIKeyStatus.REVOKED
    api_key.updated_at = datetime.utcnow()

    db.commit()
    db.refresh(api_key)

    return api_key


def validate_api_key(db: Session, api_key: str) -> tuple[APIKey, bool]:
    """Validate an API key and return the key object.

    Args:
        db: Database session
        api_key: Plain API key to validate

    Returns:
        Tuple of (APIKey object or None, is_valid)

    Raises:
        ValueError: If key is invalid
    """
    from app.core.api_key_utils import hash_api_key, is_api_key_expired

    key_hash = hash_api_key(api_key)
    db_key = db.query(APIKey).filter(APIKey.key_hash == key_hash).first()

    if not db_key:
        raise ValueError("Invalid API key")

    # Check if revoked
    if db_key.status == APIKeyStatus.REVOKED:
        raise ValueError("API key has been revoked")

    # Check if expired
    if is_api_key_expired(db_key.expiry_date):
        # Mark as expired if not already
        if db_key.status != APIKeyStatus.EXPIRED:
            db_key.status = APIKeyStatus.EXPIRED
            db.commit()
        raise ValueError("API key has expired")

    # Update last_used_at
    db_key.last_used_at = datetime.utcnow()
    db.commit()

    return db_key, True
