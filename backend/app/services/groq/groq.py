"""Groq client helpers."""

from __future__ import annotations

from os import getenv
from typing import Any, Iterable

from groq import Groq


DEFAULT_MODEL = getenv("GROQ_MODEL")
DEFAULT_TEMPERATURE = float(getenv("GROQ_TEMPERATURE", "0.3"))
DEFAULT_MAX_COMPLETION_TOKENS = int(getenv("GROQ_MAX_COMPLETION_TOKENS", "1024"))


def get_client() -> Groq:
    """Create a Groq client from environment configuration."""
    key = getenv("GROQ_API_KEY")
    if not key:
        raise ValueError("GROQ_API_KEY is not set")
    return Groq(api_key=key)


def stream_chat_completion(
    messages: list[dict[str, str]],
    model: str | None = DEFAULT_MODEL,
    temperature: float = DEFAULT_TEMPERATURE,
    max_completion_tokens: int = DEFAULT_MAX_COMPLETION_TOKENS,
) -> Iterable[Any]:
    """Return a streamed Groq chat completion iterator."""
    if not model:
        raise ValueError("GROQ_MODEL is not set")
    client = get_client()
    return client.chat.completions.create(
        model=model,
        messages=messages,
        temperature=temperature,
        max_completion_tokens=max_completion_tokens,
        top_p=1,
        stop=None,
        stream=True,
    )
