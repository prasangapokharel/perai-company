"""Ticket service for CRUD operations."""

from sqlalchemy.orm import Session
from app.models.ticket import Ticket, TicketOpened, TicketCategory, TicketStatus
from app.schemas.ticketSchema import TicketCreate, TicketUpdate
from datetime import datetime


def createTicket(db: Session, company_id: int, ticket_data: TicketCreate) -> Ticket:
    """Create a new ticket."""
    # Validate category - use string value
    valid_categories = ["payment", "technical", "general"]
    category_str = (
        ticket_data.category.lower()
        if hasattr(ticket_data.category, "lower")
        else ticket_data.category
    )
    if category_str not in valid_categories:
        category_str = "general"

    ticket = Ticket(
        company_id=company_id, issue=ticket_data.issue, category=category_str, status="open"
    )

    db.add(ticket)
    db.flush()  # Get the ticket ID

    # Create ticket_opened record
    ticket_opened = TicketOpened(
        company_id=company_id, ticket_id=ticket.id, opened_at=datetime.utcnow(), closed_at=None
    )
    db.add(ticket_opened)
    db.commit()
    db.refresh(ticket)

    return ticket


def getTicketById(db: Session, company_id: int, ticket_id: int) -> Ticket | None:
    """Get ticket by ID."""
    return db.query(Ticket).filter(Ticket.id == ticket_id, Ticket.company_id == company_id).first()


def listTickets(
    db: Session, company_id: int, status: str | None = None, category: str | None = None
) -> list[Ticket]:
    """List tickets for company with optional filters."""
    query = db.query(Ticket).filter(Ticket.company_id == company_id)

    if status:
        query = query.filter(Ticket.status == status)

    if category:
        query = query.filter(Ticket.category == category)

    return query.order_by(Ticket.created_at.desc()).all()


def updateTicket(
    db: Session, company_id: int, ticket_id: int, ticket_data: TicketUpdate
) -> Ticket | None:
    """Update ticket."""
    ticket = getTicketById(db, company_id, ticket_id)
    if not ticket:
        return None

    # Update fields
    if ticket_data.issue is not None:
        ticket.issue = ticket_data.issue

    if ticket_data.category is not None:
        valid_categories = ["payment", "technical", "general"]
        category_str = (
            ticket_data.category.lower()
            if hasattr(ticket_data.category, "lower")
            else ticket_data.category
        )
        if category_str in valid_categories:
            ticket.category = category_str

    if ticket_data.status is not None:
        valid_statuses = ["open", "closed"]
        status_str = (
            ticket_data.status.lower()
            if hasattr(ticket_data.status, "lower")
            else ticket_data.status
        )
        if status_str in valid_statuses:
            old_status = ticket.status
            ticket.status = status_str

            # If closing ticket, update last ticket_opened record
            if old_status == "open" and status_str == "closed":
                last_opened = (
                    db.query(TicketOpened)
                    .filter(TicketOpened.ticket_id == ticket_id, TicketOpened.closed_at == None)
                    .first()
                )

                if last_opened:
                    last_opened.closed_at = datetime.utcnow()

    db.commit()
    db.refresh(ticket)

    return ticket


def deleteTicket(db: Session, company_id: int, ticket_id: int) -> bool:
    """Delete ticket."""
    ticket = getTicketById(db, company_id, ticket_id)
    if not ticket:
        return False

    db.delete(ticket)
    db.commit()

    return True


def getTicketOpenedRecords(db: Session, company_id: int, ticket_id: int) -> list[TicketOpened]:
    """Get all ticket_opened records for a ticket."""
    return (
        db.query(TicketOpened)
        .filter(TicketOpened.company_id == company_id, TicketOpened.ticket_id == ticket_id)
        .order_by(TicketOpened.opened_at.desc())
        .all()
    )


def getTicketStats(db: Session, company_id: int) -> dict:
    """Get ticket statistics for company."""
    total = db.query(Ticket).filter(Ticket.company_id == company_id).count()
    open_count = (
        db.query(Ticket).filter(Ticket.company_id == company_id, Ticket.status == "open").count()
    )
    closed_count = total - open_count

    by_category = {}
    categories = ["payment", "technical", "general"]
    for category in categories:
        count = (
            db.query(Ticket)
            .filter(Ticket.company_id == company_id, Ticket.category == category)
            .count()
        )
        by_category[category] = count

    return {"total": total, "open": open_count, "closed": closed_count, "by_category": by_category}
