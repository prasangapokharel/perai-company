"""RAG retrieval and training-file storage helpers for finetune."""

from __future__ import annotations

import math
import re
import unicodedata
from hashlib import sha1
from pathlib import Path
from typing import Any

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

# Root directory for company-level RAG files.
_RAG_ROOT = Path(__file__).parent
_RAG_COMPANIES_ROOT = _RAG_ROOT / "companies"

# Legacy root kept for backward compatibility with previously written files.
_RAG_COMPANIES_ROOT_LEGACY = _RAG_ROOT / "rag" / "companies"

_TRAINING_FILENAME = "herethefile.md"
_TRAINING_FILENAME_NEW = "company.md"

# Chunk settings
_CHUNK_SIZE = 400  # characters per chunk
_CHUNK_OVERLAP = 80  # overlap between consecutive chunks
_TOP_K = 6  # maximum chunks to return
_MIN_SCORE = 0.05  # discard chunks below this BM25-like score
_TREE_MIN_SCORE = 0.08
_MAX_TREE_NODES = 1200
_MAX_NODE_TEXT = 1600
_MIN_MATCHED_NODES = 1


_company_tree_cache: dict[int, tuple[int, list[dict[str, Any]], str]] = {}


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------


def _safe_name(raw: str) -> str:
    """Normalise a user/company name into a safe directory segment."""
    # Unicode → ASCII decomposition
    name = unicodedata.normalize("NFKD", raw or "unknown")
    name = name.encode("ascii", "ignore").decode("ascii")
    # Replace anything not alphanumeric / dash / underscore / dot with _
    name = re.sub(r"[^\w.\-]", "_", name).strip("_")
    return name or "unknown"


def _get_rag_path(user_full_name: str, company_name: str) -> Path:
    """Return the Path to herethefile.md for a specific user+company pair."""
    return _RAG_ROOT / _safe_name(user_full_name) / _safe_name(company_name) / _TRAINING_FILENAME


# ---------------------------------------------------------------------------
# Public API — file management
# ---------------------------------------------------------------------------


def save_training_file(
    user_full_name: str,
    company_name: str,
    content: str,
) -> Path:
    """
    Persist training content to disk.

    Parameters
    ----------
    user_full_name : str
        e.g. ``"John Doe"`` — becomes the first directory level.
    company_name : str
        e.g. ``"Acme Corp"`` — becomes the second directory level.
    content : str
        Raw text (CSV / markdown / plain text) uploaded by the user.

    Returns
    -------
    Path
        Absolute path where the file was written.
    """
    path = _get_rag_path(user_full_name, company_name)
    path.parent.mkdir(parents=True, exist_ok=True)

    # If a file already exists, append the new content separated by a
    # horizontal rule so previous training data is preserved.
    if path.exists():
        existing = path.read_text(encoding="utf-8")
        content = existing.rstrip() + "\n\n---\n\n" + content.strip()

    path.write_text(content, encoding="utf-8")
    return path


def overwrite_training_file(
    user_full_name: str,
    company_name: str,
    content: str,
) -> Path:
    """Like save_training_file but fully replaces the file (used by PUT)."""
    path = _get_rag_path(user_full_name, company_name)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content.strip(), encoding="utf-8")
    return path


def load_training_file(user_full_name: str, company_name: str) -> str | None:
    """Return the full text of the training file, or None if it doesn't exist."""
    path = _get_rag_path(user_full_name, company_name)
    if not path.exists():
        return None
    return path.read_text(encoding="utf-8")


def delete_training_file(user_full_name: str, company_name: str) -> bool:
    """Delete the training file. Returns True if it existed."""
    path = _get_rag_path(user_full_name, company_name)
    if path.exists():
        path.unlink()
        # Remove empty parent directories
        try:
            path.parent.rmdir()
            path.parent.parent.rmdir()
        except OSError:
            pass
        return True
    return False


def get_rag_path(user_full_name: str, company_name: str) -> Path:
    """Public alias for _get_rag_path — returns the Path to herethefile.md."""
    return _get_rag_path(user_full_name, company_name)


