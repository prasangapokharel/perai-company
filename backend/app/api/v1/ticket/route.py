"""Ticket API routes — protected by company ownership."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.api.v1.ticket.service import (
    createTicket,
    deleteTicket,
    getTicketById,
    getTicketOpenedRecords,
    getTicketStats,
    listTickets,
    updateTicket,
)
from app.core.database import get_db
from app.core.security import require_company
from app.schemas.ticketSchema import TicketCreate, TicketRead, TicketUpdate

router = APIRouter(prefix="/api/v1/company", tags=["tickets"])


@router.post("/{company_id}/tickets", response_model=TicketRead, status_code=status.HTTP_201_CREATED)
def create_ticket(
    company_id: int,
    ticket_data: TicketCreate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return createTicket(db, company_id, ticket_data)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err))


@router.get("/{company_id}/tickets", response_model=list[TicketRead])
def list_tickets(
    company_id: int,
    status_filter: str | None = None,
    category_filter: str | None = None,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    try:
        return listTickets(db, company_id, status_filter, category_filter)
    except ValueError as err:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(err))


@router.get("/{company_id}/tickets/{ticket_id}", response_model=TicketRead)
def get_ticket(
    company_id: int,
    ticket_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    ticket = getTicketById(db, company_id, ticket_id)
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found.")
    return ticket


@router.put("/{company_id}/tickets/{ticket_id}", response_model=TicketRead)
def update_ticket(
    company_id: int,
    ticket_id: int,
    ticket_data: TicketUpdate,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    ticket = updateTicket(db, company_id, ticket_id, ticket_data)
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found.")
    return ticket


@router.delete("/{company_id}/tickets/{ticket_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_ticket(
    company_id: int,
    ticket_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    if not deleteTicket(db, company_id, ticket_id):
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found.")


@router.get("/{company_id}/tickets/{ticket_id}/history")
def get_ticket_history(
    company_id: int,
    ticket_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    ticket = getTicketById(db, company_id, ticket_id)
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found.")
    records = getTicketOpenedRecords(db, company_id, ticket_id)
    return {"ticket_id": ticket_id, "company_id": company_id, "records": records}


@router.get("/{company_id}/tickets-stats")
def get_ticket_stats(
    company_id: int,
    _: int = Depends(require_company),
    db: Session = Depends(get_db),
):
    stats = getTicketStats(db, company_id)
    return {"company_id": company_id, **stats}
