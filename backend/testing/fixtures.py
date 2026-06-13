"""Data factories for generating test payloads."""

import random
import string


def random_suffix(n: int = 6) -> str:
    return "".join(random.choices(string.ascii_lowercase + string.digits, k=n))


def company_payload(suffix: str | None = None) -> dict:
    s = suffix or random_suffix()
    return {
        "company_name": f"Test Company {s}",
        "company_email": f"test-{s}@perai.test",
        "password": "testpass1234",
        "website": f"https://test-{s}.perai.test",
    }


def ticket_payload(issue: str = "Widget not loading", category: str = "technical") -> dict:
    return {"issue": issue, "category": category}


def settings_payload(tone: str = "friendly", language: str = "english", max_tokens: int = 500) -> dict:
    return {"tone": tone, "language": language, "max_tokens": max_tokens}


def api_key_payload(name: str | None = None) -> dict:
    return {"name": name or f"key-{random_suffix()}"}


SAMPLE_KNOWLEDGE = """{"question":"What is Perai?","answer":"Perai helps companies deploy AI chat widgets that answer customer questions."}
{"title":"Pricing","content":"Starter: $29/month, 10,000 tokens/month. Business: $99/month, 100,000 tokens/month. Enterprise: contact sales."}
{"title":"Features","content":"Custom knowledge base upload, tone configuration, dashboard analytics, REST API with API key authentication."}
{"text":"Email support@perai.io for help."}
"""