def get_rag_path_str(user_full_name: str, company_name: str) -> str:
    """Return the absolute path string for storage in the DB training_file column."""
    return str(_get_rag_path(user_full_name, company_name))


def get_company_rag_path(company_id: int) -> Path:
    """Return the markdown path for a company-id based RAG file."""
    return get_training_file_path_for_company(company_id)


def get_company_rag_path_str(company_id: int) -> str:
    """Return the absolute path string for DB storage."""
    return str(get_company_rag_path(company_id).resolve())


# ---------------------------------------------------------------------------
# NEW: Company ID-based RAG functions (stable, no name collisions)
# ---------------------------------------------------------------------------


def _get_rag_path_by_company_id(company_id: int) -> Path:
    """Return path for company-id based RAG file (new structure)"""
    return _RAG_COMPANIES_ROOT / str(company_id) / _TRAINING_FILENAME_NEW


def _get_legacy_rag_path_by_company_id(company_id: int) -> Path:
    """Return path for legacy company-id based RAG file."""
    return _RAG_COMPANIES_ROOT_LEGACY / str(company_id) / _TRAINING_FILENAME_NEW


def get_training_file_path_for_company(company_id: int) -> Path:
    """Return the active training file path for a company.

    Preference order:
    1) New path if it exists
    2) Legacy path if it exists
    3) New path (for future writes)
    """
    current_path = _get_rag_path_by_company_id(company_id)
    legacy_path = _get_legacy_rag_path_by_company_id(company_id)

    if current_path.exists():
        return current_path
    if legacy_path.exists():
        return legacy_path
    return current_path


def save_training_file_for_company(company_id: int, content: str) -> Path:
    """
    Save training file by company ID (new structure).
    Overwrites the file so one company maps to one markdown source.
    """
    path = get_training_file_path_for_company(company_id)
    path.parent.mkdir(parents=True, exist_ok=True)

    path.write_text(content.strip(), encoding="utf-8")
    return path


def load_training_file_for_company(company_id: int) -> str | None:
    """Load training file content by company ID"""
    path = get_training_file_path_for_company(company_id)
    if not path.exists():
        return None
    return path.read_text(encoding="utf-8")


def _retrieve_from_text(
    full_text: str,
    query: str,
    top_k: int = _TOP_K,
    fallback_chars: int = 3000,
) -> tuple[str, bool]:
    """Retrieve the most relevant chunks from a full text blob."""
    if not full_text:
        return ("", False)

    tree_context, used_tree = _retrieve_from_tree_nodes(
        full_text=full_text,
        query=query,
        top_k=top_k,
        fallback_chars=fallback_chars,
    )
    if tree_context:
        return (tree_context, used_tree)

    chunks = _chunk_text(full_text)
    if not chunks:
        return (full_text[:fallback_chars], False)

    query_tokens = _tokenize(query)
    if not query_tokens:
        selected = chunks[:top_k]
        return ("\n\n---\n\n".join(selected), True)

    avg_dl = sum(len(_tokenize(c)) for c in chunks) / len(chunks)
    corpus_size = len(chunks)

    scored: list[tuple[float, str]] = [
        (_bm25_score(query_tokens, c, corpus_size, avg_dl), c) for c in chunks
    ]
    scored.sort(key=lambda x: x[0], reverse=True)

    selected = [c for score, c in scored if score >= _MIN_SCORE][:top_k]

    if not selected:
        selected = [scored[0][1]] if scored else chunks[:1]

    return ("\n\n---\n\n".join(selected), True)


def _first_nonempty_line(text: str) -> str:
    for line in text.splitlines():
        line = line.strip()
        if line:
            return line
    return ""


def _split_tree_records(full_text: str) -> list[str]:
    text = (full_text or "").strip()
    if not text:
        return []

    if "\n\n---\n\n" in text:
        records = [r.strip() for r in text.split("\n\n---\n\n") if r.strip()]
        if records:
            return records

    records = [r.strip() for r in re.split(r"\n{2,}", text) if r.strip()]
    return records


