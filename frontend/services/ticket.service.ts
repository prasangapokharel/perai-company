import type { ApiAuth } from "@/lib/api-auth"
import { apiClient } from "@/lib/api-client"

export type Ticket = {
  id: number
  company_id: number
  issue: string
  category: "payment" | "technical" | "general"
  status: "open" | "closed"
  created_at: string
  updated_at: string
  ticket_opened_records: TicketOpened[]
}

export type TicketOpened = {
  id: number
  company_id: number
  ticket_id: number
  opened_at: string
  closed_at?: string | null
  created_at: string
  updated_at: string
}

export type TicketCreateInput = {
  issue: string
  category?: Ticket["category"]
}

export type TicketUpdateInput = {
  issue?: string
  category?: Ticket["category"]
  status?: Ticket["status"]
}

export type TicketHistory = {
  ticket_id: number
  company_id: number
  records: TicketOpened[]
}

export type TicketStats = {
  company_id: number
  total: number
  open: number
  closed: number
  by_category: Record<Ticket["category"], number>
}

export function createTicket(companyId: number, payload: TicketCreateInput, auth?: ApiAuth | string) {
  return apiClient<Ticket>(`/company/${companyId}/tickets`, { method: "POST", body: JSON.stringify(payload) }, auth)
}

export function listTickets(
  companyId: number,
  filters: { status_filter?: Ticket["status"]; category_filter?: Ticket["category"] } = {},
  auth?: ApiAuth | string,
) {
  const params = new URLSearchParams()
  if (filters.status_filter) params.set("status_filter", filters.status_filter)
  if (filters.category_filter) params.set("category_filter", filters.category_filter)
  const query = params.toString() ? `?${params.toString()}` : ""
  return apiClient<Ticket[]>(`/company/${companyId}/tickets${query}`, {}, auth)
}

export function getTicket(companyId: number, ticketId: number, auth?: ApiAuth | string) {
  return apiClient<Ticket>(`/company/${companyId}/tickets/${ticketId}`, {}, auth)
}

export function updateTicket(companyId: number, ticketId: number, payload: TicketUpdateInput, auth?: ApiAuth | string) {
  return apiClient<Ticket>(`/company/${companyId}/tickets/${ticketId}`, { method: "PUT", body: JSON.stringify(payload) }, auth)
}

export function deleteTicket(companyId: number, ticketId: number, auth?: ApiAuth | string) {
  return apiClient<void>(`/company/${companyId}/tickets/${ticketId}`, { method: "DELETE" }, auth)
}

export function getTicketHistory(companyId: number, ticketId: number, auth?: ApiAuth | string) {
  return apiClient<TicketHistory>(`/company/${companyId}/tickets/${ticketId}/history`, {}, auth)
}

export function getTicketStats(companyId: number, auth?: ApiAuth | string) {
  return apiClient<TicketStats>(`/company/${companyId}/tickets-stats`, {}, auth)
}
