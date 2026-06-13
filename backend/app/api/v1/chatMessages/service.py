"""Chat session listing service."""

from math import ceil

from sqlalchemy import func
from sqlalchemy.orm import Session

from app.models.chatMessage import ChatMessage


def list_sessions(
    db: Session,
    company_id: int,
    page: int = 1,
    page_size: int = 20,
) -> tuple[list[ChatMessage], int, int, int, int]:
    page = max(1, page)
    page_size = min(max(1, page_size), 100)

    base = db.query(ChatMessage).filter(ChatMessage.company_id == company_id)
    total = base.with_entities(func.count(ChatMessage.id)).scalar() or 0
    items = (
        base.order_by(ChatMessage.created_at.desc())
        .offset((page - 1) * page_size)
        .limit(page_size)
        .all()
    )
    total_pages = ceil(total / page_size) if total else 0
    return items, total, page, page_size, total_pages


def delete_session_messages(db: Session, company_id: int, session_id: str) -> int:
    deleted = (
        db.query(ChatMessage)
        .filter(
            ChatMessage.company_id == company_id,
            ChatMessage.session_id == session_id,
        )
        .delete(synchronize_session=False)
    )
    db.commit()
    return deleted