def _extract_key_value_pairs(record: str, max_pairs: int = 40) -> list[str]:
    pairs: list[str] = []
    for line in record.splitlines():
        raw = line.strip()
        if not raw or ":" not in raw:
            continue
        key, value = raw.split(":", 1)
        key = key.strip().lower()
        value = value.strip()
        if not key or not value:
            continue
        if len(value) > 180:
            value = value[:180].rstrip()
        pairs.append(f"{key}: {value}")
        if len(pairs) >= max_pairs:
            break
    return pairs


def _build_tree_nodes(full_text: str) -> list[dict[str, Any]]:
    records = _split_tree_records(full_text)
    if not records:
        return []

    nodes: list[dict[str, Any]] = [
        {
            "id": "root",
            "parent_id": None,
            "kind": "root",
            "title": "Knowledge Root",
            "content": "",
            "keywords": [],
            "record_index": -1,
            "weight": 0.0,
        }
    ]

    for idx, record in enumerate(records):
        if idx >= _MAX_TREE_NODES:
            break

        lines = [ln.strip() for ln in record.splitlines() if ln.strip()]
        title = _first_nonempty_line(record) or f"Record {idx + 1}"
        if len(title) > 140:
            title = title[:140].rstrip()

        record_text = record[:_MAX_NODE_TEXT].strip()
        key_values = _extract_key_value_pairs(record)
        keywords = _tokenize(" ".join([title, *key_values]))[:80]
        record_id = f"record:{idx}"
        nodes.append(
            {
                "id": record_id,
                "parent_id": "root",
                "kind": "record",
                "title": title,
                "content": record_text,
                "keywords": keywords,
                "record_index": idx,
                "weight": 1.0,
            }
        )

        child_count = 0
        for line in lines:
            if ":" not in line:
                continue
            key, value = line.split(":", 1)
            key = key.strip().lower()
            value = value.strip()
            if not key or not value:
                continue
            if len(value) > 220:
                value = value[:220].rstrip()
            child_count += 1
            if child_count > 8:
                break
            child_id = f"record:{idx}:field:{child_count}"
            nodes.append(
                {
                    "id": child_id,
                    "parent_id": record_id,
                    "kind": "field",
                    "title": key,
                    "content": value,
                    "keywords": _tokenize(f"{key} {value}")[:40],
                    "record_index": idx,
                    "weight": 1.2 if key in {"q", "question", "prompt", "input"} else 1.0,
                }
            )

    return nodes


def _query_features(query: str) -> tuple[list[str], set[str], list[str]]:
    text = (query or "").strip().lower()
    tokens = _tokenize(text)
    token_set = set(tokens)
    bigrams: list[str] = []
    if len(tokens) > 1:
        for i in range(len(tokens) - 1):
            bigrams.append(f"{tokens[i]} {tokens[i + 1]}")
    return tokens, token_set, bigrams


