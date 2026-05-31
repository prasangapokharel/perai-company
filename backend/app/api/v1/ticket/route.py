"""Ticket API routes."""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from app.core.database import getDb
from app.schemas.ticketSchema import TicketCreate, TicketUpdate, TicketRead, TicketWithDetails
from app.api.v1.ticket.service import (
    createTicket,
    getTicketById,
    listTickets,
    updateTicket,
    deleteTicket,
    getTicketOpenedRecords,
    getTicketStats
)


router = APIRouter(
    prefix="/api/v1/company",
    tags=["tickets"]
)


@router.post("/{company_id}/tickets", response_model=TicketRead, status_code=status.HTTP_201_CREATED)
async def createTicketRoute(
    company_id: int,
    ticket_data: TicketCreate,
    db: Session = Depends(getDb)
):
    """Create a new ticket."""
    try:
        ticket = createTicket(db, company_id, ticket_data)
        return ticket
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(err)
        )


@router.get("/{company_id}/tickets", response_model=list[TicketRead])
async def listTicketsRoute(
    company_id: int,
    status_filter: str | None = None,
    category_filter: str | None = None,
    db: Session = Depends(getDb)
):
    """List all tickets for company."""
    try:
        tickets = listTickets(db, company_id, status_filter, category_filter)
        return tickets
    except ValueError as err:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(err)
        )


@router.get("/{company_id}/tickets/{ticket_id}", response_model=TicketRead)
async def getTicketRoute(
    company_id: int,
    ticket_id: int,
    db: Session = Depends(getDb)
):
    """Get ticket details."""
    ticket = getTicketById(db, company_id, ticket_id)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    return ticket


@router.put("/{company_id}/tickets/{ticket_id}", response_model=TicketRead)
async def updateTicketRoute(
    company_id: int,
    ticket_id: int,
    ticket_data: TicketUpdate,
    db: Session = Depends(getDb)
):
    """Update ticket."""
    ticket = updateTicket(db, company_id, ticket_id, ticket_data)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    return ticket


@router.delete("/{company_id}/tickets/{ticket_id}", status_code=status.HTTP_204_NO_CONTENT)
async def deleteTicketRoute(
    company_id: int,
    ticket_id: int,
    db: Session = Depends(getDb)
):
    """Delete ticket."""
    success = deleteTicket(db, company_id, ticket_id)
    if not success:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    return None


@router.get("/{company_id}/tickets/{ticket_id}/history")
async def getTicketHistoryRoute(
    company_id: int,
    ticket_id: int,
    db: Session = Depends(getDb)
):
    """Get ticket open/close history."""
    ticket = getTicketById(db, company_id, ticket_id)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    records = getTicketOpenedRecords(db, company_id, ticket_id)
    return {
        "ticket_id": ticket_id,
        "company_id": company_id,
        "records": records
    }


@router.get("/{company_id}/tickets-stats")
async def getTicketStatsRoute(
    company_id: int,
    db: Session = Depends(getDb)
):
    """Get ticket statistics."""
    stats = getTicketStats(db, company_id)
    return {
        "company_id": company_id,
        **stats
    }
