"""Ticket schemas for Pydantic validation."""

from pydantic import BaseModel
from datetime import datetime
from typing import Optional


class TicketCreate(BaseModel):
    """Create ticket request."""

    issue: str
    category: str = "general"  # payment, technical, general

    class Config:
        from_attributes = True


class TicketUpdate(BaseModel):
    """Update ticket request."""

    issue: Optional[str] = None
    category: Optional[str] = None
    status: Optional[str] = None  # open, closed

    class Config:
        from_attributes = True


class TicketOpenedCreate(BaseModel):
    """Create ticket opened record."""

    ticket_id: int
    opened_at: datetime
    closed_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class TicketOpenedRead(BaseModel):
    """Read ticket opened record."""

    id: int
    company_id: int
    ticket_id: int
    opened_at: datetime
    closed_at: Optional[datetime]
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class TicketRead(BaseModel):
    """Read ticket response."""

    id: int
    company_id: int
    issue: str
    category: str
    status: str
    created_at: datetime
    updated_at: datetime
    ticket_opened_records: list[TicketOpenedRead] = []

    class Config:
        from_attributes = True


class TicketWithDetails(BaseModel):
    """Ticket with all details."""

    id: int
    company_id: int
    issue: str
    category: str
    status: str
    created_at: datetime
    updated_at: datetime
    total_opened_count: int = 0
    last_opened_at: Optional[datetime] = None
    last_closed_at: Optional[datetime] = None

    class Config:
        from_attributes = True
