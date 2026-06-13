import type { ApiAuth } from "@/lib/api-auth"
import { apiClient } from "@/lib/api-client"

export type ChatSessionRow = {
  id: number
  company_id: number
  session_id: string
  conversation: string
  review: string
  ip: string | null
  token_consume: number
  model_name: string | null
  created_at: string
  updated_at: string
}

export type PaginatedSessions = {
  items: ChatSessionRow[]
  total: number
  page: number
  page_size: number
  total_pages: number
}

export function listSessions(
  companyId: number,
  page: number,
  pageSize: number,
  auth: ApiAuth,
) {
  const params = new URLSearchParams({
    page: String(page),
    page_size: String(pageSize),
  })
  return apiClient<PaginatedSessions>(
    `/company/${companyId}/sessions?${params.toString()}`,
    {},
    auth,
  )
}

export function deleteSession(companyId: number, sessionId: string, auth: ApiAuth) {
  return apiClient<void>(
    `/company/${companyId}/sessions/${sessionId}`,
    { method: "DELETE" },
    auth,
  )
}

export function formatReview(review: string) {
  if (review === "1") return "Like"
  if (review === "2") return "Dislike"
  return "—"
}

export function truncateConversation(text: string, max = 120) {
  const oneLine = text.replace(/\s+/g, " ").trim()
  if (oneLine.length <= max) return oneLine
  return `${oneLine.slice(0, max)}…`
}
