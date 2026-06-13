"""Alembic environment configuration."""

from __future__ import annotations

from logging.config import fileConfig

from alembic import context
from sqlalchemy import engine_from_config, pool

from app.core.database import Base

# Import ALL models so autogenerate detects every table
import app.models.balance  # noqa: F401
import app.models.balance_deduct  # noqa: F401
import app.models.balance_topup  # noqa: F401
import app.models.chatMessage  # noqa: F401
import app.models.company  # noqa: F401
import app.models.companyRequests  # noqa: F401
import app.models.companySettings  # noqa: F401
import app.models.ticket  # noqa: F401


config = context.config

if config.config_file_name is not None:
    fileConfig(config.config_file_name)

target_metadata = Base.metadata


def _database_url() -> str:
    from app.core.config.config import DATABASE_URL

    return DATABASE_URL


def run_migrations_offline() -> None:
    url = _database_url()
    context.configure(
        url=url,
        target_metadata=target_metadata,
        literal_binds=True,
        dialect_opts={"paramstyle": "named"},
    )
    with context.begin_transaction():
        context.run_migrations()


def run_migrations_online() -> None:
    configuration = config.get_section(config.config_ini_section) or {}
    configuration["sqlalchemy.url"] = _database_url()

    connectable = engine_from_config(
        configuration,
        prefix="sqlalchemy.",
        poolclass=pool.NullPool,
    )

    with connectable.connect() as connection:
        context.configure(connection=connection, target_metadata=target_metadata)
        with context.begin_transaction():
            context.run_migrations()


if context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()
