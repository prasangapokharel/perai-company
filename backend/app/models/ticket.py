"""Ticket model for support tickets."""

from sqlalchemy import Column, Integer, String, Text, DateTime, Enum, ForeignKey
from sqlalchemy.orm import relationship
from datetime import datetime
from enum import Enum as PyEnum
from app.core.database import Base


class TicketCategory(PyEnum):
    """Ticket category enum."""
    payment = "payment"
    technical = "technical"
    general = "general"


class TicketStatus(PyEnum):
    """Ticket status enum."""
    open = "open"
    closed = "closed"


class Ticket(Base):
    """Support ticket model."""
    __tablename__ = "ticket"
    
    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(Integer, ForeignKey("company.id", ondelete="CASCADE"), nullable=False, index=True)
    issue = Column(Text, nullable=False)
    category = Column(Enum(TicketCategory), default=TicketCategory.general, nullable=False)
    status = Column(Enum(TicketStatus), default=TicketStatus.open, nullable=False)
    
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    
    # Relationship to company
    company = relationship("Company", back_populates="tickets")
    
    # Relationship to ticket_opened
    ticket_opened_records = relationship("TicketOpened", back_populates="ticket", cascade="all, delete-orphan")


class TicketOpened(Base):
    """Track when tickets are opened and closed."""
    __tablename__ = "ticket_opened"
    
    id = Column(Integer, primary_key=True, index=True)
    company_id = Column(Integer, ForeignKey("company.id", ondelete="CASCADE"), nullable=False, index=True)
    ticket_id = Column(Integer, ForeignKey("ticket.id", ondelete="CASCADE"), nullable=False, index=True)
    
    opened_at = Column(DateTime, nullable=False)
    closed_at = Column(DateTime, nullable=True)  # Null if still open
    
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    
    # Relationships
    company = relationship("Company")
    ticket = relationship("Ticket", back_populates="ticket_opened_records")
