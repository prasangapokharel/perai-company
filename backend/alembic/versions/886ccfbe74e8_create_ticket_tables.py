"""create ticket tables

Revision ID: 886ccfbe74e8
Revises: f920af34db03
Create Date: 2026-05-31 16:07:49.117741
"""

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


revision = '886ccfbe74e8'
down_revision = 'f920af34db03'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Create ticket_category and ticket_status enum types (safe)
    ticket_category = postgresql.ENUM(
        'payment', 'technical', 'general',
        name='ticketcategory',
        create_type=False,
    )
    ticket_status = postgresql.ENUM(
        'open', 'closed',
        name='ticketstatus',
        create_type=False,
    )
    ticket_category.create(op.get_bind(), checkfirst=True)
    ticket_status.create(op.get_bind(), checkfirst=True)

    op.create_table(
        'ticket',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('company_id', sa.Integer(), nullable=False),
        sa.Column('issue', sa.Text(), nullable=False),
        sa.Column(
            'category',
            sa.Enum('payment', 'technical', 'general', name='ticketcategory'),
            nullable=False,
            server_default='general',
        ),
        sa.Column(
            'status',
            sa.Enum('open', 'closed', name='ticketstatus'),
            nullable=False,
            server_default='open',
        ),
        sa.Column('created_at', sa.DateTime(), nullable=False, server_default=sa.text('now()')),
        sa.Column(
            'updated_at',
            sa.DateTime(),
            nullable=False,
            server_default=sa.text('now()'),
        ),
        sa.ForeignKeyConstraint(['company_id'], ['company.id'], ondelete='CASCADE'),
        sa.PrimaryKeyConstraint('id'),
        if_not_exists=True,
    )
    op.create_index('ix_ticket_id', 'ticket', ['id'], unique=False, if_not_exists=True)
    op.create_index('ix_ticket_company_id', 'ticket', ['company_id'], unique=False, if_not_exists=True)

    op.create_table(
        'ticket_opened',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('company_id', sa.Integer(), nullable=False),
        sa.Column('ticket_id', sa.Integer(), nullable=False),
        sa.Column('opened_at', sa.DateTime(), nullable=False),
        sa.Column('closed_at', sa.DateTime(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=False, server_default=sa.text('now()')),
        sa.Column(
            'updated_at',
            sa.DateTime(),
            nullable=False,
            server_default=sa.text('now()'),
        ),
        sa.ForeignKeyConstraint(['company_id'], ['company.id'], ondelete='CASCADE'),
        sa.ForeignKeyConstraint(['ticket_id'], ['ticket.id'], ondelete='CASCADE'),
        sa.PrimaryKeyConstraint('id'),
        if_not_exists=True,
    )
    op.create_index('ix_ticket_opened_id', 'ticket_opened', ['id'], unique=False, if_not_exists=True)
    op.create_index('ix_ticket_opened_company_id', 'ticket_opened', ['company_id'], unique=False, if_not_exists=True)
    op.create_index('ix_ticket_opened_ticket_id', 'ticket_opened', ['ticket_id'], unique=False, if_not_exists=True)


def downgrade() -> None:
    op.drop_index('ix_ticket_opened_ticket_id', table_name='ticket_opened', if_exists=True)
    op.drop_index('ix_ticket_opened_company_id', table_name='ticket_opened', if_exists=True)
    op.drop_index('ix_ticket_opened_id', table_name='ticket_opened', if_exists=True)
    op.drop_table('ticket_opened', if_exists=True)
    op.drop_index('ix_ticket_company_id', table_name='ticket', if_exists=True)
    op.drop_index('ix_ticket_id', table_name='ticket', if_exists=True)
    op.drop_table('ticket', if_exists=True)
    op.execute("DROP TYPE IF EXISTS ticketcategory")
    op.execute("DROP TYPE IF EXISTS ticketstatus")
