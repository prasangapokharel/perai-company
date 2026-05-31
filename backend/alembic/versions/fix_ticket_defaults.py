"""add defaults to ticket updated_at columns

Revision ID: fix_ticket_defaults
Revises: 886ccfbe74e8
Create Date: 2026-05-31 16:15:00.000000
"""

from alembic import op
import sqlalchemy as sa
from sqlalchemy.sql import text


# revision identifiers, used by Alembic.
revision = 'fix_ticket_defaults'
down_revision = '886ccfbe74e8'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Add default value to ticket.updated_at
    op.execute(text("ALTER TABLE ticket ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP"))
    # Add default value to ticket_opened.updated_at
    op.execute(text("ALTER TABLE ticket_opened ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP"))


def downgrade() -> None:
    # Remove default values
    op.execute(text("ALTER TABLE ticket ALTER COLUMN updated_at DROP DEFAULT"))
    op.execute(text("ALTER TABLE ticket_opened ALTER COLUMN updated_at DROP DEFAULT"))
