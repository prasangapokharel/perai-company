import type { AuthSession } from "@/features/auth/hooks"
import { sessionAuth } from "@/features/auth/hooks"
import { apiClientAuth } from "@/lib/api-auth"

export type AdminOverview = {
  total_companies: number
  admin_companies: number
  total_balance: string
  total_topups: string
  total_deducted: string
  total_tickets: number
  open_tickets: number
  total_api_keys: number
  active_api_keys: number
  total_khalti_payments: number
  completed_khalti_payments: number
}

export type AdminCompany = {
  id: number
  company_name: string
  company_email: string
  logo: string | null
  website: string | null
  is_admin: boolean
  balance: string
  created_at: string
  updated_at: string
}

export type AdminCompanyUpdate = {
  company_name?: string
  company_email?: string
  password?: string
  logo?: string
  website?: string
}

export type AdminBalance = {
  company_id: number
  balance: string
  currency: string
}

export type AdminTopup = {
  id: number
  company_id: number
  amount: string
  reference: string | null
  created_at: string
}

export type AdminDeduction = {
  id: number
  company_id: number
  chat_message_id: number | null
  session_id: string | null
  amount: string
  token_consume: number
  model_name: string | null
  created_at: string
}

export type AdminKhaltiPayment = {
  id: number
  company_id: number
  pidx: string
  amount_usd: string
  amount_npr_paisa: number
  status: string
  transaction_id: string | null
  created_at: string
  updated_at: string
}

export type AdminTicket = {
  id: number
  company_id: number
  issue: string
  category: "payment" | "technical" | "general"
  status: "open" | "closed"
  created_at: string
  updated_at: string
  ticket_opened_records: unknown[]
}

export type AdminApiKey = {
  id: number
  company_id: number
  name: string
  key_preview: string
  status: string
  expiry_date: string | null
  last_used_at: string | null
  created_at: string
}

export type AdminSettings = {
  id: number
  company_id: number
  language: string
  tone: string
  max_tokens: number
  created_at: string
  updated_at: string
}

function auth(session: AuthSession) {
  return sessionAuth(session)
}

// --- Overview ---
export function getAdminOverview(session: AuthSession) {
  return apiClientAuth<AdminOverview>("/admin/overview", {}, auth(session))
}

// --- Companies ---
export function listAdminCompanies(session: AuthSession, search?: string) {
  const query = search ? `?search=${encodeURIComponent(search)}&limit=200` : "?limit=200"
  return apiClientAuth<AdminCompany[]>(`/admin/companies${query}`, {}, auth(session))
}

export function getAdminCompany(session: AuthSession, companyId: number) {
  return apiClientAuth<AdminCompany>(`/admin/companies/${companyId}`, {}, auth(session))
}

export function updateAdminCompany(session: AuthSession, companyId: number, payload: AdminCompanyUpdate) {
  return apiClientAuth<AdminCompany>(
    `/admin/companies/${companyId}`,
    { method: "PUT", body: JSON.stringify(payload) },
    auth(session),
  )
}

export function setAdminCompanyRole(session: AuthSession, companyId: number, isAdmin: boolean) {
  return apiClientAuth<AdminCompany>(
    `/admin/companies/${companyId}/role`,
    { method: "PUT", body: JSON.stringify({ is_admin: isAdmin }) },
    auth(session),
  )
}

export function deleteAdminCompany(session: AuthSession, companyId: number) {
  return apiClientAuth<void>(
    `/admin/companies/${companyId}`,
    { method: "DELETE" },
    auth(session),
  )
}

// --- Balance ---
export function getAdminCompanyBalance(session: AuthSession, companyId: number) {
  return apiClientAuth<AdminBalance>(`/admin/companies/${companyId}/balance`, {}, auth(session))
}

export function adjustAdminBalance(session: AuthSession, companyId: number, amount: number, reason?: string) {
  return apiClientAuth<AdminBalance>(
    `/admin/companies/${companyId}/balance/adjust`,
    { method: "POST", body: JSON.stringify({ amount, reason }) },
    auth(session),
  )
}

export function listAdminTopups(session: AuthSession, companyId: number) {
  return apiClientAuth<AdminTopup[]>(`/admin/companies/${companyId}/topups?limit=100`, {}, auth(session))
}

export function listAdminDeductions(session: AuthSession, companyId: number) {
  return apiClientAuth<AdminDeduction[]>(`/admin/companies/${companyId}/deductions?limit=100`, {}, auth(session))
}

export function listAdminKhaltiPayments(session: AuthSession, companyId?: number) {
  const query = companyId ? `?company_id=${companyId}&limit=100` : "?limit=100"
  return apiClientAuth<AdminKhaltiPayment[]>(`/admin/payments/khalti${query}`, {}, auth(session))
}

// --- Tickets ---
export function listAdminTickets(session: AuthSession, statusFilter?: "open" | "closed") {
  const query = statusFilter ? `?status_filter=${statusFilter}&limit=200` : "?limit=200"
  return apiClientAuth<AdminTicket[]>(`/admin/tickets${query}`, {}, auth(session))
}

export function updateAdminTicket(session: AuthSession, ticketId: number, status: "open" | "closed") {
  return apiClientAuth<AdminTicket>(
    `/admin/tickets/${ticketId}`,
    { method: "PUT", body: JSON.stringify({ status }) },
    auth(session),
  )
}

export function deleteAdminTicket(session: AuthSession, ticketId: number) {
  return apiClientAuth<void>(`/admin/tickets/${ticketId}`, { method: "DELETE" }, auth(session))
}

// --- API keys ---
export function listAdminApiKeys(session: AuthSession, companyId?: number) {
  const query = companyId ? `?company_id=${companyId}&limit=200` : "?limit=200"
  return apiClientAuth<AdminApiKey[]>(`/admin/apikeys${query}`, {}, auth(session))
}

export function revokeAdminApiKey(session: AuthSession, apiKeyId: number) {
  return apiClientAuth<{ id: number; status: string }>(
    `/admin/apikeys/${apiKeyId}/revoke`,
    { method: "POST" },
    auth(session),
  )
}

// --- Settings ---
export function getAdminCompanySettings(session: AuthSession, companyId: number) {
  return apiClientAuth<AdminSettings>(`/admin/companies/${companyId}/settings`, {}, auth(session))
}
