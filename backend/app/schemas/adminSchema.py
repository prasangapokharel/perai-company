"""Admin schemas — platform-wide administration."""

from datetime import datetime
from decimal import Decimal

from pydantic import BaseModel, ConfigDict, Field


# ---------------------------------------------------------------------------
# Overview / stats
# ---------------------------------------------------------------------------


class AdminOverview(BaseModel):
    total_companies: int
    admin_companies: int
    total_balance: Decimal
    total_topups: Decimal
    total_deducted: Decimal
    total_tickets: int
    open_tickets: int
    total_api_keys: int
    active_api_keys: int
    total_khalti_payments: int
    completed_khalti_payments: int


# ---------------------------------------------------------------------------
# Companies
# ---------------------------------------------------------------------------


class AdminCompanyRead(BaseModel):
    id: int
    company_name: str
    company_email: str
    logo: str | None = None
    website: str | None = None
    is_admin: bool = False
    balance: Decimal = Decimal("0")
    created_at: datetime
    updated_at: datetime

    model_config = ConfigDict(from_attributes=True)


class AdminCompanyUpdate(BaseModel):
    company_name: str | None = Field(default=None, min_length=1, max_length=255)
    company_email: str | None = Field(default=None, min_length=3, max_length=255)
    password: str | None = Field(default=None, min_length=8, max_length=255)
    logo: str | None = Field(default=None, max_length=500)
    website: str | None = Field(default=None, max_length=500)


class AdminSetRole(BaseModel):
    is_admin: bool


# ---------------------------------------------------------------------------
# Balance
# ---------------------------------------------------------------------------


class AdminBalanceAdjust(BaseModel):
    amount: Decimal = Field(..., description="USD delta; positive credits, negative debits")
    reason: str | None = Field(default=None, max_length=255)


class AdminKhaltiPaymentRead(BaseModel):
    id: int
    company_id: int
    pidx: str
    amount_usd: Decimal
    amount_npr_paisa: int
    status: str
    transaction_id: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = ConfigDict(from_attributes=True)
