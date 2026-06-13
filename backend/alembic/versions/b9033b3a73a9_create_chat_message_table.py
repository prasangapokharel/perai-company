"""create chat_message table

Revision ID: b9033b3a73a9
Revises: 20260605_company_requests
Create Date: 2026-06-05 22:55:23.129055
"""

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = 'b9033b3a73a9'
down_revision = '20260605_company_requests'
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "chat_message",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column("company_id", sa.Integer(), sa.ForeignKey("company.id", ondelete="CASCADE"), nullable=False, index=True),
        sa.Column("session_id", sa.String(12), nullable=False, index=True),
        sa.Column("conversation", sa.Text(), nullable=False, server_default=""),
        sa.Column("review", sa.String(1), nullable=False, server_default="0"),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
        sa.Column("updated_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    )


def downgrade() -> None:
    op.drop_table("chat_message")
