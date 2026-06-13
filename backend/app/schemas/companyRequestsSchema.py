"""Company request schemas."""

from datetime import datetime
from decimal import Decimal

from pydantic import BaseModel, ConfigDict


class CompanyRequestBase(BaseModel):
    """Base company request schema."""

    company_id: int
    token_consume: int
    balance_deducted: Decimal
    ip: str | None = None


class CompanyRequestCreate(CompanyRequestBase):
    """Create company request schema."""


class CompanyRequestRead(CompanyRequestBase):
    """Read company request schema."""

    id: int
    date: datetime

    model_config = ConfigDict(from_attributes=True)
