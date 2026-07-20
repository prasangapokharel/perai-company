from datetime import datetime
from decimal import Decimal

from pydantic import BaseModel, ConfigDict, Field


class CompanyBalanceRead(BaseModel):
    company_id: int
    balance: Decimal
    currency: str = "USD"

    model_config = ConfigDict(from_attributes=True)


class BalanceTopupCreate(BaseModel):
    amount: Decimal = Field(..., gt=0, le=500, description="USD credit amount (max 500)")


class BalanceTopupRead(BaseModel):
    id: int
    company_id: int
    amount: Decimal
    reference: str | None = None
    created_at: datetime

    model_config = ConfigDict(from_attributes=True)


class BalanceDeductRead(BaseModel):
    id: int
    company_id: int
    chat_message_id: int | None = None
    session_id: str | None = None
    amount: Decimal
    token_consume: int = 0
    model_name: str | None = None
    created_at: datetime

    model_config = ConfigDict(from_attributes=True)


class KhaltiInitiateCreate(BaseModel):
    amount: Decimal = Field(..., gt=0, le=500, description="USD credit amount (max 500)")


class KhaltiInitiateRead(BaseModel):
    pidx: str
    payment_url: str
    amount_usd: Decimal
    amount_npr_paisa: int
    status: str

    model_config = ConfigDict(from_attributes=True)


class KhaltiVerifyCreate(BaseModel):
    pidx: str = Field(..., min_length=1, max_length=64)


class KhaltiVerifyRead(BaseModel):
    pidx: str
    status: str
    amount_usd: Decimal
    balance: Decimal
    currency: str = "USD"


class AuthMeResponse(BaseModel):
    company_id: int
    company_name: str
    company_email: str
    balance: Decimal
    currency: str = "USD"

    model_config = ConfigDict(from_attributes=True)
