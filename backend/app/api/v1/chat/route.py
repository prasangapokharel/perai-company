"""Chat endpoints."""

from __future__ import annotations

import json

from fastapi import APIRouter, Depends, HTTPException, Request, status
from fastapi.responses import StreamingResponse
from sqlalchemy.orm import Session

from app.api.v1.chat.service import get_company_prompt, get_company_prompt_with_settings
from app.api.v1.company.service import get_company_model_name
from app.core.database import getDb
from app.schemas.companySchema import ChatQuery, ChatResponse
from app.services.groq.groq import stream_chat_completion


router = APIRouter(prefix="/api/v1/company", tags=["chat"])


@router.post("/{company_id}/chat/stream")
async def stream_company_chat(company_id: int, request: Request, db: Session = Depends(getDb)):
    async def event_stream():
        try:
            body = await request.json()
            message = body.get("message", "")
            if not message:
                yield "data: {\"error\": \"message is required\"}\n\n"
                return

            _, system_prompt = get_company_prompt(db, company_id)
            messages = [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": message},
            ]

            stream = stream_chat_completion(messages)
            yield "data: {\"type\": \"start\"}\n\n"
            for chunk in stream:
                content = getattr(chunk.choices[0].delta, "content", None)
                if content:
                    payload = json.dumps({"type": "token", "content": content})
                    yield f"data: {payload}\n\n"

            yield "data: {\"type\": \"done\"}\n\n"
        except ValueError as err:
            yield "data: " + json.dumps({"error": str(err)}) + "\n\n"
        except Exception as err:
            yield "data: " + json.dumps({"error": "chat_failed", "detail": str(err)}) + "\n\n"

    return StreamingResponse(event_stream(), media_type="text/event-stream")


@router.get("/{company_id}/chat/ping")
def ping(company_id: int):
    return {"company_id": company_id, "status": "ok"}


@router.post("/{company_id}/chat/query", response_model=ChatResponse)
def query_company_chat(company_id: int, payload: ChatQuery, db: Session = Depends(getDb)):
    """Query company with finetune model and dynamic settings.
    
    Returns response from the company's AI model based on finetune data,
    tone, and language preferences.
    """
    try:
        # Get company and model name
        model_name = get_company_model_name(db, company_id)
        
        # Get system prompt with settings (tone, language, max_tokens)
        _, system_prompt, settings = get_company_prompt_with_settings(db, company_id)
        
        # Prepare messages
        messages = [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": payload.prompt},
        ]
        
        # Get response from Groq with max_tokens from settings
        stream = stream_chat_completion(messages, max_tokens=settings.max_tokens)
        response_text = ""
        for chunk in stream:
            content = getattr(chunk.choices[0].delta, "content", None)
            if content:
                response_text += content
        
        return ChatResponse(
            model_name=model_name,
            company_id=company_id,
            response=response_text,
        )
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(err)) from err
    except Exception as err:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Chat failed: {str(err)}"
        ) from err