def _score_tree_node(
    node: dict[str, Any],
    query_text: str,
    query_tokens: list[str],
    query_token_set: set[str],
    query_bigrams: list[str],
) -> float:
    if not query_tokens:
        return 0.0

    title = str(node.get("title", "")).lower()
    content = str(node.get("content", "")).lower()
    keywords = " ".join(str(k) for k in node.get("keywords", [])).lower()
    query_vector = _build_vector(query_tokens)
    node_vector = _build_vector(_tokenize(f"{title} {content} {keywords}"))

    title_tokens = set(_tokenize(title))
    content_tokens = set(_tokenize(content))
    keyword_tokens = set(_tokenize(keywords))

    title_overlap = len(query_token_set & title_tokens)
    keyword_overlap = len(query_token_set & keyword_tokens)
    content_overlap = len(query_token_set & content_tokens)

    overlap_score = (
        (1.8 * title_overlap) + (1.3 * keyword_overlap) + (1.0 * content_overlap)
    ) / max(len(query_token_set), 1)

    phrase_bonus = 0.0
    if query_text and len(query_text) > 3:
        if query_text in content:
            phrase_bonus += 0.25
        elif query_text in title:
            phrase_bonus += 0.3

    bigram_bonus = 0.0
    if query_bigrams:
        hit = sum(1 for bg in query_bigrams if bg in content or bg in title)
        bigram_bonus = min(hit / max(len(query_bigrams), 1), 1.0) * 0.2

    exact_numeric_bonus = 0.0
    numeric_like = [t for t in query_tokens if any(ch.isdigit() for ch in t)]
    if numeric_like:
        matches = sum(1 for t in numeric_like if t in content or t in title)
        exact_numeric_bonus = min(matches * 0.08, 0.24)

    vector_bonus = _cosine_similarity(query_vector, node_vector)

    node_weight = float(node.get("weight", 1.0) or 1.0)
    length_penalty = 1.0 / (1.0 + math.log(max(len(content_tokens), 1) + 1, 10))

    score = (
        overlap_score + phrase_bonus + bigram_bonus + exact_numeric_bonus + vector_bonus
    ) * node_weight
    score = score + (0.08 * length_penalty)
    return max(score, 0.0)


def _expand_tree_hits(
    ranked_nodes: list[dict[str, Any]],
    nodes_by_id: dict[str, dict[str, Any]],
    by_record: dict[int, list[dict[str, Any]]],
    max_nodes: int,
) -> list[dict[str, Any]]:
    selected: list[dict[str, Any]] = []
    seen: set[str] = set()

    def _add(node_id: str, score: float) -> None:
        if node_id in seen:
            return
        node = nodes_by_id.get(node_id)
        if not node:
            return
        selected.append({**node, "score": round(score, 4)})
        seen.add(node_id)

    for item in ranked_nodes:
        if len(selected) >= max_nodes:
            break
        node_id = str(item.get("id", ""))
        base_score = float(item.get("score", 0.0) or 0.0)
        _add(node_id, base_score)

        parent_id = item.get("parent_id")
        if parent_id and parent_id != "root":
            _add(str(parent_id), max(base_score - 0.08, 0.0))

        record_index = int(item.get("record_index", -1) or -1)
        if record_index >= 0:
            for near in (record_index - 1, record_index + 1):
                if near < 0:
                    continue
                near_nodes = by_record.get(near, [])
                for near_node in near_nodes[:1]:
                    _add(str(near_node.get("id", "")), max(base_score - 0.12, 0.0))

            same_record = by_record.get(record_index, [])
            for sibling in same_record:
                if sibling.get("parent_id") != node_id:
                    continue
                _add(str(sibling.get("id", "")), max(base_score - 0.06, 0.0))
                break

        if len(selected) >= max_nodes:
            break

    selected.sort(key=lambda n: float(n.get("score", 0.0)), reverse=True)
    return selected[:max_nodes]


def _format_tree_context(nodes: list[dict[str, Any]], fallback_chars: int) -> str:
    if not nodes:
        return ""
    blocks: list[str] = []
    total = 0
    for node in nodes:
        title = str(node.get("title", "Node"))
        content = " ".join(str(node.get("content", "")).split())
        if len(content) > 420:
            content = content[:420].rstrip()
        line = f"[{title}] (score: {node.get('score', 0.0)})\n{content}"
        if total + len(line) > fallback_chars:
            break
        blocks.append(line)
        total += len(line)
    return "\n\n---\n\n".join(blocks).strip()


