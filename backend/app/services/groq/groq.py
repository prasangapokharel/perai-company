"""Groq client helpers."""

from __future__ import annotations

from collections.abc import Iterator
from os import getenv
from typing import Any

from groq import APIStatusError, AuthenticationError, Groq, PermissionDeniedError, RateLimitError

from app.core.config.config import GROQ_API_KEYS, GROQ_MODEL

DEFAULT_MODEL = GROQ_MODEL
DEFAULT_TEMPERATURE = float(getenv("GROQ_TEMPERATURE", "0.3"))
DEFAULT_MAX_COMPLETION_TOKENS = int(getenv("GROQ_MAX_COMPLETION_TOKENS", "1024"))


def get_groq_model_name() -> str:
    if not GROQ_MODEL:
        raise ValueError("GROQ_MODEL is not set")
    return GROQ_MODEL


def get_client(api_key: str | None = None) -> Groq:
    key = api_key or (GROQ_API_KEYS[0] if GROQ_API_KEYS else "")
    if not key:
        raise ValueError("GROQ_API_KEY is not set in backend .env")
    return Groq(api_key=key)


def _is_key_retryable(exc: Exception) -> bool:
    if isinstance(exc, (AuthenticationError, PermissionDeniedError, RateLimitError)):
        return True
    if isinstance(exc, APIStatusError):
        return exc.status_code in (401, 403, 429)
    return False


def _open_stream(
    client: Groq,
    messages: list[dict[str, str]],
    model: str,
    temperature: float,
    max_completion_tokens: int,
):
    return client.chat.completions.create(
        model=model,
        messages=messages,
        temperature=temperature,
        max_completion_tokens=max_completion_tokens,
        top_p=1,
        stop=None,
        stream=True,
    )


def stream_chat_completion(
    messages: list[dict[str, str]],
    model: str | None = DEFAULT_MODEL,
    temperature: float = DEFAULT_TEMPERATURE,
    max_completion_tokens: int = DEFAULT_MAX_COMPLETION_TOKENS,
) -> Iterator[Any]:
    if not model:
        raise ValueError("GROQ_MODEL is not set")
    if not GROQ_API_KEYS:
        raise ValueError("GROQ_API_KEY is not set in backend .env")

    def _iter() -> Iterator[Any]:
        last_error: Exception | None = None
        for index, api_key in enumerate(GROQ_API_KEYS):
            client = get_client(api_key)
            yielded = False
            try:
                stream = _open_stream(
                    client, messages, model, temperature, max_completion_tokens
                )
                for chunk in stream:
                    yielded = True
                    yield chunk
                return
            except Exception as exc:
                if not yielded and _is_key_retryable(exc) and index < len(GROQ_API_KEYS) - 1:
                    last_error = exc
                    continue
                raise
        if last_error:
            raise last_error

    return _iter()
