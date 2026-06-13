"""API key utilities for generation and hashing."""

import hashlib
import secrets
from datetime import datetime, timedelta, timezone


def _as_utc(dt: datetime) -> datetime:
    if dt.tzinfo is None:
        return dt.replace(tzinfo=timezone.utc)
    return dt.astimezone(timezone.utc)


def generate_api_key(prefix: str = "sk", length: int = 32) -> str:
    """Generate a secure random API key.

    Args:
        prefix: Key prefix (e.g., 'sk' for secret key)
        length: Length of random part

    Returns:
        Generated API key in format: {prefix}_{random}
    """
    random_part = secrets.token_urlsafe(length)
    return f"{prefix}_{random_part}"


def hash_api_key(api_key: str) -> str:
    """Hash an API key using SHA-256.

    Args:
        api_key: API key to hash

    Returns:
        Hashed API key (hex string)
    """
    return hashlib.sha256(api_key.encode()).hexdigest()


def get_key_preview(api_key: str, show_chars: int = 4) -> str:
    """Get preview of API key (first + last chars).

    Args:
        api_key: Full API key
        show_chars: Number of chars to show from start and end

    Returns:
        Preview string (e.g., "sk_4...Cw") - max 20 chars
    """
    if len(api_key) <= show_chars * 2 + 3:
        return api_key[:20]  # Truncate to 20 chars max

    start = api_key[:show_chars]
    end = api_key[-show_chars:]
    preview = f"{start}...{end}"

    # Ensure it fits in 20 chars
    if len(preview) > 20:
        return api_key[:20]

    return preview


def verify_api_key(api_key: str, key_hash: str) -> bool:
    """Verify an API key against its hash.

    Args:
        api_key: Plain API key
        key_hash: Hashed API key to verify against

    Returns:
        True if key matches, False otherwise
    """
    return hash_api_key(api_key) == key_hash


def is_api_key_expired(expiry_date: datetime | None) -> bool:
    """Check if API key has expired.

    Args:
        expiry_date: Expiry datetime or None (no expiry)

    Returns:
        True if expired, False otherwise
    """
    if expiry_date is None:
        return False
    return datetime.now(timezone.utc) > _as_utc(expiry_date)
