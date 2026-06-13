"""Chat message model."""

import enum
import secrets
import string
from datetime import datetime

from sqlalchemy import Column, Integer, String, Text, DateTime, ForeignKey, Enum as SAEnum
from sqlalchemy.orm import relationship

from app.core.database import Base


class ReviewEnum(str, enum.Enum):
    NOTHING = "0"
    LIKE = "1"
    DISLIKE = "2"


def generate_session_id() -> str:
    """Generate 12-char random session ID (letters + digits)."""
    chars = string.ascii_letters + string.digits
    return "".join(secrets.choice(chars) for _ in range(12))


class ChatMessage(Base):
    __tablename__ = "chat_message"

    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(Integer, ForeignKey("company.id", ondelete="CASCADE"), nullable=False, index=True)
    session_id = Column(String(12), nullable=False, default=generate_session_id, index=True)
    conversation = Column(Text, nullable=False, default="")
    review = Column(String(1), nullable=False, default=ReviewEnum.NOTHING.value)
    ip = Column(String(45), nullable=True)
    token_consume = Column(Integer, nullable=False, default=0)
    model_name = Column(String(255), nullable=True)

    created_at = Column(DateTime, nullable=False, default=datetime.utcnow)
    updated_at = Column(DateTime, nullable=False, default=datetime.utcnow, onupdate=datetime.utcnow)

    company = relationship("Company", back_populates="chat_messages", lazy="selectin")
