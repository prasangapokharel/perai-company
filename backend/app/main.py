"""Backend application entry point."""

import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.errors import RateLimitExceeded
from slowapi.middleware import SlowAPIMiddleware
from slowapi.util import get_remote_address

from app.api.v1.companyBalance.route import router as company_balance_router
from app.api.v1.balanceDeducted.route import router as balance_deducted_router
from app.api.v1.balance.route import router as balance_router
from app.api.v1.auth.route import router as auth_router
from app.api.v1.chat.route import router as chat_router
from app.api.v1.chatMessages.route import router as chat_messages_router
from app.api.v1.company.route import router as company_router
from app.api.v1.company.dashboard.route import router as dashboard_router
from app.api.v1.companyRequests.route import router as company_requests_router
from app.api.v1.apikey.route import router as apikey_router
from app.api.v1.ticket.route import router as ticket_router
from app.api.v1.companySettings.route import router as company_settings_router
from app.api.v1.admin.route import router as admin_router
from app.api.v1.files.route import router as files_router
from app.core.config.config import FRONTEND_URL, WIDGET_CORS_ENABLED
from app.core.database import init_db

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(name)s %(message)s",
)
log = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Rate limiter (slowapi)
# ---------------------------------------------------------------------------
limiter = Limiter(key_func=get_remote_address, default_limits=["120/minute"])


@asynccontextmanager
async def lifespan(app: FastAPI):
    init_db()
    log.info("Perai backend started — models registered")
    yield
    log.info("Perai backend shutting down")


# ---------------------------------------------------------------------------
# App
# ---------------------------------------------------------------------------
app = FastAPI(
    title="Perai Backend",
    description="Enterprise AI Platform — Company Module",
    version="2.0.0",
    lifespan=lifespan,
)

# Rate limiting
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)
app.add_middleware(SlowAPIMiddleware)

# CORS — explicit origins, not wildcard + credentials (that's a browser spec violation)
allowed_origins = [
    FRONTEND_URL,
    "http://localhost:3000",
    "http://127.0.0.1:3000",
]
app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins,
    allow_origin_regex=r"(https?://.*|null)" if WIDGET_CORS_ENABLED else None,
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ---------------------------------------------------------------------------
# Routers
# ---------------------------------------------------------------------------
app.include_router(auth_router)
app.include_router(balance_router)
app.include_router(company_balance_router)
app.include_router(balance_deducted_router)
app.include_router(company_router)
app.include_router(dashboard_router)
app.include_router(company_requests_router)
app.include_router(chat_router)
app.include_router(chat_messages_router)
app.include_router(apikey_router)
app.include_router(ticket_router)
app.include_router(company_settings_router)
app.include_router(admin_router)
app.include_router(files_router)


# ---------------------------------------------------------------------------
# Health check
# ---------------------------------------------------------------------------
@app.get("/", tags=["health"])
async def root():
    return {"status": "ok", "service": "perai-backend", "version": "2.0.0"}


# ---------------------------------------------------------------------------
# Global error handler — never leak stack traces to clients
# ---------------------------------------------------------------------------
@app.exception_handler(Exception)
async def unhandled_exception_handler(request: Request, exc: Exception):
    log.exception("Unhandled exception on %s %s", request.method, request.url)
    return JSONResponse(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        content={"detail": "An internal server error occurred."},
    )
