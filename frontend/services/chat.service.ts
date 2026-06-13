import type { ApiAuth } from "@/lib/api-auth"
import { apiClient } from "@/lib/api-client"

export type ChatQuery = { prompt: string; session_id?: string }
export type ChatResponse = {
  model_name: string
  company_id: number
  response: string
  balance_remaining?: string
  session_id?: string
  message_id?: number
  token_consume?: number
}

export function queryChat(companyId: number, payload: ChatQuery, auth?: ApiAuth | string) {
  return apiClient<ChatResponse>(`/company/${companyId}/chat/query`, { method: "POST", body: JSON.stringify(payload) }, auth)
}

export function streamChat(companyId: number, message: string, auth?: ApiAuth | string) {
  return apiClient<{ message: string }>(`/company/${companyId}/chat/stream`, { method: "POST", body: JSON.stringify({ message }) }, auth)
}
