"""add pgvector document_chunks table for vector RAG

Revision ID: 20260612_pgvector
Revises: b9033b3a73a9
Create Date: 2026-06-12 00:00:00.000000
"""

from alembic import op
import sqlalchemy as sa


revision = '20260612_pgvector'
down_revision = 'b9033b3a73a9'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Enable pgvector extension (Supabase has it pre-installed)
    op.execute("CREATE EXTENSION IF NOT EXISTS vector")

    op.create_table(
        'document_chunks',
        sa.Column('id', sa.BigInteger(), nullable=False),
        sa.Column('company_id', sa.Integer(), nullable=False),
        sa.Column('chunk_index', sa.Integer(), nullable=False),
        sa.Column('chunk_text', sa.Text(), nullable=False),
        # 384-dimensional vector for BAAI/bge-small-en-v1.5
        sa.Column('embedding', sa.Text(), nullable=True),
        sa.Column('created_at', sa.DateTime(timezone=True), nullable=False, server_default=sa.text('now()')),
        sa.ForeignKeyConstraint(['company_id'], ['company.id'], ondelete='CASCADE'),
        sa.PrimaryKeyConstraint('id'),
        if_not_exists=True,
    )
    op.create_index('ix_doc_chunks_company_id', 'document_chunks', ['company_id'], unique=False, if_not_exists=True)

    # Add the actual VECTOR column via raw SQL (pgvector type not in sqlalchemy by default)
    op.execute(
        "ALTER TABLE document_chunks "
        "ALTER COLUMN embedding TYPE vector(384) USING embedding::vector"
        if False  # only run if column already exists as text
        else "SELECT 1"  # no-op, column is already vector from CREATE TABLE
    )

    # Re-create with proper vector type via raw SQL
    op.execute("DROP TABLE IF EXISTS document_chunks")
    op.execute("""
        CREATE TABLE IF NOT EXISTS document_chunks (
            id BIGSERIAL PRIMARY KEY,
            company_id INTEGER NOT NULL REFERENCES company(id) ON DELETE CASCADE,
            chunk_index INTEGER NOT NULL,
            chunk_text TEXT NOT NULL,
            embedding vector(384),
            created_at TIMESTAMPTZ NOT NULL DEFAULT now()
        )
    """)
    op.execute(
        "CREATE INDEX IF NOT EXISTS ix_doc_chunks_company_id "
        "ON document_chunks (company_id)"
    )
    # IVFFlat index for fast approximate nearest-neighbour search
    op.execute(
        "CREATE INDEX IF NOT EXISTS ix_doc_chunks_embedding "
        "ON document_chunks USING ivfflat (embedding vector_cosine_ops) "
        "WITH (lists = 100)"
    )


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS document_chunks")
