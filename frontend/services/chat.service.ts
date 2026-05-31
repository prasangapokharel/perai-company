import { apiClient } from "@/lib/api-client"

export type ChatQuery = { prompt: string }
export type ChatResponse = { model_name: string; company_id: number; response: string }

export function queryChat(companyId: number, payload: ChatQuery, apiKey?: string) {
  return apiClient<ChatResponse>(`/company/${companyId}/chat/query`, { method: "POST", body: JSON.stringify(payload) }, apiKey)
}

export function streamChat(companyId: number, message: string, apiKey?: string) {
  return apiClient<{ message: string }>(`/company/${companyId}/chat/stream`, { method: "POST", body: JSON.stringify({ message }) }, apiKey)
}
