from datetime import datetime, timezone

from sqlalchemy import Column, DateTime, ForeignKey, Integer, Numeric, String

from app.core.database import Base


class KhaltiPayment(Base):
    __tablename__ = "khalti_payment"

    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(
        Integer,
        ForeignKey("company.id", ondelete="CASCADE"),
        nullable=False,
        index=True,
    )
    pidx = Column(String(64), nullable=False, unique=True, index=True)
    amount_usd = Column(Numeric(14, 6), nullable=False)
    amount_npr_paisa = Column(Integer, nullable=False)
    status = Column(String(32), nullable=False, default="Initiated")
    transaction_id = Column(String(128), nullable=True)
    created_at = Column(
        DateTime(timezone=True),
        default=lambda: datetime.now(timezone.utc),
        nullable=False,
    )
    updated_at = Column(
        DateTime(timezone=True),
        default=lambda: datetime.now(timezone.utc),
        onupdate=lambda: datetime.now(timezone.utc),
        nullable=False,
    )
