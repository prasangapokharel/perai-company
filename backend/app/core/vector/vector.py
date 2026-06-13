"""Vector-based RAG using pgvector + fastembed.

On first use the BAAI/bge-small-en-v1.5 ONNX model (~130 MB) is
downloaded and cached in ~/.cache/fastembed. All subsequent calls are
instant. Embedding dimension: 384.

Public API
----------
embed_and_store(db, company_id, content)
    Chunk the content, embed every chunk, delete old chunks for this
    company, then bulk-insert the new ones.

retrieve_context(db, company_id, query, top_k=5)
    Embed the query and return the top-k most similar chunks as a
    single string (suitable for use as RAG context in a system prompt).
"""

from __future__ import annotations

import logging
import re
from typing import Any

from sqlalchemy import text
from sqlalchemy.orm import Session

log = logging.getLogger(__name__)

# Chunk settings
_CHUNK_SIZE = 512      # characters
_CHUNK_OVERLAP = 80
_TOP_K = 5
_EMBED_DIM = 384
_MODEL_NAME = "BAAI/bge-small-en-v1.5"

# Lazy-loaded encoder instance
_encoder: Any = None


def _get_encoder() -> Any:
    global _encoder
    if _encoder is None:
        try:
            from fastembed import TextEmbedding
            _encoder = TextEmbedding(model_name=_MODEL_NAME)
            log.info("fastembed encoder loaded: %s", _MODEL_NAME)
        except ImportError:
            log.warning("fastembed not installed — vector RAG disabled")
            return None
    return _encoder


def _chunk_text(text_content: str) -> list[str]:
    """Split text into overlapping character-level chunks."""
    text_content = text_content.strip()
    if not text_content:
        return []

    paragraphs = re.split(r"\n{2,}", text_content)
    chunks: list[str] = []
    buffer = ""

    for para in paragraphs:
        para = para.strip()
        if not para:
            continue
        if len(buffer) + len(para) + 1 <= _CHUNK_SIZE:
            buffer = (buffer + "\n\n" + para).strip()
        else:
            if buffer:
                chunks.append(buffer)
                buffer = buffer[-_CHUNK_OVERLAP:].strip()
            if len(para) <= _CHUNK_SIZE:
                buffer = (buffer + "\n\n" + para).strip()
            else:
                for sent in re.split(r"(?<=[.!?])\s+", para):
                    if len(buffer) + len(sent) + 1 <= _CHUNK_SIZE:
                        buffer = (buffer + " " + sent).strip()
                    else:
                        if buffer:
                            chunks.append(buffer)
                            buffer = buffer[-_CHUNK_OVERLAP:].strip()
                        buffer = (buffer + " " + sent).strip()

    if buffer:
        chunks.append(buffer)

    return [c for c in chunks if c.strip()]


def _embed(texts: list[str]) -> list[list[float]]:
    enc = _get_encoder()
    if enc is None:
        return []
    return [emb.tolist() for emb in enc.embed(texts)]


def embed_and_store(db: Session, company_id: int, content: str) -> int:
    """Embed content chunks and (re-)store them in document_chunks table.

    Returns the number of chunks stored, or 0 if fastembed is unavailable
    (in which case the system falls back to BM25 retrieval automatically).
    """
    enc = _get_encoder()
    if enc is None:
        log.warning("Skipping vector embed for company %d — fastembed unavailable", company_id)
        return 0

    chunks = _chunk_text(content)
    if not chunks:
        return 0

    embeddings = _embed(chunks)
    if not embeddings:
        return 0

    # Delete old chunks for this company
    db.execute(
        text("DELETE FROM document_chunks WHERE company_id = :cid"),
        {"cid": company_id},
    )

    # Bulk insert
    rows = [
        {
            "company_id": company_id,
            "chunk_index": i,
            "chunk_text": chunk,
            "embedding": str(emb),  # pgvector accepts '[0.1, 0.2, ...]' format
        }
        for i, (chunk, emb) in enumerate(zip(chunks, embeddings))
    ]

    db.execute(
        text(
            "INSERT INTO document_chunks (company_id, chunk_index, chunk_text, embedding) "
            "VALUES (:company_id, :chunk_index, :chunk_text, :embedding::vector)"
        ),
        rows,
    )
    db.commit()
    log.info("Stored %d vector chunks for company %d", len(rows), company_id)
    return len(rows)


def retrieve_context(db: Session, company_id: int, query: str, top_k: int = _TOP_K) -> str:
    """Return top-k most relevant chunks as a single string.

    Falls back to empty string if no chunks exist or fastembed is unavailable
    (caller should then use the BM25 fallback from rag/main.py).
    """
    enc = _get_encoder()
    if enc is None:
        return ""

    query_emb = _embed([query])
    if not query_emb:
        return ""

    emb_str = str(query_emb[0])

    try:
        result = db.execute(
            text(
                "SELECT chunk_text, 1 - (embedding <=> :emb::vector) AS similarity "
                "FROM document_chunks "
                "WHERE company_id = :cid "
                "ORDER BY embedding <=> :emb::vector "
                "LIMIT :k"
            ),
            {"emb": emb_str, "cid": company_id, "k": top_k},
        )
        rows = result.fetchall()
    except Exception as exc:
        log.warning("pgvector query failed for company %d: %s", company_id, exc)
        return ""

    if not rows:
        return ""

    return "\n\n---\n\n".join(row[0] for row in rows)


def delete_chunks(db: Session, company_id: int) -> None:
    """Delete all vector chunks for a company."""
    db.execute(
        text("DELETE FROM document_chunks WHERE company_id = :cid"),
        {"cid": company_id},
    )
    db.commit()
