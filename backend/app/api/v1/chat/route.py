"""Chat endpoints.

- POST /{company_id}/chat/stream   — SSE streaming (requires X-API-Key or JWT)
- POST /{company_id}/chat/query    — Non-streaming + usage tracking
- POST /{company_id}/prompt/preview — Preview system prompt (admin)
- GET  /{company_id}/chat/ping     — Health check (public)
"""

from __future__ import annotations

import json

from fastapi import APIRouter, Depends, HTTPException, Request, status
from fastapi.responses import StreamingResponse
from pydantic import BaseModel
from sqlalchemy.orm import Session

from app.api.v1.balance.service import (
    InsufficientBalanceError,
    release_reserve,
    reserve_balance,
)
from app.api.v1.chat.service import (
    complete_chat_usage,
    estimate_max_chat_cost,
    estimateTokens,
    get_company_prompt_with_settings,
    run_chat_query,
    resolve_session_id,
)
from app.api.v1.company.service import get_company_model_name
from app.core.database import get_db
from app.core.prompt.template_generator import prompt_template_generator
from app.core.security import require_company
from app.schemas.companySchema import ChatQuery, ChatResponse
from app.services.groq.groq import stream_chat_completion

router = APIRouter(prefix="/api/v1/company", tags=["chat"])


class PromptPreviewRequest(BaseModel):
    tone: str
    language: str
    max_tokens: int
    company_name: str
    category: str
    website: str


class PromptPreviewResponse(BaseModel):
    prompt: str
    metadata: dict


@router.post("/{company_id}/chat/stream")
async def stream_company_chat(
    company_id: int,
    request: Request,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    async def event_stream():
        reserved = None
        try:
            body = await request.json()
            message = body.get("message", "")
            session_id = body.get("session_id")
            if not message:
                yield 'data: {"error": "message is required"}\n\n'
                return

            _, system_prompt, settings = get_company_prompt_with_settings(db, company_id, message)
            max_cost = estimate_max_chat_cost(system_prompt, message, settings.max_tokens)
            reserved = reserve_balance(db, company_id, max_cost)

            messages = [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": message},
            ]

            stream = stream_chat_completion(messages, max_completion_tokens=settings.max_tokens)
            yield 'data: {"type": "start"}\n\n'
            response_text = ""
            usage = None
            for chunk in stream:
                usage = getattr(chunk, "usage", usage)
                content = getattr(chunk.choices[0].delta, "content", None)
                if content:
                    response_text += content
                    yield f"data: {json.dumps({'type': 'token', 'content': content})}\n\n"

            prompt_tokens = int(
                getattr(usage, "prompt_tokens", None) or estimateTokens(system_prompt + message)
            )
            completion_tokens = int(
                getattr(usage, "completion_tokens", None) or estimateTokens(response_text)
            )
            model_name = get_company_model_name(db, company_id)
            resolved_session = resolve_session_id(session_id)
            msg, remaining = complete_chat_usage(
                db,
                company_id,
                resolved_session,
                message,
                response_text,
                model_name,
                prompt_tokens,
                completion_tokens,
                reserved,
            )
            yield f"data: {json.dumps({'type': 'done', 'balance_remaining': str(remaining), 'session_id': resolved_session, 'message_id': msg.id, 'token_consume': msg.token_consume, 'model_name': model_name})}\n\n"
        except InsufficientBalanceError as err:
            yield f"data: {json.dumps({'error': 'insufficient_balance', 'detail': str(err)})}\n\n"
        except ValueError as err:
            if reserved:
                release_reserve(db, company_id, reserved)
            yield f"data: {json.dumps({'error': str(err)})}\n\n"
        except Exception as err:
            if reserved:
                release_reserve(db, company_id, reserved)
            yield f"data: {json.dumps({'error': 'chat_failed', 'detail': str(err)})}\n\n"

    return StreamingResponse(event_stream(), media_type="text/event-stream")


@router.get("/{company_id}/chat/ping")
def ping(company_id: int):
    return {"company_id": company_id, "status": "ok"}


@router.post("/{company_id}/prompt/preview", response_model=PromptPreviewResponse)
def preview_prompt(
    company_id: int,
    payload: PromptPreviewRequest,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        preview_data = prompt_template_generator.preview_prompt(
            company_name=payload.company_name,
            category=payload.category,
            website=payload.website,
            tone=payload.tone,
            language=payload.language,
            max_tokens=payload.max_tokens,
            knowledge_block="",
            fallback_contact="Contact support",
        )
        return PromptPreviewResponse(prompt=preview_data["prompt"], metadata=preview_data["metadata"])
    except Exception as err:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to generate preview: {err}",
        ) from err


@router.post("/{company_id}/chat/query", response_model=ChatResponse)
def query_company_chat(
    company_id: int,
    payload: ChatQuery,
    request: Request,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        client_ip = request.headers.get("X-Forwarded-For", request.client.host if request.client else None)
        if client_ip and "," in client_ip:
            client_ip = client_ip.split(",", 1)[0].strip()

        model_name, response_text, remaining, session_id, message_id, token_consume = run_chat_query(
            db,
            company_id,
            payload.prompt,
            session_id=payload.session_id,
            client_ip=client_ip,
        )
        return ChatResponse(
            model_name=model_name,
            company_id=company_id,
            response=response_text,
            balance_remaining=remaining,
            session_id=session_id,
            message_id=message_id,
            token_consume=token_consume,
        )
    except InsufficientBalanceError as err:
        raise HTTPException(
            status_code=status.HTTP_402_PAYMENT_REQUIRED,
            detail=str(err),
        ) from err
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err
    except Exception as err:
        detail = str(err)
        if "invalid_api_key" in detail.lower() or "invalid api key" in detail.lower():
            detail = "All Groq API keys failed. Update GROQ_API_KEY (and fallbacks) in backend/.env and restart."
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail=f"Chat failed: {detail}",
        ) from err
