"""Company database models."""

from sqlalchemy import Boolean, Column, DateTime, ForeignKey, Integer, String, UniqueConstraint
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
import enum

from app.core.database import Base


class APIKeyStatus(str, enum.Enum):
    """API key status enum."""

    ACTIVE = "active"
    REVOKED = "revoked"
    EXPIRED = "expired"


class Company(Base):
    __tablename__ = "company"
    __table_args__ = (
        UniqueConstraint("company_name", name="uq_company_company_name"),
        UniqueConstraint("company_email", name="uq_company_company_email"),
    )

    id = Column(Integer, primary_key=True, index=True)
    company_name = Column(String(255), nullable=False, index=True)
    company_email = Column(String(255), nullable=False, index=True)
    password_hash = Column(String(255), nullable=False)
    logo = Column(String(500), nullable=True)
    website = Column(String(500), nullable=True)
    is_admin = Column(Boolean, nullable=False, server_default="false", default=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now(), nullable=False)
    updated_at = Column(
        DateTime(timezone=True), server_default=func.now(), onupdate=func.now(), nullable=False
    )

    finetunes = relationship(
        "CompanyFinetune",
        back_populates="company",
        cascade="all, delete-orphan",
        lazy="selectin",
    )

    tickets = relationship(
        "Ticket",
        back_populates="company",
        cascade="all, delete-orphan",
        lazy="selectin",
    )

    company_requests = relationship(
        "CompanyRequest",
        back_populates="company",
        cascade="all, delete-orphan",
        lazy="selectin",
    )

    chat_messages = relationship(
        "ChatMessage",
        back_populates="company",
        cascade="all, delete-orphan",
        lazy="selectin",
    )

    balance_account = relationship(
        "CompanyBalance",
        back_populates="company",
        cascade="all, delete-orphan",
        uselist=False,
        lazy="selectin",
    )

    balance_deductions = relationship(
        "BalanceDeduct",
        back_populates="company",
        cascade="all, delete-orphan",
        lazy="selectin",
    )

    balance_topups = relationship(
        "BalanceTopup",
        back_populates="company",
        cascade="all, delete-orphan",
        lazy="selectin",
    )

    @property
    def company_model_name(self) -> str | None:
        """Get company model name from finetune data."""
        if self.finetunes and len(self.finetunes) > 0:
            return self.finetunes[0].company_model_name
        return None


class CompanyFinetune(Base):
    __tablename__ = "company_finetune"
    __table_args__ = (UniqueConstraint("company_id", name="uq_company_finetune_company_id"),)

    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(
        Integer,
        ForeignKey("company.id", ondelete="CASCADE"),
        nullable=False,
        index=True,
    )
    company_model_name = Column(String(255), nullable=False, index=True)  # perai-{company_name}
    rag_company_path = Column(String(1000), nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now(), nullable=False)
    updated_at = Column(
        DateTime(timezone=True), server_default=func.now(), onupdate=func.now(), nullable=False
    )

    company = relationship("Company", back_populates="finetunes", lazy="selectin")


class APIKey(Base):
    """API Key model for company authentication."""

    __tablename__ = "api_key"
    __table_args__ = (
        UniqueConstraint("key_hash", name="uq_api_key_key_hash"),
        UniqueConstraint("company_id", "name", name="uq_api_key_company_name"),
    )

    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(
        Integer,
        ForeignKey("company.id", ondelete="CASCADE"),
        nullable=False,
        index=True,
    )
    name = Column(String(255), nullable=False, index=True)
    key_hash = Column(String(512), nullable=False, unique=True, index=True)
    key_preview = Column(String(20), nullable=False)  # First 10 + last 10 chars
    status = Column(
        String(20),
        default=APIKeyStatus.ACTIVE.value,
        nullable=False,
        index=True,
    )
    expiry_date = Column(DateTime(timezone=True), nullable=True)
    last_used_at = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now(), nullable=False)
    updated_at = Column(
        DateTime(timezone=True), server_default=func.now(), onupdate=func.now(), nullable=False
    )

    company = relationship("Company", backref="api_keys", lazy="selectin")
