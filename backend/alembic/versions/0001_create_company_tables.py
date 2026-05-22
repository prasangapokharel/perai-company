"""create company and company_finetune tables

Revision ID: 0001
Revises: 
Create Date: 2026-05-22 00:00:00.000000
"""

from alembic import op
import sqlalchemy as sa


revision = "0001"
down_revision = None
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "company",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column("company_name", sa.String(length=255), nullable=False),
        sa.Column("company_email", sa.String(length=255), nullable=False),
        sa.Column("password_hash", sa.String(length=255), nullable=False),
        sa.Column("logo", sa.String(length=500), nullable=True),
        sa.Column("website", sa.String(length=500), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("updated_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.UniqueConstraint("company_name", name="uq_company_company_name"),
        sa.UniqueConstraint("company_email", name="uq_company_company_email"),
    )
    op.create_index("ix_company_company_name", "company", ["company_name"], unique=False)
    op.create_index("ix_company_company_email", "company", ["company_email"], unique=False)

    op.create_table(
        "company_finetune",
        sa.Column("id", sa.Integer(), primary_key=True),
        sa.Column("company_id", sa.Integer(), nullable=False),
        sa.Column("rag_company_path", sa.String(length=1000), nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("updated_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["company_id"], ["company.id"], ondelete="CASCADE"),
        sa.UniqueConstraint("company_id", name="uq_company_finetune_company_id"),
    )
    op.create_index("ix_company_finetune_company_id", "company_finetune", ["company_id"], unique=False)


def downgrade() -> None:
    op.drop_index("ix_company_finetune_company_id", table_name="company_finetune")
    op.drop_table("company_finetune")
    op.drop_index("ix_company_company_email", table_name="company")
    op.drop_index("ix_company_company_name", table_name="company")
    op.drop_table("company")
