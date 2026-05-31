"""Company Settings API Routes"""
from fastapi import APIRouter, Depends, HTTPException, status, Request
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.core.security import verify_api_key
from app.api.v1.apikey.service import validate_api_key as validate_api_key_service
from app.schemas.companyCustomSettings import (
    CompanyCustomSettingsCreate,
    CompanyCustomSettingsUpdate,
    CompanyCustomSettingsResponse
)
from app.api.v1.companySettings.service import CompanySettingsService

router = APIRouter(
    prefix="/api/v1/company",
    tags=["company-settings"]
)


async def get_company_id_from_api_key(
    request: Request,
    db: Session = Depends(get_db)
) -> int:
    """Dependency to get company_id from API key"""
    api_key = request.headers.get("X-API-Key")
    if not api_key:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing API key. Provide 'X-API-Key' header."
        )
    
    try:
        db_key, _ = validate_api_key_service(db, api_key)
        return db_key.company_id
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=str(err)
        ) from err


@router.post(
    "/{company_id}/settings",
    response_model=CompanyCustomSettingsResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Create or update company settings",
    description="Create or update AI behavior settings (language, tone, max_tokens) for a company"
)
async def createOrUpdateSettings(
    company_id: int,
    data: CompanyCustomSettingsCreate,
    current_company_id: int = Depends(get_company_id_from_api_key),
    db: Session = Depends(get_db)
):
    """Create or update company settings with language, tone, and token limits"""
    
    # Verify company_id matches API key owner
    if current_company_id != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied: Cannot access settings for other companies"
        )
    
    try:
        settings = CompanySettingsService.createOrUpdateSettings(
            db=db,
            company_id=company_id,
            data=data
        )
        
        return CompanyCustomSettingsResponse(
            id=settings.id,
            company_id=settings.company_id,
            language=settings.language,
            tone=settings.tone,
            max_tokens=settings.max_tokens,
            message="Settings created/updated successfully"
        )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to save settings: {str(e)}"
        )


@router.get(
    "/{company_id}/settings",
    response_model=CompanyCustomSettingsResponse,
    status_code=status.HTTP_200_OK,
    summary="Get company settings",
    description="Retrieve current AI behavior settings for a company"
)
async def getSettings(
    company_id: int,
    current_company_id: int = Depends(get_company_id_from_api_key),
    db: Session = Depends(get_db)
):
    """Get company settings"""
    
    # Verify company_id matches API key owner
    if current_company_id != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied: Cannot access settings for other companies"
        )
    
    # Get existing or create default settings
    settings = CompanySettingsService.getOrCreateSettings(
        db=db,
        company_id=company_id
    )
    
    return CompanyCustomSettingsResponse(
        id=settings.id,
        company_id=settings.company_id,
        language=settings.language,
        tone=settings.tone,
        max_tokens=settings.max_tokens,
        message="Settings retrieved successfully"
    )


@router.put(
    "/{company_id}/settings",
    response_model=CompanyCustomSettingsResponse,
    status_code=status.HTTP_200_OK,
    summary="Update company settings",
    description="Update specific AI behavior settings for a company"
)
async def updateSettings(
    company_id: int,
    data: CompanyCustomSettingsUpdate,
    current_company_id: int = Depends(get_company_id_from_api_key),
    db: Session = Depends(get_db)
):
    """Update company settings (partial update supported)"""
    
    # Verify company_id matches API key owner
    if current_company_id != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied: Cannot access settings for other companies"
        )
    
    try:
        settings = CompanySettingsService.updateSettings(
            db=db,
            company_id=company_id,
            data=data
        )
        
        return CompanyCustomSettingsResponse(
            id=settings.id,
            company_id=settings.company_id,
            language=settings.language,
            tone=settings.tone,
            max_tokens=settings.max_tokens,
            message="Settings updated successfully"
        )
    except ValueError as e:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(e)
        )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to update settings: {str(e)}"
        )


@router.delete(
    "/{company_id}/settings",
    status_code=status.HTTP_204_NO_CONTENT,
    summary="Delete company settings",
    description="Delete company settings and revert to defaults"
)
async def deleteSettings(
    company_id: int,
    current_company_id: int = Depends(get_company_id_from_api_key),
    db: Session = Depends(get_db)
):
    """Delete company settings (will revert to defaults on next get)"""
    
    # Verify company_id matches API key owner
    if current_company_id != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied: Cannot access settings for other companies"
        )
    
    CompanySettingsService.deleteSettings(db=db, company_id=company_id)
    return None
