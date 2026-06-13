"""Unified password hashing — single format, constant-time verification.

Format: "{salt_hex}${digest_hex}"
  - salt  = 16 random bytes (token_bytes(16))
  - digest = PBKDF2-HMAC-SHA256, 200 000 iterations

Legacy format (used by older auth/service.py records):
  - raw bytes: 32-byte salt || 32-byte digest, hex-encoded (no '$' separator, 128 chars)
  - 100 000 iterations

verify_password() handles both formats transparently so existing accounts
continue to work. All new hashes are written in the new format.
"""

from __future__ import annotations

import hashlib
import hmac
from secrets import token_bytes


_ITERATIONS = 200_000
_LEGACY_ITERATIONS = 100_000


def hash_password(password: str) -> str:
    """Hash a password in the canonical new format."""
    salt = token_bytes(16)
    digest = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, _ITERATIONS)
    return f"{salt.hex()}${digest.hex()}"


def verify_password(password: str, stored: str) -> bool:
    """Verify a password against a stored hash (new or legacy format)."""
    try:
        if "$" in stored:
            return _verify_new(password, stored)
        return _verify_legacy(password, stored)
    except Exception:
        return False


def _verify_new(password: str, stored: str) -> bool:
    salt_hex, digest_hex = stored.split("$", 1)
    salt = bytes.fromhex(salt_hex)
    candidate = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, _ITERATIONS)
    return hmac.compare_digest(candidate.hex(), digest_hex)


def _verify_legacy(password: str, stored: str) -> bool:
    """Handle old format: (salt + hash).hex() with 100k iterations."""
    stored_bytes = bytes.fromhex(stored)
    salt = stored_bytes[:32]
    expected = stored_bytes[32:]
    candidate = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, _LEGACY_ITERATIONS)
    return hmac.compare_digest(candidate, expected)
