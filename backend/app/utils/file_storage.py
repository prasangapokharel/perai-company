"""File storage and management service."""

import shutil
from pathlib import Path
from typing import Optional
from fastapi import UploadFile, HTTPException, status

from app.core.config.config import STORAGE_BASE


def get_company_storage_dir(company_id: int) -> Path:
    """Get company storage directory."""
    return STORAGE_BASE / str(company_id)


def ensure_company_dir(company_id: int) -> Path:
    """Ensure company directory exists."""
    company_dir = get_company_storage_dir(company_id)
    company_dir.mkdir(parents=True, exist_ok=True)
    return company_dir


def get_logo_path(company_id: int) -> Path:
    """Get logo file path for company."""
    return ensure_company_dir(company_id) / "logo" / "logo.png"


def get_content_dir(company_id: int) -> Path:
    """Get content directory for company files."""
    content_dir = ensure_company_dir(company_id) / "content"
    content_dir.mkdir(parents=True, exist_ok=True)
    return content_dir


async def upload_logo(company_id: int, file: UploadFile) -> str:
    """
    Upload company logo.

    Returns relative path for storage in database.
    """
    if not file.filename:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="No filename provided")

    # Validate file type
    valid_types = {"image/png", "image/jpeg", "image/jpg", "image/gif", "image/webp"}
    if file.content_type not in valid_types:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Invalid file type. Allowed: {valid_types}",
        )

    # Create logo directory
    logo_dir = ensure_company_dir(company_id) / "logo"
    logo_dir.mkdir(parents=True, exist_ok=True)

    # Save as logo.png
    logo_path = logo_dir / "logo.png"

    try:
        contents = await file.read()
        with open(logo_path, "wb") as f:
            f.write(contents)

        # Return relative path for database storage
        return f"storage/companies/{company_id}/logo/logo.png"
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to upload logo: {str(e)}",
        )


async def upload_content_file(company_id: int, file: UploadFile) -> str:
    """
    Upload company content file.

    Returns relative path for storage in database.
    """
    if not file.filename:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="No filename provided")

    content_dir = get_content_dir(company_id)

    try:
        # Sanitize filename
        safe_filename = "".join(c for c in file.filename if c.isalnum() or c in "._-")
        if not safe_filename:
            safe_filename = "file"

        file_path = content_dir / safe_filename

        contents = await file.read()
        with open(file_path, "wb") as f:
            f.write(contents)

        return f"storage/companies/{company_id}/content/{safe_filename}"
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to upload file: {str(e)}",
        )


PLACEHOLDER_LOGO = STORAGE_BASE / "logo" / "placeholder.png"


def get_logo_file(company_id: int) -> Optional[Path]:
    """Get logo file if exists, otherwise return placeholder."""
    logo_path = get_logo_path(company_id)
    if logo_path.exists():
        return logo_path
    if PLACEHOLDER_LOGO.exists():
        return PLACEHOLDER_LOGO
    return None


def delete_company_storage(company_id: int) -> bool:
    """Delete all company storage files."""
    try:
        company_dir = get_company_storage_dir(company_id)
        if company_dir.exists():
            shutil.rmtree(company_dir)
        return True
    except Exception as e:
        print(f"Error deleting company storage {company_id}: {e}")
        return False


def list_company_files(company_id: int) -> dict:
    """List all files in company storage."""
    company_dir = get_company_storage_dir(company_id)

    result = {"logo": None, "content_files": []}

    if not company_dir.exists():
        return result

    # Check logo
    logo_path = company_dir / "logo" / "logo.png"
    if logo_path.exists():
        result["logo"] = {
            "path": f"storage/companies/{company_id}/logo/logo.png",
            "size": logo_path.stat().st_size,
        }

    # List content files
    content_dir = company_dir / "content"
    if content_dir.exists():
        for file_path in content_dir.iterdir():
            if file_path.is_file():
                result["content_files"].append(
                    {
                        "name": file_path.name,
                        "path": f"storage/companies/{company_id}/content/{file_path.name}",
                        "size": file_path.stat().st_size,
                    }
                )

    return result
