"""Chat message history API — protected by company ownership."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.core.database import get_db
from app.core.security import require_company, require_api_key_company
from app.models.chatMessage import ChatMessage, generate_session_id
from app.schemas.chatSchema import (
    ChatMessageCreate,
    ChatMessageRead,
    ChatMessageUpdate,
    ChatSessionPage,
)
from app.api.v1.chatMessages.service import delete_session_messages, list_sessions

router = APIRouter(prefix="/api/v1/company", tags=["chat-messages"])


@router.post(
    "/{company_id}/messages",
    response_model=ChatMessageRead,
    status_code=status.HTTP_201_CREATED,
)
def create_message(
    company_id: int,
    payload: ChatMessageCreate,
    _: int = Depends(require_api_key_company),
    db: Session = Depends(get_db),
):
    """Store a chat message/conversation for a session."""
    session_id = payload.session_id or generate_session_id()
    msg = ChatMessage(
        company_id=company_id,
        session_id=session_id,
        conversation=payload.conversation,
        review=payload.review,
    )
    db.add(msg)
    db.commit()
    db.refresh(msg)
    return ChatMessageRead.model_validate(msg)


@router.get("/{company_id}/sessions", response_model=ChatSessionPage)
def list_company_sessions(
    company_id: int,
    page: int = 1,
    page_size: int = 20,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    items, total, page, page_size, total_pages = list_sessions(
        db, company_id, page, page_size
    )
    return ChatSessionPage(
        items=[ChatMessageRead.model_validate(item) for item in items],
        total=total,
        page=page,
        page_size=page_size,
        total_pages=total_pages,
    )


@router.delete("/{company_id}/sessions/{session_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_company_session(
    company_id: int,
    session_id: str,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    deleted = delete_session_messages(db, company_id, session_id)
    if deleted == 0:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Session not found.")


@router.get("/{company_id}/messages", response_model=list[ChatMessageRead])
def list_messages(
    company_id: int,
    session_id: str | None = None,
    limit: int = 50,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    """List chat messages for a company (admin view)."""
    q = db.query(ChatMessage).filter(ChatMessage.company_id == company_id)
    if session_id:
        q = q.filter(ChatMessage.session_id == session_id)
    msgs = q.order_by(ChatMessage.created_at.desc()).limit(min(limit, 200)).all()
    return [ChatMessageRead.model_validate(m) for m in msgs]


@router.get("/{company_id}/messages/{message_id}", response_model=ChatMessageRead)
def get_message(
    company_id: int,
    message_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    msg = (
        db.query(ChatMessage)
        .filter(ChatMessage.id == message_id, ChatMessage.company_id == company_id)
        .first()
    )
    if not msg:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Message not found.")
    return ChatMessageRead.model_validate(msg)


@router.patch("/{company_id}/messages/{message_id}", response_model=ChatMessageRead)
def update_message_review(
    company_id: int,
    message_id: int,
    payload: ChatMessageUpdate,
    _: int = Depends(require_api_key_company),
    db: Session = Depends(get_db),
):
    """Update the review (like/dislike) for a message."""
    msg = (
        db.query(ChatMessage)
        .filter(ChatMessage.id == message_id, ChatMessage.company_id == company_id)
        .first()
    )
    if not msg:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Message not found.")
    msg.review = payload.review
    db.commit()
    db.refresh(msg)
    return ChatMessageRead.model_validate(msg)


@router.delete("/{company_id}/messages/{message_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_message(
    company_id: int,
    message_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    msg = (
        db.query(ChatMessage)
        .filter(ChatMessage.id == message_id, ChatMessage.company_id == company_id)
        .first()
    )
    if not msg:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Message not found.")
    db.delete(msg)
    db.commit()
