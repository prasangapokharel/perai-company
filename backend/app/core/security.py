"""API key authentication middleware."""

from fastapi import HTTPException, status, Request
from sqlalchemy.orm import Session

from app.api.v1.apikey.service import validate_api_key
from app.core.database import SessionLocal


async def verify_api_key(request: Request, db: Session = None) -> int:
    """Verify API key from request header.

    Args:
        request: FastAPI request object
        db: Database session

    Returns:
        Company ID if valid

    Raises:
        HTTPException: If API key is invalid or missing
    """
    if db is None:
        db = SessionLocal()

    # Get API key from header
    api_key = request.headers.get("X-API-Key")
    if not api_key:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing API key. Provide 'X-API-Key' header.",
        )

    try:
        db_key, _ = validate_api_key(db, api_key)
        return db_key.company_id
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=str(err),
        ) from err
    finally:
        db.close()
