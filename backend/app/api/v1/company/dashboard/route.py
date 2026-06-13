"""Dashboard endpoint — protected by company ownership."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.company.dashboard.service import getDashboard
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.dashboardSchema import DashboardResponse

router = APIRouter(prefix="/api/v1/company", tags=["dashboard"])


@router.get("/{company_id}/dashboard", response_model=DashboardResponse)
def get_dashboard_endpoint(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
) -> DashboardResponse:
    """Comprehensive company dashboard: usage metrics, credits, API keys."""
    try:
        return getDashboard(db, company_id)
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc)) from exc
    except Exception as exc:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to fetch dashboard data.",
        ) from exc
