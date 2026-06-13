"""Dashboard service — all aggregations done in SQL (no N+1)."""

from datetime import datetime, timedelta, timezone
from decimal import Decimal

from sqlalchemy import func
from sqlalchemy.orm import Session

from app.api.v1.balance.service import get_balance
from app.models.company import APIKey, Company
from app.models.companyRequests import CompanyRequest
from app.schemas.dashboardSchema import (
    APIKeyInfo,
    CreditMetrics,
    DashboardMetrics,
    DashboardResponse,
    UsageMetrics,
)


def _utc_now() -> datetime:
    return datetime.now(timezone.utc)


def _agg_requests(db: Session, company_id: int, since: datetime) -> tuple[int, int, Decimal]:
    """Return (request_count, total_tokens, total_balance) since *since*."""
    row = (
        db.query(
            func.count(CompanyRequest.id),
            func.coalesce(func.sum(CompanyRequest.token_consume), 0),
            func.coalesce(func.sum(CompanyRequest.balance_deducted), Decimal("0")),
        )
        .filter(
            CompanyRequest.company_id == company_id,
            CompanyRequest.date >= since,
        )
        .one()
    )
    return int(row[0]), int(row[1]), Decimal(str(row[2]))


def getDashboardMetrics(db: Session, company_id: int) -> DashboardMetrics:
    now = _utc_now()
    today_start = now.replace(hour=0, minute=0, second=0, microsecond=0)
    week_start = today_start - timedelta(days=7)
    month_start = today_start - timedelta(days=30)

    def _metrics(since: datetime) -> UsageMetrics:
        reqs, tokens, balance = _agg_requests(db, company_id, since)
        return UsageMetrics(
            total_requests=reqs,
            total_tokens_consumed=tokens,
            total_balance_deducted=balance,
        )

    return DashboardMetrics(
        today=_metrics(today_start),
        weekly=_metrics(week_start),
        monthly=_metrics(month_start),
    )


def getCreditMetrics(db: Session, company_id: int) -> CreditMetrics:
    now = _utc_now()
    today_start = now.replace(hour=0, minute=0, second=0, microsecond=0)
    week_start = today_start - timedelta(days=7)
    month_start = today_start - timedelta(days=30)

    def _credit(since: datetime) -> Decimal:
        val = (
            db.query(func.sum(CompanyRequest.balance_deducted))
            .filter(
                CompanyRequest.company_id == company_id,
                CompanyRequest.date >= since,
            )
            .scalar()
        )
        return Decimal(str(val)) if val else Decimal("0")

    return CreditMetrics(today=_credit(today_start), weekly=_credit(week_start), monthly=_credit(month_start))


def getAPIKeyInfo(db: Session, company_id: int) -> tuple[list[APIKeyInfo], int, int]:
    api_keys = (
        db.query(APIKey)
        .filter(APIKey.company_id == company_id)
        .order_by(APIKey.created_at.desc())
        .all()
    )
    total = len(api_keys)
    active = sum(1 for k in api_keys if k.status == "active")
    infos = [
        APIKeyInfo(
            id=k.id,
            name=k.name,
            key_preview=k.key_preview,
            status=k.status,
            created_at=k.created_at,
            expiry_date=k.expiry_date,
        )
        for k in api_keys
    ]
    return infos, total, active


def getAllTimeMetrics(db: Session, company_id: int) -> tuple[int, Decimal, datetime | None]:
    row = (
        db.query(
            func.coalesce(func.sum(CompanyRequest.token_consume), 0),
            func.coalesce(func.sum(CompanyRequest.balance_deducted), Decimal("0")),
            func.max(CompanyRequest.date),
        )
        .filter(CompanyRequest.company_id == company_id)
        .one()
    )
    return int(row[0]), Decimal(str(row[1])), row[2]


def getDashboard(db: Session, company_id: int) -> DashboardResponse:
    company = db.query(Company).filter(Company.id == company_id).first()
    if not company:
        raise ValueError(f"Company {company_id} not found")

    api_keys, total_api_keys, active_api_keys = getAPIKeyInfo(db, company_id)
    usage_metrics = getDashboardMetrics(db, company_id)
    credit_metrics = getCreditMetrics(db, company_id)
    total_tokens, total_balance, last_request_at = getAllTimeMetrics(db, company_id)
    current_balance = get_balance(db, company_id)

    return DashboardResponse(
        company_id=company_id,
        company_name=company.company_name,
        model_name=company.company_model_name,
        total_api_keys=total_api_keys,
        active_api_keys=active_api_keys,
        api_keys=api_keys,
        usage_metrics=usage_metrics,
        credit_deducted=credit_metrics,
        last_request_at=last_request_at,
        total_tokens_all_time=total_tokens,
        total_balance_deducted_all_time=total_balance,
        current_balance=current_balance,
    )
