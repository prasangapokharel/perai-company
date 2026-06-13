"""Company API routes — all protected by company ownership."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.company.service import (
    create_company,
    delete_company,
    delete_company_finetune,
    get_company,
    get_company_finetune,
    upsert_company_finetune,
    update_company,
)
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.companySchema import (
    CompanyCreate,
    CompanyFinetuneRead,
    CompanyFinetuneUpload,
    CompanyRead,
    CompanyUpdate,
)

router = APIRouter(prefix="/api/v1/company", tags=["company"])


@router.get("/{company_id}", response_model=CompanyRead)
def get_company_route(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return CompanyRead.model_validate(get_company(db, company_id))
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.put("/{company_id}", response_model=CompanyRead)
def update_company_route(
    company_id: int,
    payload: CompanyUpdate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return CompanyRead.model_validate(update_company(db, company_id, payload))
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.delete("/{company_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_company_route(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        delete_company(db, company_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.post(
    "/{company_id}/finetune",
    response_model=CompanyFinetuneRead,
    status_code=status.HTTP_201_CREATED,
)
def upsert_company_finetune_route(
    company_id: int,
    payload: CompanyFinetuneUpload,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        finetune = upsert_company_finetune(db, company_id, payload)
        return CompanyFinetuneRead.model_validate(finetune)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.get("/{company_id}/finetune", response_model=CompanyFinetuneRead)
def get_company_finetune_route(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return CompanyFinetuneRead.model_validate(get_company_finetune(db, company_id))
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.delete("/{company_id}/finetune", status_code=status.HTTP_204_NO_CONTENT)
def delete_company_finetune_route(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        delete_company_finetune(db, company_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err