def _retrieve_from_tree_nodes(
    full_text: str,
    query: str,
    top_k: int = _TOP_K,
    fallback_chars: int = 3000,
    nodes: list[dict[str, Any]] | None = None,
) -> tuple[str, bool]:
    nodes = nodes if nodes is not None else _build_tree_nodes(full_text)
    if not nodes:
        return ("", False)

    query_text = (query or "").strip().lower()
    query_tokens, query_token_set, query_bigrams = _query_features(query_text)
    if not query_tokens:
        default_nodes = [n for n in nodes if n.get("kind") == "record"][: max(top_k, 1)]
        return (_format_tree_context(default_nodes, fallback_chars), True)

    ranked: list[dict[str, Any]] = []
    nodes_by_id = {str(n.get("id", "")): n for n in nodes}
    by_record: dict[int, list[dict[str, Any]]] = {}
    for node in nodes:
        ri = int(node.get("record_index", -1) or -1)
        if ri >= 0:
            by_record.setdefault(ri, []).append(node)

        if node.get("kind") == "root":
            continue
        score = _score_tree_node(
            node=node,
            query_text=query_text,
            query_tokens=query_tokens,
            query_token_set=query_token_set,
            query_bigrams=query_bigrams,
        )
        if score < _TREE_MIN_SCORE:
            continue
        ranked.append({**node, "score": score})

    ranked.sort(key=lambda n: float(n.get("score", 0.0)), reverse=True)
    if not ranked:
        default_nodes = [n for n in nodes if n.get("kind") == "record"][: max(top_k, 1)]
        return (_format_tree_context(default_nodes, fallback_chars), True)

    expanded = _expand_tree_hits(
        ranked_nodes=ranked[: max(top_k * 2, 4)],
        nodes_by_id=nodes_by_id,
        by_record=by_record,
        max_nodes=max(top_k + 1, _MIN_MATCHED_NODES),
    )

    return (_format_tree_context(expanded, fallback_chars), True)


def _load_or_build_company_tree(company_id: int, full_text: str) -> list[dict[str, Any]]:
    path = get_training_file_path_for_company(company_id)
    stat_mtime_ns = path.stat().st_mtime_ns if path.exists() else 0
    cached = _company_tree_cache.get(company_id)
    content_hash = sha1(full_text.encode("utf-8")).hexdigest()
    if cached:
        cached_mtime, cached_nodes, cached_hash = cached
        if cached_mtime == stat_mtime_ns and cached_hash == content_hash:
            return cached_nodes

    nodes = _build_tree_nodes(full_text)
    _company_tree_cache[company_id] = (stat_mtime_ns, nodes, content_hash)
    return nodes


def delete_training_file_for_company(company_id: int) -> bool:
    """Delete training file for company ID, return True if deleted"""
    deleted = False
    for path in (
        _get_rag_path_by_company_id(company_id),
        _get_legacy_rag_path_by_company_id(company_id),
    ):
        if not path.exists():
            continue
        path.unlink()
        deleted = True
        try:
            path.parent.rmdir()
        except OSError:
            pass
    return deleted


def retrieve_context_for_company(
    company_id: int,
    query: str,
    top_k: int = _TOP_K,
    fallback_chars: int = 3000,
) -> tuple[str, bool]:
    """Retrieve ranked RAG context for a company ID."""
    full_text = load_training_file_for_company(company_id)
    if not full_text:
        return ("", False)

    tree_nodes = _load_or_build_company_tree(company_id, full_text)
    if tree_nodes:
        context, used = _retrieve_from_tree_nodes(
            full_text=full_text,
            query=query,
            top_k=top_k,
            fallback_chars=fallback_chars,
            nodes=tree_nodes,
        )
        if context:
            return (context, used)

    return ("", False)


# ---------------------------------------------------------------------------
# Chunking
# ---------------------------------------------------------------------------


