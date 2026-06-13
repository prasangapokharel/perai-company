"""add token_consume and model_name to chat_message and balance_deduct

Revision ID: 20260615_token_model
Revises: 20260614_balance_topup
Create Date: 2026-06-15 00:00:00.000000
"""

from alembic import op
import sqlalchemy as sa


revision = "20260615_token_model"
down_revision = "20260614_balance_topup"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.add_column(
        "chat_message",
        sa.Column("token_consume", sa.Integer(), nullable=False, server_default="0"),
    )
    op.add_column(
        "chat_message",
        sa.Column("model_name", sa.String(length=255), nullable=True),
    )
    op.add_column(
        "balance_deduct",
        sa.Column("token_consume", sa.Integer(), nullable=False, server_default="0"),
    )
    op.add_column(
        "balance_deduct",
        sa.Column("model_name", sa.String(length=255), nullable=True),
    )


def downgrade() -> None:
    op.drop_column("balance_deduct", "model_name")
    op.drop_column("balance_deduct", "token_consume")
    op.drop_column("chat_message", "model_name")
    op.drop_column("chat_message", "token_consume")
