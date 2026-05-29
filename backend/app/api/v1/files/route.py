"""File storage endpoints."""

from typing import Dict
from fastapi import APIRouter, Depends, File, UploadFile, HTTPException, status
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session

from app.core.database import get_db
from app.core.file_storage import (
    upload_logo,
    upload_content_file,
    get_logo_file,
    list_company_files,
)
from app.models.company import Company
from app.core.security import verify_api_key

router = APIRouter(prefix="/api/v1/files", tags=["files"])


@router.post("/companies/{company_id}/logo", response_model=None)
async def upload_company_logo(
    company_id: int,
    file: UploadFile = File(...),
    db: Session = Depends(get_db),
    api_key_info: dict = Depends(verify_api_key)
):
    """
    Upload company logo.
    
    - **company_id**: Company ID
    - **file**: Logo file (PNG, JPEG, GIF, WebP)
    
    Returns storage path and file info.
    """
    # Verify company exists and belongs to api_key_info company
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Company not found"
        )
    
    # Verify API key belongs to this company
    if api_key_info["company_id"] != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="API key does not belong to this company"
        )
    
    # Upload logo
    logo_path = await upload_logo(company_id, file)
    
    return {
        "company_id": company_id,
        "logo_path": logo_path,
        "message": "Logo uploaded successfully"
    }


@router.get("/companies/{company_id}/logo")
async def download_company_logo(
    company_id: int,
    db: Session = Depends(get_db)
) -> FileResponse:
    """
    Download company logo.
    
    - **company_id**: Company ID
    
    Returns logo file.
    """
    # Verify company exists
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Company not found"
        )
    
    # Get logo file
    logo_file = get_logo_file(company_id)
    if not logo_file:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Logo not found"
        )
    
    return FileResponse(
        path=logo_file,
        filename="logo.png",
        media_type="image/png"
    )


@router.post("/companies/{company_id}/content", response_model=None)
async def upload_company_content(
    company_id: int,
    file: UploadFile = File(...),
    db: Session = Depends(get_db),
    api_key_info: dict = Depends(verify_api_key)
):
    """
    Upload company content file.
    
    - **company_id**: Company ID
    - **file**: Content file
    
    Returns storage path and file info.
    """
    # Verify company exists
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Company not found"
        )
    
    # Verify API key belongs to this company
    if api_key_info["company_id"] != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="API key does not belong to this company"
        )
    
    # Upload content file
    file_path = await upload_content_file(company_id, file)
    
    return {
        "company_id": company_id,
        "file_path": file_path,
        "filename": file.filename,
        "message": "Content file uploaded successfully"
    }


@router.get("/companies/{company_id}/list", response_model=None)
async def list_company_storage(
    company_id: int,
    db: Session = Depends(get_db),
    api_key_info: dict = Depends(verify_api_key)
):
    """
    List all company storage files.
    
    - **company_id**: Company ID
    
    Returns list of files and their sizes.
    """
    # Verify company exists
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Company not found"
        )
    
    # Verify API key belongs to this company
    if api_key_info["company_id"] != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="API key does not belong to this company"
        )
    
    files = list_company_files(company_id)
    
    return {
        "company_id": company_id,
        "files": files
    }
