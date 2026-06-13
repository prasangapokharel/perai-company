"""create company_balance and balance_deduct tables

Revision ID: 20260613_company_balance
Revises: 20260612_pgvector
Create Date: 2026-06-13 00:00:00.000000
"""

from alembic import op
import sqlalchemy as sa


revision = "20260613_company_balance"
down_revision = "20260612_pgvector"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "company_balance",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column("company_id", sa.Integer(), nullable=False),
        sa.Column("balance", sa.Numeric(14, 6), nullable=False, server_default="0"),
        sa.Column(
            "updated_at",
            sa.DateTime(timezone=True),
            nullable=False,
            server_default=sa.text("CURRENT_TIMESTAMP"),
        ),
        sa.ForeignKeyConstraint(
            ["company_id"],
            ["company.id"],
            ondelete="CASCADE",
            name="fk_company_balance_company_id",
        ),
        sa.UniqueConstraint("company_id", name="uq_company_balance_company_id"),
    )
    op.create_index("ix_company_balance_company_id", "company_balance", ["company_id"])

    op.create_table(
        "balance_deduct",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column("company_id", sa.Integer(), nullable=False),
        sa.Column("chat_message_id", sa.Integer(), nullable=True),
        sa.Column("session_id", sa.String(length=12), nullable=True),
        sa.Column("amount", sa.Numeric(14, 6), nullable=False),
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
            name="fk_balance_deduct_company_id",
        ),
        sa.ForeignKeyConstraint(
            ["chat_message_id"],
            ["chat_message.id"],
            ondelete="SET NULL",
            name="fk_balance_deduct_chat_message_id",
        ),
    )
    op.create_index("ix_balance_deduct_company_id", "balance_deduct", ["company_id"])
    op.create_index("ix_balance_deduct_chat_message_id", "balance_deduct", ["chat_message_id"])
    op.create_index("ix_balance_deduct_session_id", "balance_deduct", ["session_id"])

    op.execute(
        """
        INSERT INTO company_balance (company_id, balance)
        SELECT c.id, 10.000000 FROM company c
        WHERE NOT EXISTS (
            SELECT 1 FROM company_balance b WHERE b.company_id = c.id
        )
        """
    )


def downgrade() -> None:
    op.drop_index("ix_balance_deduct_session_id", table_name="balance_deduct")
    op.drop_index("ix_balance_deduct_chat_message_id", table_name="balance_deduct")
    op.drop_index("ix_balance_deduct_company_id", table_name="balance_deduct")
    op.drop_table("balance_deduct")
    op.drop_index("ix_company_balance_company_id", table_name="company_balance")
    op.drop_table("company_balance")
