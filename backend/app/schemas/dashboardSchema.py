"""Dashboard response schemas."""

from datetime import datetime
from decimal import Decimal

from pydantic import BaseModel, ConfigDict


class UsageMetrics(BaseModel):
    """Usage metrics for a time period."""

    total_requests: int
    total_tokens_consumed: int
    total_balance_deducted: Decimal


class CreditMetrics(BaseModel):
    """Credit deduction metrics for a time period."""

    today: Decimal
    weekly: Decimal
    monthly: Decimal


class APIKeyInfo(BaseModel):
    """API Key information."""

    id: int
    name: str
    key_preview: str
    status: str
    created_at: datetime
    expiry_date: datetime | None = None

    model_config = ConfigDict(from_attributes=True)


class DashboardMetrics(BaseModel):
    """Dashboard metrics for today, weekly, and monthly."""

    today: UsageMetrics
    weekly: UsageMetrics
    monthly: UsageMetrics


class DashboardResponse(BaseModel):
    """Complete dashboard response with all metrics and API keys."""

    company_id: int
    company_name: str
    model_name: str | None = None

    total_api_keys: int
    active_api_keys: int
    api_keys: list[APIKeyInfo]

    usage_metrics: DashboardMetrics
    credit_deducted: CreditMetrics

    last_request_at: datetime | None = None
    total_tokens_all_time: int
    total_balance_deducted_all_time: Decimal
    current_balance: Decimal

    model_config = ConfigDict(from_attributes=True)
