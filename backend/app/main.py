"""Backend application entry point."""

from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.api.v1.chat.route import router as chat_router
from app.api.v1.company.route import router as company_router
from app.api.v1.apikey.route import router as apikey_router
from app.core.database import init_db


@asynccontextmanager
async def lifespan(app: FastAPI):
    init_db()
    yield


app = FastAPI(
    title="Perai Backend",
    description="Enterprise AI Platform - Company Module",
    version="1.0.0",
    lifespan=lifespan,
)

# CORS Configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(company_router)
app.include_router(chat_router)
app.include_router(apikey_router)


@app.get("/")
async def root():
    """Health check endpoint."""
    return {"status": "ok", "service": "perai-backend", "version": "1.0.0"}
