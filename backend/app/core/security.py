"""Auth dependencies — JWT and API-key based authentication.

Usage in routes:

    # Require ANY valid auth whose company_id matches path param
    @router.get("/{company_id}/resource")
    def endpoint(
        company_id: int,
        _: int = Depends(require_company),
        db: Session = Depends(get_db),
    ): ...

    # Finetune + chat + most company routes use require_company (JWT or X-API-Key)

    # Require API-key only (external integrations / chat widget)
    @router.post("/{company_id}/chat/query")
    def chat(
        company_id: int,
        _: int = Depends(require_api_key_company),
        db: Session = Depends(get_db),
    ): ...
"""

from __future__ import annotations

from datetime import datetime, timedelta, timezone

from fastapi import Depends, HTTPException, Request, Security, status
from fastapi.security import APIKeyHeader, HTTPAuthorizationCredentials, HTTPBearer
from jose import JWTError, jwt
from sqlalchemy.orm import Session

from app.core.config.config import JWT_ALGORITHM, JWT_EXPIRE_MINUTES, JWT_SECRET
from app.core.database import get_db

_bearer = HTTPBearer(auto_error=False)
_api_key_header = APIKeyHeader(name="X-API-Key", auto_error=False)


# ---------------------------------------------------------------------------
# JWT helpers
# ---------------------------------------------------------------------------


def create_access_token(company_id: int) -> str:
    """Create a signed JWT for the given company_id."""
    expire = datetime.now(timezone.utc) + timedelta(minutes=JWT_EXPIRE_MINUTES)
    payload = {"sub": str(company_id), "exp": expire}
    return jwt.encode(payload, JWT_SECRET, algorithm=JWT_ALGORITHM)


def _decode_jwt(token: str) -> int | None:
    try:
        payload = jwt.decode(token, JWT_SECRET, algorithms=[JWT_ALGORITHM])
        sub = payload.get("sub")
        return int(sub) if sub else None
    except (JWTError, ValueError):
        return None


# ---------------------------------------------------------------------------
# Core dependency: get company_id from JWT or X-API-Key header
# ---------------------------------------------------------------------------


async def get_auth_company_id(
    credentials: HTTPAuthorizationCredentials | None = Security(_bearer),
    api_key: str | None = Security(_api_key_header),
    db: Session = Depends(get_db),
) -> int:
    """Extract and validate caller's company_id.

    Tries Bearer JWT first, then X-API-Key header.
    Raises HTTP 401 if neither is present or valid.
    """
    # 1) Bearer JWT
    if credentials:
        company_id = _decode_jwt(credentials.credentials)
        if company_id is not None:
            return company_id
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or expired token.",
            headers={"WWW-Authenticate": "Bearer"},
        )

    # 2) API key
    if api_key:
        from app.api.v1.apikey.service import validate_api_key  # lazy import avoids circular

        try:
            db_key, _ = validate_api_key(db, api_key)
            return db_key.company_id
        except ValueError as err:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail=str(err),
            ) from err

    raise HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Authentication required. Provide 'Authorization: Bearer <token>' or 'X-API-Key' header.",
    )


# ---------------------------------------------------------------------------
# Route-level guard: caller must own the company_id in the path
# ---------------------------------------------------------------------------


def require_company(
    company_id: int,
    auth_company_id: int = Depends(get_auth_company_id),
) -> int:
    """Dependency that verifies the authenticated company matches the path company_id."""
    if auth_company_id != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied: you do not own this resource.",
        )
    return auth_company_id


# ---------------------------------------------------------------------------
# API-key-only variant (for external integrations / chat widget)
# ---------------------------------------------------------------------------


async def require_api_key_company(
    company_id: int,
    api_key: str | None = Security(_api_key_header),
    db: Session = Depends(get_db),
) -> int:
    """Require a valid X-API-Key whose owner matches path company_id."""
    if not api_key:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing API key. Provide 'X-API-Key' header.",
        )
    from app.api.v1.apikey.service import validate_api_key

    try:
        db_key, _ = validate_api_key(db, api_key)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail=str(err)) from err

    if db_key.company_id != company_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="API key does not belong to this company.",
        )
    return db_key.company_id
