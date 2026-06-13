"""Company Settings API Routes — protected by company ownership."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.companySettings.service import CompanySettingsService
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.companyCustomSettings import (
    CompanyCustomSettingsCreate,
    CompanyCustomSettingsResponse,
    CompanyCustomSettingsUpdate,
)

router = APIRouter(prefix="/api/v1/company", tags=["company-settings"])


@router.post(
    "/{company_id}/settings",
    response_model=CompanyCustomSettingsResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Create or update company AI settings",
)
async def create_or_update_settings(
    company_id: int,
    data: CompanyCustomSettingsCreate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        s = CompanySettingsService.createOrUpdateSettings(db, company_id, data)
        return CompanyCustomSettingsResponse(
            id=s.id, company_id=s.company_id, language=s.language,
            tone=s.tone, max_tokens=s.max_tokens,
            message="Settings created/updated successfully.",
        )
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"Failed to save settings: {exc}") from exc


@router.get(
    "/{company_id}/settings",
    response_model=CompanyCustomSettingsResponse,
    summary="Get company AI settings",
)
async def get_settings(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    s = CompanySettingsService.getOrCreateSettings(db, company_id)
    return CompanyCustomSettingsResponse(
        id=s.id, company_id=s.company_id, language=s.language,
        tone=s.tone, max_tokens=s.max_tokens,
        message="Settings retrieved successfully.",
    )


@router.put(
    "/{company_id}/settings",
    response_model=CompanyCustomSettingsResponse,
    summary="Partially update company AI settings",
)
async def update_settings(
    company_id: int,
    data: CompanyCustomSettingsUpdate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        s = CompanySettingsService.updateSettings(db, company_id, data)
        return CompanyCustomSettingsResponse(
            id=s.id, company_id=s.company_id, language=s.language,
            tone=s.tone, max_tokens=s.max_tokens,
            message="Settings updated successfully.",
        )
    except ValueError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
    except Exception as exc:
        raise HTTPException(status_code=500, detail=f"Failed to update settings: {exc}") from exc


@router.delete(
    "/{company_id}/settings",
    status_code=status.HTTP_204_NO_CONTENT,
    summary="Delete company AI settings",
)
async def delete_settings(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    CompanySettingsService.deleteSettings(db, company_id)
