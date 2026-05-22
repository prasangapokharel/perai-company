"""Prompt engineering helpers for company chat."""

from __future__ import annotations

from pathlib import Path

from app.models.company import Company


_PROMPT_DIR = Path(__file__).resolve().parent


def _read(name: str) -> str:
    return (_PROMPT_DIR / name).read_text(encoding="utf-8").strip()


def _tone_rule() -> str:
    rules = {
        "professional": "Use formal, precise language. No slang.",
        "friendly": "Be warm, approachable, and conversational.",
        "technical": "Use technical terms freely. Assume expertise.",
        "simple": "Use plain language. Short sentences. No jargon.",
        "sales": "Be persuasive, benefit-focused, and enthusiastic.",
    }
    return rules["professional"]


def build_company_system_prompt(company: Company, knowledge: str | None = None) -> str:
    base = _read("SystemPrompt.md")
    tone = _read("ToneInstructions.md")

    knowledge_block = ""
    if knowledge:
        knowledge_block = "\n\n## Company Knowledge\n" + knowledge.strip()

    return (
        base.replace("{company_name}", company.company_name)
        .replace("{website}", company.website or "N/A")
        .replace("{category}", "company support assistant")
        .replace("{tone_rule}", _tone_rule())
        .replace("{length_rule}", "Keep responses short, clear, and useful by default.")
        .replace("{fallback_contact}", company.company_email)
        .replace("{knowledge_block}", knowledge_block)
    )
