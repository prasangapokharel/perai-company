"""Company request tracking model."""

from datetime import datetime

from sqlalchemy import Column, DateTime, ForeignKey, Integer, Numeric, String
from sqlalchemy.orm import relationship

from app.core.database import Base


class CompanyRequest(Base):
    """Track company chat request usage and deducted balance."""

    __tablename__ = "company_requests"

    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(
        Integer,
        ForeignKey("company.id", ondelete="CASCADE"),
        nullable=False,
        index=True,
    )
    token_consume = Column(Integer, nullable=False, default=0)
    balance_deducted = Column(Numeric(12, 6), nullable=False, default=0)
    ip = Column(String(45), nullable=True)
    date = Column(DateTime, default=datetime.utcnow, nullable=False)

    company = relationship("Company", back_populates="company_requests", lazy="selectin")
