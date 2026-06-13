"""add ip to chat_message

Revision ID: 20260616_chat_message_ip
Revises: 20260615_token_model
Create Date: 2026-06-16 00:00:00.000000
"""

from alembic import op
import sqlalchemy as sa


revision = "20260616_chat_message_ip"
down_revision = "20260615_token_model"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.add_column("chat_message", sa.Column("ip", sa.String(length=45), nullable=True))


def downgrade() -> None:
    op.drop_column("chat_message", "ip")
