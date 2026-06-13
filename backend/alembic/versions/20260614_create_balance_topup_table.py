"""create balance_topup table

Revision ID: 20260614_balance_topup
Revises: 20260613_company_balance
Create Date: 2026-06-14 00:00:00.000000
"""

from alembic import op
import sqlalchemy as sa


revision = "20260614_balance_topup"
down_revision = "20260613_company_balance"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "balance_topup",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column("company_id", sa.Integer(), nullable=False),
        sa.Column("amount", sa.Numeric(14, 6), nullable=False),
        sa.Column("reference", sa.String(length=255), nullable=True),
        sa.Column(
            "created_at",
            sa.DateTime(timezone=True),
            nullable=False,
            server_default=sa.text("CURRENT_TIMESTAMP"),
        ),
        sa.ForeignKeyConstraint(
            ["company_id"],
            ["company.id"],
            ondelete="CASCADE",
            name="fk_balance_topup_company_id",
        ),
    )
    op.create_index("ix_balance_topup_company_id", "balance_topup", ["company_id"])


def downgrade() -> None:
    op.drop_index("ix_balance_topup_company_id", table_name="balance_topup")
    op.drop_table("balance_topup")
