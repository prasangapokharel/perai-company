"""Company chat service."""

from decimal import Decimal

from sqlalchemy.orm import Session

from app.core.config.config import VECTOR_RAG_ENABLED
from app.core.finetune.prompts.builder import build_company_system_prompt
from app.core.finetune.rag.main import retrieve_context_for_company
from app.models.companyRequests import CompanyRequest
from app.models.company import Company
from app.models.companySettings import CompanySettings
from app.models.chatMessage import ChatMessage, generate_session_id
from app.core.prompt.generator import PromptGenerator
from app.api.v1.balance.service import (
    finalize_deduction,
    get_balance,
    InsufficientBalanceError,
    release_reserve,
    reserve_balance,
)
from app.api.v1.company.service import get_company_model_name
from app.api.v1.companySettings.service import CompanySettingsService
from app.services.groq.groq import stream_chat_completion
from app.utils.token_cost import calculate_usd_cost, estimate_max_usd_cost, estimate_tokens


def estimateTokens(text: str) -> int:
    return estimate_tokens(text)


def calculateBalanceDeducted(input_tokens: int, output_tokens: int) -> Decimal:
    return calculate_usd_cost(input_tokens, output_tokens)


def createCompanyRequest(
    db: Session,
    companyId: int,
    tokenConsume: int,
    balanceDeducted: Decimal,
    ip: str | None,
) -> CompanyRequest:
    record = CompanyRequest(
        company_id=companyId,
        token_consume=tokenConsume,
        balance_deducted=balanceDeducted,
        ip=ip,
    )
    db.add(record)
    db.commit()
    db.refresh(record)
    return record


def _get_rag_context(db: Session, company_id: int, query: str) -> str | None:
    if not (query or "").strip():
        return None

    if VECTOR_RAG_ENABLED:
        try:
            from app.core.vector.vector import retrieve_context as vector_retrieve

            ctx = vector_retrieve(db, company_id, query)
            if ctx:
                return ctx
        except Exception:
            pass

    try:
        ctx, used = retrieve_context_for_company(company_id, query)
        if ctx and used:
            return ctx
    except Exception:
        pass

    return None


def get_company_prompt(db: Session, company_id: int) -> tuple[Company, str]:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if not company:
        raise ValueError("Company not found")
    settings = CompanySettingsService.getOrCreateSettings(db, company_id)
    rag_text = _get_rag_context(db, company_id, "")
    return company, build_company_system_prompt(company, rag_text, tone=settings.tone)


def get_company_prompt_with_settings(
    db: Session, company_id: int, query: str = ""
) -> tuple[Company, str, CompanySettings]:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if not company:
        raise ValueError("Company not found")

    rag_text = _get_rag_context(db, company_id, query) or ""
    settings = CompanySettingsService.getOrCreateSettings(db, company_id)

    system_prompt = PromptGenerator.generateSystemPrompt(
        company_settings=settings,
        base_context=rag_text,
        company_name=company.company_name,
        website=company.website or "",
        category="company support assistant",
    )

    return company, system_prompt, settings


def estimate_max_chat_cost(system_prompt: str, user_prompt: str, max_tokens: int) -> Decimal:
    input_tokens = estimate_tokens(system_prompt + user_prompt)
    return estimate_max_usd_cost(input_tokens, max_tokens)


def resolve_session_id(session_id: str | None) -> str:
    return session_id or generate_session_id()


def _format_conversation(user_prompt: str, assistant_response: str) -> str:
    return f"User: {user_prompt}\n\nAssistant: {assistant_response}"


def complete_chat_usage(
    db: Session,
    company_id: int,
    session_id: str,
    user_prompt: str,
    response_text: str,
    model_name: str,
    prompt_tokens: int,
    completion_tokens: int,
    reserved: Decimal,
    client_ip: str | None = None,
) -> tuple[ChatMessage, Decimal]:
    total_tokens = prompt_tokens + completion_tokens
    actual_cost = calculate_usd_cost(prompt_tokens, completion_tokens)

    msg = ChatMessage(
        company_id=company_id,
        session_id=session_id,
        conversation=_format_conversation(user_prompt, response_text),
        model_name=model_name,
        token_consume=total_tokens,
        ip=client_ip,
    )
    db.add(msg)
    db.flush()

    finalize_deduction(
        db,
        company_id,
        reserved,
        actual_cost,
        session_id=session_id,
        chat_message_id=msg.id,
        token_consume=total_tokens,
        model_name=model_name,
    )
    createCompanyRequest(db, company_id, total_tokens, actual_cost, client_ip)
    remaining = get_balance(db, company_id)
    db.refresh(msg)
    return msg, remaining


def run_chat_query(
    db: Session,
    company_id: int,
    prompt: str,
    session_id: str | None = None,
    client_ip: str | None = None,
) -> tuple[str, str, Decimal, str, int, int]:
    session_id = resolve_session_id(session_id)
    model_name = get_company_model_name(db, company_id)
    _, system_prompt, settings = get_company_prompt_with_settings(db, company_id, prompt)

    max_cost = estimate_max_chat_cost(system_prompt, prompt, settings.max_tokens)
    reserved = Decimal("0")

    try:
        reserved = reserve_balance(db, company_id, max_cost)
    except InsufficientBalanceError:
        raise

    messages = [
        {"role": "system", "content": system_prompt},
        {"role": "user", "content": prompt},
    ]

    try:
        stream = stream_chat_completion(messages, max_completion_tokens=settings.max_tokens)
        response_text = ""
        usage = None
        for chunk in stream:
            usage = getattr(chunk, "usage", usage)
            content = getattr(chunk.choices[0].delta, "content", None)
            if content:
                response_text += content

        prompt_tokens = int(
            getattr(usage, "prompt_tokens", None) or estimate_tokens(system_prompt + prompt)
        )
        completion_tokens = int(
            getattr(usage, "completion_tokens", None) or estimate_tokens(response_text)
        )
        total_tokens = prompt_tokens + completion_tokens

        msg, remaining = complete_chat_usage(
            db,
            company_id,
            session_id,
            prompt,
            response_text,
            model_name,
            prompt_tokens,
            completion_tokens,
            reserved,
            client_ip,
        )
        return model_name, response_text, remaining, session_id, msg.id, total_tokens
    except Exception:
        if reserved > 0:
            release_reserve(db, company_id, reserved)
        raise