def _chunk_text(text: str, size: int = _CHUNK_SIZE, overlap: int = _CHUNK_OVERLAP) -> list[str]:
    """
    Split *text* into overlapping character-level chunks.
    Tries to break at paragraph / sentence boundaries when possible.
    """
    text = text.strip()
    if not text:
        return []

    # Split into paragraphs first to respect natural boundaries
    paragraphs: list[str] = re.split(r"\n{2,}", text)
    chunks: list[str] = []
    buffer = ""

    for para in paragraphs:
        para = para.strip()
        if not para:
            continue

        if len(buffer) + len(para) + 1 <= size:
            buffer = (buffer + "\n\n" + para).strip()
        else:
            # Flush existing buffer
            if buffer:
                chunks.append(buffer)
                # Keep overlap from tail of current buffer
                buffer = buffer[-overlap:].strip()
            # Para itself may be larger than chunk_size — split by sentences
            if len(para) <= size:
                buffer = (buffer + "\n\n" + para).strip()
            else:
                sentences = re.split(r"(?<=[.!?])\s+", para)
                for sent in sentences:
                    if len(buffer) + len(sent) + 1 <= size:
                        buffer = (buffer + " " + sent).strip()
                    else:
                        if buffer:
                            chunks.append(buffer)
                            buffer = buffer[-overlap:].strip()
                        buffer = (buffer + " " + sent).strip()

    if buffer:
        chunks.append(buffer)

    return [c for c in chunks if c.strip()]


# ---------------------------------------------------------------------------
# BM25-inspired scoring (no external deps)
# ---------------------------------------------------------------------------


def _tokenize(text: str) -> list[str]:
    return re.findall(r"[a-zA-Z0-9]+", text.lower())


def _build_vector(tokens: list[str]) -> dict[str, float]:
    vector: dict[str, float] = {}
    for token in tokens:
        vector[token] = vector.get(token, 0.0) + 1.0
    return vector


def _cosine_similarity(left: dict[str, float], right: dict[str, float]) -> float:
    if not left or not right:
        return 0.0
    dot = sum(weight * right.get(token, 0.0) for token, weight in left.items())
    if dot <= 0.0:
        return 0.0
    left_norm = math.sqrt(sum(weight * weight for weight in left.values()))
    right_norm = math.sqrt(sum(weight * weight for weight in right.values()))
    if left_norm == 0.0 or right_norm == 0.0:
        return 0.0
    return dot / (left_norm * right_norm)


def _bm25_score(query_tokens: list[str], chunk: str, corpus_size: int, avg_dl: float) -> float:
    """
    Simplified BM25 score for a single chunk.
    k1=1.5, b=0.75 are standard defaults.
    """
    k1 = 1.5
    b = 0.75
    chunk_tokens = _tokenize(chunk)
    dl = len(chunk_tokens)
    if dl == 0:
        return 0.0

    # Term frequency dict
    tf: dict = {}
    for t in chunk_tokens:
        tf[t] = tf.get(t, 0) + 1

    score = 0.0
    for qt in query_tokens:
        f = tf.get(qt, 0)
        if f == 0:
            continue
        # IDF approximation: assume rare tokens score higher
        idf = math.log((corpus_size - 1 + 0.5) / (1 + 0.5) + 1)
        numerator = f * (k1 + 1)
        denominator = f + k1 * (1 - b + b * (dl / max(avg_dl, 1)))
        score += idf * (numerator / denominator)

    return score


# ---------------------------------------------------------------------------
# Public API — retrieval
# ---------------------------------------------------------------------------


def retrieve_context(
    user_full_name: str,
    company_name: str,
    query: str,
    top_k: int = _TOP_K,
    fallback_chars: int = 3000,
) -> tuple[str, bool]:
    """
    Retrieve the most relevant chunks from the training file for *query*.

    Parameters
    ----------
    user_full_name, company_name : str
        Identify which training file to use.
    query : str
        The user's chat message.
    top_k : int
        Maximum number of chunks to include in the returned context.
    fallback_chars : int
        If no RAG file exists, fall back to the first N chars of
        ``db_training_data`` (handled by caller).

    Returns
    -------
    (context_text, used_rag)
        *context_text* — the retrieved/ranked context string.
        *used_rag*     — True if the RAG file was used, False if fallback.
    """
    full_text = load_training_file(user_full_name, company_name)
    if not full_text:
        return ("", False)
    return _retrieve_from_text(
        full_text=full_text,
        query=query,
        top_k=top_k,
        fallback_chars=fallback_chars,
    )
