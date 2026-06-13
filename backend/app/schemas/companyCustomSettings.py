"""Company Settings Pydantic Schemas"""
from pydantic import BaseModel, Field
from typing import Optional, Literal
from datetime import datetime


class CompanyCustomSettingsCreate(BaseModel):
    """Schema for creating company settings"""

    language: Literal["english", "nepali"] = Field(
        default="english", description="Language preference for AI responses"
    )
    tone: Literal["formal", "casual", "friendly", "professional"] = Field(
        default="formal", description="Tone/style for AI responses"
    )
    max_tokens: int = Field(
        default=1000, ge=100, le=4000, description="Maximum tokens for API responses (100-4000)"
    )


class CompanyCustomSettingsUpdate(BaseModel):
    """Schema for updating company settings"""

    language: Optional[Literal["english", "nepali"]] = Field(
        None, description="Language preference for AI responses"
    )
    tone: Optional[Literal["formal", "casual", "friendly", "professional"]] = Field(
        None, description="Tone/style for AI responses"
    )
    max_tokens: Optional[int] = Field(
        None, ge=100, le=4000, description="Maximum tokens for API responses (100-4000)"
    )


class CompanyCustomSettingsRead(BaseModel):
    """Schema for reading company settings"""

    id: int
    company_id: int
    language: str
    tone: str
    max_tokens: int
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class CompanyCustomSettingsResponse(BaseModel):
    """Schema for API response of company settings"""

    id: int
    company_id: int
    language: str
    tone: str
    max_tokens: int
    message: Optional[str] = "Settings retrieved successfully"

    class Config:
        from_attributes = True
