"""Add api_key table.

Revision ID: 0002
Revises: 0001
Create Date: 2026-05-22 23:15:00.000000

"""

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = "0002"
down_revision = "0001"
branch_labels = None
depends_on = None


def upgrade() -> None:
    """Create api_key table."""
    op.create_table(
        "api_key",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("company_id", sa.Integer(), nullable=False),
        sa.Column("name", sa.String(length=255), nullable=False),
        sa.Column("key_hash", sa.String(length=512), nullable=False),
        sa.Column("key_preview", sa.String(length=20), nullable=False),
        sa.Column("status", sa.String(length=20), nullable=False, server_default="active"),
        sa.Column("expiry_date", sa.DateTime(timezone=True), nullable=True),
        sa.Column("last_used_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column(
            "created_at",
            sa.DateTime(timezone=True),
            server_default=sa.func.now(),
            nullable=False,
        ),
        sa.Column(
            "updated_at",
            sa.DateTime(timezone=True),
            server_default=sa.func.now(),
            nullable=False,
        ),
        sa.ForeignKeyConstraint(
            ["company_id"],
            ["company.id"],
            name="fk_api_key_company_id",
            ondelete="CASCADE",
        ),
        sa.PrimaryKeyConstraint("id", name="pk_api_key"),
        sa.UniqueConstraint("key_hash", name="uq_api_key_key_hash"),
        sa.UniqueConstraint("company_id", "name", name="uq_api_key_company_name"),
    )

    # Create indexes
    op.create_index("ix_api_key_company_id", "api_key", ["company_id"])
    op.create_index("ix_api_key_key_hash", "api_key", ["key_hash"])
    op.create_index("ix_api_key_status", "api_key", ["status"])
    op.create_index("ix_api_key_name", "api_key", ["name"])


def downgrade() -> None:
    """Drop api_key table."""
    op.drop_table("api_key")
