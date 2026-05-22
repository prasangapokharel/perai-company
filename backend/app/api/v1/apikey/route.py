"""API Key endpoints."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.apikey.service import (
    create_api_key,
    delete_api_key,
    get_api_key,
    list_api_keys,
    revoke_api_key,
    update_api_key,
)
from app.core.database import getDb
from app.schemas.companySchema import (
    APIKeyCreate,
    APIKeyCreateResponse,
    APIKeyRead,
    APIKeyUpdate,
)


router = APIRouter(prefix="/api/v1/company", tags=["apikey"])


@router.post(
    "/{company_id}/api-keys",
    response_model=APIKeyCreateResponse,
    status_code=status.HTTP_201_CREATED,
)
def create_api_key_route(
    company_id: int,
    payload: APIKeyCreate,
    db: Session = Depends(getDb),
):
    """Create a new API key for a company.

    Returns the full API key (only shown once at creation).
    """
    try:
        db_key, full_key = create_api_key(db, company_id, payload)
        return APIKeyCreateResponse(
            id=db_key.id,
            company_id=db_key.company_id,
            name=db_key.name,
            key=full_key,
            key_preview=db_key.key_preview,
            status=db_key.status,
            expiry_date=db_key.expiry_date,
            created_at=db_key.created_at,
        )
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(err),
        ) from err


@router.get("/{company_id}/api-keys", response_model=list[APIKeyRead])
def list_api_keys_route(
    company_id: int,
    db: Session = Depends(getDb),
):
    """List all API keys for a company."""
    try:
        keys = list_api_keys(db, company_id)
        return [
            APIKeyRead(
                id=key.id,
                company_id=key.company_id,
                name=key.name,
                key_preview=key.key_preview,
                status=key.status,
                expiry_date=key.expiry_date,
                last_used_at=key.last_used_at,
                created_at=key.created_at,
                updated_at=key.updated_at,
            )
            for key in keys
        ]
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(err),
        ) from err


@router.get("/{company_id}/api-keys/{key_id}", response_model=APIKeyRead)
def get_api_key_route(
    company_id: int,
    key_id: int,
    db: Session = Depends(getDb),
):
    """Get a specific API key."""
    try:
        key = get_api_key(db, company_id, key_id)
        return APIKeyRead(
            id=key.id,
            company_id=key.company_id,
            name=key.name,
            key_preview=key.key_preview,
            status=key.status,
            expiry_date=key.expiry_date,
            last_used_at=key.last_used_at,
            created_at=key.created_at,
            updated_at=key.updated_at,
        )
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(err),
        ) from err


@router.put("/{company_id}/api-keys/{key_id}", response_model=APIKeyRead)
def update_api_key_route(
    company_id: int,
    key_id: int,
    payload: APIKeyUpdate,
    db: Session = Depends(getDb),
):
    """Update an API key (name, expiry, status)."""
    try:
        key = update_api_key(db, company_id, key_id, payload)
        return APIKeyRead(
            id=key.id,
            company_id=key.company_id,
            name=key.name,
            key_preview=key.key_preview,
            status=key.status,
            expiry_date=key.expiry_date,
            last_used_at=key.last_used_at,
            created_at=key.created_at,
            updated_at=key.updated_at,
        )
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(err),
        ) from err


@router.post("/{company_id}/api-keys/{key_id}/revoke", response_model=APIKeyRead)
def revoke_api_key_route(
    company_id: int,
    key_id: int,
    db: Session = Depends(getDb),
):
    """Revoke an API key."""
    try:
        key = revoke_api_key(db, company_id, key_id)
        return APIKeyRead(
            id=key.id,
            company_id=key.company_id,
            name=key.name,
            key_preview=key.key_preview,
            status=key.status,
            expiry_date=key.expiry_date,
            last_used_at=key.last_used_at,
            created_at=key.created_at,
            updated_at=key.updated_at,
        )
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(err),
        ) from err


@router.delete("/{company_id}/api-keys/{key_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_api_key_route(
    company_id: int,
    key_id: int,
    db: Session = Depends(getDb),
):
    """Delete an API key permanently."""
    try:
        delete_api_key(db, company_id, key_id)
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(err),
        ) from err
