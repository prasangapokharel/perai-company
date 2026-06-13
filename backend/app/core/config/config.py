"""Application configuration — all settings loaded from environment."""

from pathlib import Path
from os import getenv
from dotenv import load_dotenv

_env_file = Path(__file__).resolve().parents[3] / ".env"
load_dotenv(_env_file)

BASE_DIR: Path = Path(__file__).resolve().parents[3]

DATABASE_URL: str = getenv("DB_URL", f"sqlite:///{BASE_DIR / 'perai.db'}")

JWT_SECRET: str = getenv("JWT_SECRET", "changeme-please-set-JWT_SECRET-in-env")
JWT_ALGORITHM: str = "HS256"
JWT_EXPIRE_MINUTES: int = int(getenv("JWT_EXPIRE_MINUTES", "60"))

STORAGE_BASE: Path = Path(getenv("STORAGE_PATH", str(BASE_DIR / "storage" / "companies")))

FRONTEND_URL: str = getenv("FRONTEND_URL", "http://localhost:3000")


def _load_groq_api_keys() -> list[str]:
    keys: list[str] = []
    primary = getenv("GROQ_API_KEY", "").strip()
    if primary:
        keys.append(primary)
    for index in range(2, 6):
        value = getenv(f"GROQ_API_KEY{index}", "").strip()
        if value and value not in keys:
            keys.append(value)
    return keys


GROQ_API_KEYS: list[str] = _load_groq_api_keys()
GROQ_API_KEY: str = GROQ_API_KEYS[0] if GROQ_API_KEYS else ""
GROQ_MODEL: str = getenv("GROQ_MODEL", "llama-3.3-70b-versatile")
GROQ_MODEL_INPUT_COST: str = getenv("GROQ_MODEL_INPUT_COST", "0.0003")
GROQ_MODEL_OUTPUT_COST: str = getenv("GROQ_MODEL_OUTPUT_COST", "0.0003")

DEFAULT_COMPANY_BALANCE: str = getenv("DEFAULT_COMPANY_BALANCE", "10.00")

RATE_LIMIT_CHAT: str = getenv("RATE_LIMIT_CHAT", "60/minute")
RATE_LIMIT_AUTH: str = getenv("RATE_LIMIT_AUTH", "10/minute")
RATE_LIMIT_DEFAULT: str = getenv("RATE_LIMIT_DEFAULT", "120/minute")

VECTOR_RAG_ENABLED: bool = getenv("VECTOR_RAG_ENABLED", "false").lower() == "true"
WIDGET_CORS_ENABLED: bool = getenv("WIDGET_CORS_ENABLED", "true").lower() == "true"

RAG_TOP_K: int = int(getenv("RAG_TOP_K", "3"))
RAG_MAX_CONTEXT_CHARS: int = int(getenv("RAG_MAX_CONTEXT_CHARS", "1200"))
RAG_RECORD_MAX_CHARS: int = int(getenv("RAG_RECORD_MAX_CHARS", "360"))
