from __future__ import annotations

from pathlib import Path

from app.models.company import Company


_PROMPT_DIR = Path(__file__).resolve().parent


def _read(name: str) -> str:
    return (_PROMPT_DIR / name).read_text(encoding="utf-8").strip()


_TONE_RULES: dict[str, str] = {
    "formal": "Use formal, structured language. Avoid contractions and casual expressions.",
    "professional": "Use formal, precise language. No slang.",
    "friendly": "Be warm, approachable, and conversational.",
    "casual": "Use everyday, relaxed language. Contractions and informal phrasing are fine.",
    "technical": "Use technical terms freely. Assume domain expertise.",
}
_DEFAULT_TONE = "professional"


def _tone_rule(tone: str | None = None) -> str:
    return _TONE_RULES.get((tone or _DEFAULT_TONE).lower(), _TONE_RULES[_DEFAULT_TONE])


def build_company_system_prompt(
    company: Company,
    knowledge: str | None = None,
    tone: str | None = None,
) -> str:
    base = _read("SystemPrompt.md")

    knowledge_block = ""
    if knowledge:
        knowledge_block = "\n\n## Company Knowledge\n" + knowledge.strip()

    return (
        base.replace("{company_name}", company.company_name)
        .replace("{website}", company.website or "N/A")
        .replace("{category}", "company support assistant")
        .replace("{tone_rule}", _tone_rule(tone))
        .replace("{length_rule}", "Keep responses short, clear, and useful by default.")
        .replace("{fallback_contact}", company.company_email)
        .replace("{knowledge_block}", knowledge_block)
    )
