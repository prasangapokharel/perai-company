"""create khalti_payment table

Revision ID: create_khalti_payment
Revises: 20260616_chat_message_ip
Create Date: 2026-07-20 00:00:00.000000
"""

import sqlalchemy as sa
from alembic import op

revision = "create_khalti_payment"
down_revision = "20260616_chat_message_ip"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "khalti_payment",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column(
            "company_id",
            sa.Integer(),
            sa.ForeignKey("company.id", ondelete="CASCADE"),
            nullable=False,
        ),
        sa.Column("pidx", sa.String(length=64), nullable=False),
        sa.Column("amount_usd", sa.Numeric(14, 6), nullable=False),
        sa.Column("amount_npr_paisa", sa.Integer(), nullable=False),
        sa.Column("status", sa.String(length=32), nullable=False, server_default="Initiated"),
        sa.Column("transaction_id", sa.String(length=128), nullable=True),
        sa.Column(
            "created_at",
            sa.DateTime(timezone=True),
            nullable=False,
            server_default=sa.text("CURRENT_TIMESTAMP"),
        ),
        sa.Column(
            "updated_at",
            sa.DateTime(timezone=True),
            nullable=False,
            server_default=sa.text("CURRENT_TIMESTAMP"),
        ),
    )
    op.create_index("ix_khalti_payment_company_id", "khalti_payment", ["company_id"])
    op.create_index("ix_khalti_payment_pidx", "khalti_payment", ["pidx"], unique=True)


def downgrade() -> None:
    op.drop_index("ix_khalti_payment_pidx", table_name="khalti_payment")
    op.drop_index("ix_khalti_payment_company_id", table_name="khalti_payment")
    op.drop_table("khalti_payment")
