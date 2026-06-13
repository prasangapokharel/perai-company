"""API Key endpoints — protected by company ownership."""

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
from app.core.database import get_db
from app.core.security import require_company
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
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    """Create a new API key. The full key is only shown once at creation.

    Authenticate with the JWT received from POST /auth/login.
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
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.get("/{company_id}/api-keys", response_model=list[APIKeyRead])
def list_api_keys_route(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return [APIKeyRead.model_validate(k) for k in list_api_keys(db, company_id)]
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.get("/{company_id}/api-keys/{key_id}", response_model=APIKeyRead)
def get_api_key_route(
    company_id: int,
    key_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return APIKeyRead.model_validate(get_api_key(db, company_id, key_id))
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.put("/{company_id}/api-keys/{key_id}", response_model=APIKeyRead)
def update_api_key_route(
    company_id: int,
    key_id: int,
    payload: APIKeyUpdate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return APIKeyRead.model_validate(update_api_key(db, company_id, key_id, payload))
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.post("/{company_id}/api-keys/{key_id}/revoke", response_model=APIKeyRead)
def revoke_api_key_route(
    company_id: int,
    key_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return APIKeyRead.model_validate(revoke_api_key(db, company_id, key_id))
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.delete("/{company_id}/api-keys/{key_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_api_key_route(
    company_id: int,
    key_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        delete_api_key(db, company_id, key_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err
