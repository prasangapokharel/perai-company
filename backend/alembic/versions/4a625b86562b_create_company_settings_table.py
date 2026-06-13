"""create company_settings table

Revision ID: 4a625b86562b
Revises: fix_ticket_defaults
Create Date: 2026-05-31 16:58:09.763295
"""

from alembic import op
import sqlalchemy as sa


revision = '4a625b86562b'
down_revision = 'fix_ticket_defaults'
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        'company_settings',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('company_id', sa.Integer(), nullable=False),
        sa.Column('language', sa.String(length=50), nullable=False, server_default='english'),
        sa.Column('tone', sa.String(length=50), nullable=False, server_default='formal'),
        sa.Column('max_tokens', sa.Integer(), nullable=False, server_default='1000'),
        sa.Column('created_at', sa.DateTime(), nullable=False, server_default=sa.text('now()')),
        sa.Column(
            'updated_at',
            sa.DateTime(),
            nullable=False,
            server_default=sa.text('now()'),
        ),
        sa.ForeignKeyConstraint(['company_id'], ['company.id'], ondelete='CASCADE'),
        sa.PrimaryKeyConstraint('id'),
        sa.UniqueConstraint('company_id', name='uq_company_settings_company_id'),
        if_not_exists=True,
    )
    op.create_index('ix_company_settings_id', 'company_settings', ['id'], unique=False, if_not_exists=True)
    op.create_index('ix_company_settings_company_id', 'company_settings', ['company_id'], unique=False, if_not_exists=True)


def downgrade() -> None:
    op.drop_index('ix_company_settings_company_id', table_name='company_settings', if_exists=True)
    op.drop_index('ix_company_settings_id', table_name='company_settings', if_exists=True)
    op.drop_table('company_settings', if_exists=True)
