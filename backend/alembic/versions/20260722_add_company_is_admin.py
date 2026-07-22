"""add is_admin to company

Revision ID: 20260722_company_is_admin
Revises: create_khalti_payment
Create Date: 2026-07-22 00:00:00.000000
"""

import sqlalchemy as sa
from alembic import op

revision = "20260722_company_is_admin"
down_revision = "create_khalti_payment"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.add_column(
        "company",
        sa.Column(
            "is_admin",
            sa.Boolean(),
            nullable=False,
            server_default=sa.text("false"),
        ),
    )


def downgrade() -> None:
    op.drop_column("company", "is_admin")
