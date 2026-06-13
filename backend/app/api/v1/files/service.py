"""File service layer."""

from pathlib import Path
from typing import Optional
from fastapi import UploadFile, HTTPException, status

from app.utils.file_storage import (
    STORAGE_BASE,
    get_logo_path,
    upload_logo as storage_upload_logo,
    get_logo_file as storage_get_logo_file,
    upload_content_file as storage_upload_content_file,
    list_company_files as storage_list_company_files,
    delete_company_storage,
)


PLACEHOLDER_LOGO = STORAGE_BASE / "logo" / "placeholder.png"


def get_company_logo(company_id: int) -> Optional[Path]:
    """Get company logo path, or placeholder if none uploaded."""
    return storage_get_logo_file(company_id)


def has_placeholder() -> bool:
    """Check if placeholder logo exists."""
    return PLACEHOLDER_LOGO.exists()
