"""Company schemas."""

from datetime import datetime
from decimal import Decimal
from enum import Enum

from pydantic import BaseModel, ConfigDict, Field


class APIKeyStatus(str, Enum):
    """API key status."""

    ACTIVE = "active"
    REVOKED = "revoked"
    EXPIRED = "expired"


class APIKeyCreate(BaseModel):
    """Create API key request."""

    name: str = Field(..., min_length=1, max_length=255, description="Key name/label")
    expiry_date: datetime | None = Field(default=None, description="Optional expiry date")


class APIKeyRead(BaseModel):
    """API key response (safe to show)."""

    id: int
    company_id: int
    name: str
    key_preview: str  # First 10 + last 10 chars
    status: str
    expiry_date: datetime | None = None
    last_used_at: datetime | None = None
    created_at: datetime
    updated_at: datetime

    model_config = ConfigDict(from_attributes=True)


class APIKeyCreateResponse(BaseModel):
    """API key creation response (includes full key)."""

    id: int
    company_id: int
    name: str
    key: str  # Full key (only shown once at creation)
    key_preview: str
    status: str
    expiry_date: datetime | None = None
    created_at: datetime

    model_config = ConfigDict(from_attributes=True)


class APIKeyUpdate(BaseModel):
    """Update API key request."""

    name: str | None = Field(default=None, min_length=1, max_length=255)
    expiry_date: datetime | None = Field(default=None)
    status: APIKeyStatus | None = Field(default=None)


class CompanyLogin(BaseModel):
    """Company login credentials."""

    email: str = Field(..., min_length=3, max_length=255, description="Company email")
    password: str = Field(..., min_length=8, max_length=255, description="Company password")


class CompanyLoginResponse(BaseModel):
    """Company login response — includes a short-lived JWT for bootstrapping API key creation."""

    access_token: str
    token_type: str = "bearer"
    company: "CompanyRead"


class CompanyCreate(BaseModel):
    company_name: str = Field(..., min_length=1, max_length=255)
    company_email: str = Field(..., min_length=3, max_length=255)
    password: str = Field(..., min_length=8, max_length=255)
    logo: str | None = Field(default=None, max_length=500)
    website: str | None = Field(default=None, max_length=500)


class CompanyUpdate(BaseModel):
    company_name: str | None = Field(default=None, min_length=1, max_length=255)
    company_email: str | None = Field(default=None, min_length=3, max_length=255)
    password: str | None = Field(default=None, min_length=8, max_length=255)
    logo: str | None = Field(default=None, max_length=500)
    website: str | None = Field(default=None, max_length=500)


class CompanyRead(BaseModel):
    id: int
    company_name: str
    company_email: str
    logo: str | None = None
    website: str | None = None
    company_model_name: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = ConfigDict(from_attributes=True)


class FinetuneUploadMode(str, Enum):
    APPEND = "append"
    REPLACE = "replace"


class CompanyFinetuneUpload(BaseModel):
    content: str = Field(..., min_length=1, max_length=10_485_760)
    mode: FinetuneUploadMode = FinetuneUploadMode.APPEND


class CompanyFinetuneRead(BaseModel):
    id: int
    company_id: int
    company_model_name: str
    rag_company_path: str
    content: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = ConfigDict(from_attributes=True)


class ChatQuery(BaseModel):
    prompt: str = Field(..., min_length=1, description="User prompt/question")
    session_id: str | None = Field(default=None, max_length=12, description="Optional session id")
    audio: bool = Field(default=False, description="Return assistant reply as WAV base64")


class ChatResponse(BaseModel):
    model_name: str
    company_id: int
    response: str
    balance_remaining: Decimal | None = None
    session_id: str | None = None
    message_id: int | None = None
    token_consume: int | None = None
    audio_base64: str | None = None
    audio_mime: str | None = None


Company = CompanyRead
CompanyFinetune = CompanyFinetuneRead
