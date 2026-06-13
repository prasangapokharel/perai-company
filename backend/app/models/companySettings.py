"""Company Settings ORM Model"""
from sqlalchemy import Column, Integer, String, DateTime, ForeignKey, Enum
from datetime import datetime
from app.core.database import Base
import enum


class LanguageEnum(str, enum.Enum):
    """Supported languages for company"""

    ENGLISH = "english"
    NEPALI = "nepali"


class ToneEnum(str, enum.Enum):
    """Supported tones for AI responses"""

    FORMAL = "formal"
    CASUAL = "casual"
    FRIENDLY = "friendly"
    PROFESSIONAL = "professional"


class CompanySettings(Base):
    """Company Settings model for storing AI behavior preferences.

    This table stores company-specific settings for:
    - Language preference for AI responses
    - Tone/style of AI responses
    - Maximum tokens for API responses
    - Created/updated timestamps
    """

    __tablename__ = "company_settings"

    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(
        Integer,
        ForeignKey("company.id", ondelete="CASCADE"),
        nullable=False,
        unique=True,
        index=True,
    )

    language = Column(String(50), default="english", nullable=False)
    tone = Column(String(50), default="formal", nullable=False)
    max_tokens = Column(Integer, default=1000, nullable=False)

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

    def __repr__(self):
        return (
            f"<CompanySettings(id={self.id}, company_id={self.company_id}, "
            f"language={self.language}, tone={self.tone}, "
            f"max_tokens={self.max_tokens})>"
        )
