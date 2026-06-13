from datetime import datetime, timezone

from sqlalchemy import Column, DateTime, ForeignKey, Integer, Numeric, String
from sqlalchemy.orm import relationship

from app.core.database import Base


class BalanceDeduct(Base):
    __tablename__ = "balance_deduct"

    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(
        Integer,
        ForeignKey("company.id", ondelete="CASCADE"),
        nullable=False,
        index=True,
    )
    chat_message_id = Column(
        Integer,
        ForeignKey("chat_message.id", ondelete="SET NULL"),
        nullable=True,
        index=True,
    )
    session_id = Column(String(12), nullable=True, index=True)
    amount = Column(Numeric(14, 6), nullable=False)
    token_consume = Column(Integer, nullable=False, default=0)
    model_name = Column(String(255), nullable=True)
    created_at = Column(
        DateTime(timezone=True),
        default=lambda: datetime.now(timezone.utc),
        nullable=False,
    )

    company = relationship("Company", back_populates="balance_deductions", lazy="selectin")
