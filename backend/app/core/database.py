"""Database setup."""

from collections.abc import Generator

from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session, declarative_base, sessionmaker

from app.core.config.config import DATABASE_URL, DATABASE_URL_FALLBACK


def _probe_engine(url: str):
    candidate = create_engine(
        url,
        connect_args={"check_same_thread": False} if url.startswith("sqlite") else {},
        future=True,
        pool_pre_ping=True,
    )
    if url.startswith("sqlite"):
        return candidate
    with candidate.connect() as conn:
        conn.execute(text("SELECT 1"))
    return candidate


def _create_engine():
    urls = [DATABASE_URL]
    if DATABASE_URL_FALLBACK:
        urls.append(DATABASE_URL_FALLBACK)

    last_error: Exception | None = None
    for url in urls:
        try:
            return _probe_engine(url)
        except Exception as err:
            last_error = err
            continue

    if last_error:
        raise last_error
    raise RuntimeError("No database URL configured")


engine = _create_engine()

if DATABASE_URL.startswith("sqlite"):
    from sqlalchemy import event

    @event.listens_for(engine, "connect")
    def _set_sqlite_pragma(dbapi_connection, _connection_record):
        cursor = dbapi_connection.cursor()
        cursor.execute("PRAGMA foreign_keys=ON")
        cursor.close()

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine, future=True)
Base = declarative_base()


def get_db() -> Generator[Session, None, None]:
    """Yield a DB session and close it after the request."""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


# Backward-compat alias
getDb = get_db


def init_db() -> None:
    """Import all models so Alembic's autogenerate can see them.

    NOTE: We do NOT call create_all() here — schema changes are managed
    exclusively through Alembic migrations.
    """
    import app.models.chatMessage  # noqa: F401
    import app.models.company  # noqa: F401
    import app.models.companyRequests  # noqa: F401
    import app.models.companySettings  # noqa: F401
    import app.models.ticket  # noqa: F401
