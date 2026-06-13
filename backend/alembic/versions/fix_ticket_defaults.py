"""add defaults to ticket updated_at columns

Revision ID: fix_ticket_defaults
Revises: 886ccfbe74e8
Create Date: 2026-05-31 16:15:00.000000
"""

from alembic import op
from sqlalchemy.sql import text


revision = 'fix_ticket_defaults'
down_revision = '886ccfbe74e8'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Tables were created by the previous migration with server_default already set.
    # These ALTER statements are idempotent — safe to run even if default exists.
    conn = op.get_bind()
    conn.execute(text(
        "ALTER TABLE ticket ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP"
    ))
    conn.execute(text(
        "ALTER TABLE ticket_opened ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP"
    ))


def downgrade() -> None:
    conn = op.get_bind()
    conn.execute(text("ALTER TABLE ticket ALTER COLUMN updated_at DROP DEFAULT"))
    conn.execute(text("ALTER TABLE ticket_opened ALTER COLUMN updated_at DROP DEFAULT"))
