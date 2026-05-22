"""Company API routes."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.company.service import (
    create_company,
    delete_company,
    delete_company_finetune,
    get_company,
    get_company_finetune,
    list_companies,
    upsert_company_finetune,
    update_company,
)
from app.core.database import getDb
from app.schemas.companySchema import (
    CompanyCreate,
    CompanyFinetuneRead,
    CompanyFinetuneUpload,
    CompanyRead,
    CompanyUpdate,
)


router = APIRouter(prefix="/api/v1/company", tags=["company"])


@router.post("", response_model=CompanyRead, status_code=status.HTTP_201_CREATED)
def create_company_route(payload: CompanyCreate, db: Session = Depends(getDb)):
    try:
        return create_company(db, payload)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err)) from err


@router.get("", response_model=list[CompanyRead])
def list_companies_route(db: Session = Depends(getDb)):
    return list_companies(db)


@router.get("/{company_id}", response_model=CompanyRead)
def get_company_route(company_id: int, db: Session = Depends(getDb)):
    try:
        return get_company(db, company_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.put("/{company_id}", response_model=CompanyRead)
def update_company_route(company_id: int, payload: CompanyUpdate, db: Session = Depends(getDb)):
    try:
        return update_company(db, company_id, payload)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.delete("/{company_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_company_route(company_id: int, db: Session = Depends(getDb)):
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
    db: Session = Depends(getDb),
):
    try:
        return upsert_company_finetune(db, company_id, payload)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.get("/{company_id}/finetune", response_model=CompanyFinetuneRead)
def get_company_finetune_route(company_id: int, db: Session = Depends(getDb)):
    try:
        return get_company_finetune(db, company_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err


@router.delete("/{company_id}/finetune", status_code=status.HTTP_204_NO_CONTENT)
def delete_company_finetune_route(company_id: int, db: Session = Depends(getDb)):
    try:
        delete_company_finetune(db, company_id)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err
