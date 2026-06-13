"""create company_requests table

Revision ID: 20260605_company_requests
Revises: 4a625b86562b
Create Date: 2026-06-05 00:00:00.000000
"""

from alembic import op
import sqlalchemy as sa


revision = "20260605_company_requests"
down_revision = "4a625b86562b"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "company_requests",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column("company_id", sa.Integer(), nullable=False),
        sa.Column("token_consume", sa.Integer(), nullable=False, server_default="0"),
        sa.Column("balance_deducted", sa.Numeric(12, 6), nullable=False, server_default="0"),
        sa.Column("ip", sa.String(length=45), nullable=True),
        sa.Column(
            "date",
            sa.DateTime(),
            nullable=False,
            server_default=sa.text("CURRENT_TIMESTAMP"),
        ),
        sa.ForeignKeyConstraint(
            ["company_id"],
            ["company.id"],
            ondelete="CASCADE",
            name="fk_company_requests_company_id",
        ),
    )
    op.create_index("ix_company_requests_company_id", "company_requests", ["company_id"], unique=False)
    op.create_index("ix_company_requests_id", "company_requests", ["id"], unique=False)


def downgrade() -> None:
    op.drop_index("ix_company_requests_id", table_name="company_requests")
    op.drop_index("ix_company_requests_company_id", table_name="company_requests")
    op.drop_table("company_requests")
