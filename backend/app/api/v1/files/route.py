"""File storage endpoints — protected by company ownership."""

from fastapi import APIRouter, Depends, File, HTTPException, UploadFile, status
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session

from app.core.database import get_db
from app.utils.file_storage import get_logo_file, list_company_files, upload_content_file, upload_logo
from app.core.security import require_company
from app.models.company import Company

router = APIRouter(prefix="/api/v1/files", tags=["files"])


@router.post("/companies/{company_id}/logo")
async def upload_company_logo(
    company_id: int,
    file: UploadFile = File(...),
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    """Upload company logo (PNG, JPEG, GIF, WebP)."""
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Company not found.")

    logo_path = await upload_logo(company_id, file)
    return {"company_id": company_id, "logo_path": logo_path, "message": "Logo uploaded successfully."}


@router.get("/companies/{company_id}/logo")
async def download_company_logo(company_id: int, db: Session = Depends(get_db)) -> FileResponse:
    """Download company logo (public — used for display in chat widgets)."""
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Company not found.")

    logo_file = get_logo_file(company_id)
    if not logo_file:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Logo not found.")

    return FileResponse(path=logo_file, filename="logo.png", media_type="image/png")


@router.post("/companies/{company_id}/content")
async def upload_company_content(
    company_id: int,
    file: UploadFile = File(...),
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    """Upload a content file for a company."""
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Company not found.")

    file_path = await upload_content_file(company_id, file)
    return {
        "company_id": company_id,
        "file_path": file_path,
        "filename": file.filename,
        "message": "Content file uploaded successfully.",
    }


@router.get("/companies/{company_id}/list")
async def list_company_storage(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    """List all storage files for a company."""
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Company not found.")

    return {"company_id": company_id, "files": list_company_files(company_id)}
