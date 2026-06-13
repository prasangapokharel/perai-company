import json

MAX_JSONL_BYTES = 10 * 1024 * 1024
MAX_JSONL_LINES = 5000


def parse_jsonl_records(raw: str) -> list[dict]:
    if len(raw.encode("utf-8")) > MAX_JSONL_BYTES:
        raise ValueError("JSONL content exceeds 10MB limit")

    lines = [line.strip() for line in raw.splitlines() if line.strip()]
    if not lines:
        raise ValueError("JSONL file is empty")
    if len(lines) > MAX_JSONL_LINES:
        raise ValueError(f"JSONL exceeds {MAX_JSONL_LINES} lines")

    records: list[dict] = []
    for index, line in enumerate(lines, start=1):
        try:
            row = json.loads(line)
        except json.JSONDecodeError as exc:
            raise ValueError(f"Invalid JSON on line {index}") from exc
        if not isinstance(row, dict):
            raise ValueError(f"Line {index} must be a JSON object")
        records.append(row)
    return records


def record_to_block(row: dict, index: int) -> str:
    question = str(row.get("question") or row.get("q") or "").strip()
    answer = str(row.get("answer") or row.get("a") or "").strip()
    title = str(row.get("title") or row.get("topic") or "").strip()
    content = str(row.get("content") or row.get("body") or "").strip()
    text = str(row.get("text") or "").strip()

    if question and answer:
        return f"Question: {question}\nAnswer: {answer}"
    if title and content:
        return f"Title: {title}\nContent: {content}"
    if text:
        return f"Text: {text}"
    if question:
        return f"Question: {question}"
    if content:
        return f"Content: {content}"

    raise ValueError(
        f"Line {index} needs question+answer, title+content, or text"
    )


def jsonl_to_knowledge(raw: str) -> str:
    records = parse_jsonl_records(raw)
    blocks = [record_to_block(row, i) for i, row in enumerate(records, start=1)]
    return "\n\n---\n\n".join(blocks)


def normalize_jsonl_upload(raw: str) -> str:
    return jsonl_to_knowledge(raw)
