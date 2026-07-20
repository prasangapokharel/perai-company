"""Application configuration — all settings loaded from environment."""

from pathlib import Path
from os import getenv
from dotenv import load_dotenv

_env_file = Path(__file__).resolve().parents[3] / ".env"
load_dotenv(_env_file)

BASE_DIR: Path = Path(__file__).resolve().parents[3]

SUPABASE_URL: str = getenv("SUPABASE_URL", "")
SUPABASE_KEY: str = getenv("SUPABASE_KEY", "")
SUPABASE_PASSWORD: str = getenv("SUPABASE_PASSWORD", "")

DB_HOST: str = getenv("DB_HOST", "aws-1-ap-south-1.pooler.supabase.com")
DB_HOST_FALLBACK: str = getenv("DB_HOST_FALLBACK", "db.lxopuyaxcxrglkfcbree.supabase.co")
DB_PORT: str = getenv("DB_PORT", "5432")
DB_NAME: str = getenv("DB_NAME", "postgres")
DB_USER: str = getenv("DB_USER", "postgres.lxopuyaxcxrglkfcbree")
DB_USER_DIRECT: str = getenv("DB_USER_DIRECT", "postgres")


def _build_postgres_url(user: str, host: str, password: str) -> str:
    from urllib.parse import quote_plus

    if not password:
        return ""
    safe_password = quote_plus(password)
    return f"postgresql://{user}:{safe_password}@{host}:{DB_PORT}/{DB_NAME}"


def _load_database_urls() -> tuple[str, str | None]:
    explicit = getenv("DB_URL", "").strip()
    explicit_fallback = getenv("DB_URL_FALLBACK", "").strip()

    pooler_url = explicit or _build_postgres_url(DB_USER, DB_HOST, SUPABASE_PASSWORD)
    direct_url = explicit_fallback or _build_postgres_url(
        DB_USER_DIRECT, DB_HOST_FALLBACK, SUPABASE_PASSWORD
    )

    if pooler_url:
        primary = pooler_url
    elif direct_url:
        primary = direct_url
        direct_url = None
    else:
        primary = f"sqlite:///{BASE_DIR / 'perai.db'}"

    fallback = direct_url if direct_url and direct_url != primary else None
    return primary, fallback


DATABASE_URL, DATABASE_URL_FALLBACK = _load_database_urls()

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

TTS_VOICE: str = getenv("TTS_VOICE", "M4")
TTS_MAX_CHARS: int = int(getenv("TTS_MAX_CHARS", "1200"))

DEFAULT_MAX_TOKENS: int = int(getenv("DEFAULT_MAX_TOKENS", "350"))
CHAT_COMPLETION_CAP: int = int(getenv("CHAT_COMPLETION_CAP", "400"))

KHALTI_SECRET_KEY: str = getenv("KHALTI_SECRET_KEY", "")
KHALTI_BASE_URL: str = getenv("KHALTI_BASE_URL", "https://dev.khalti.com/api/v2").rstrip("/")
KHALTI_USD_TO_NPR: str = getenv("KHALTI_USD_TO_NPR", "140")
