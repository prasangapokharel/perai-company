"""Database setup."""

from collections.abc import Generator

from sqlalchemy import create_engine
from sqlalchemy.orm import Session, declarative_base, sessionmaker

from app.core.config.config import DATABASE_URL


connect_args = {"check_same_thread": False} if DATABASE_URL.startswith("sqlite") else {}

engine = create_engine(DATABASE_URL, connect_args=connect_args, future=True)

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
