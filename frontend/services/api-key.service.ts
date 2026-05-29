import { apiClient } from "@/lib/api-client"

export type APIKey = {
  id: number
  company_id: number
  name: string
  key_preview: string
  status: "active" | "revoked" | "expired"
  expiry_date: string
  last_used_at: string | null
  created_at: string
  updated_at: string
}

export type APIKeyCreateInput = {
  name: string
  expiry_date: string
}

export function listAPIKeys(companyId: number, apiKey?: string) {
  return apiClient<APIKey[]>(`/company/${companyId}/api-keys`, {}, apiKey)
}

export function getAPIKey(companyId: number, keyId: number, apiKey?: string) {
  return apiClient<APIKey>(`/company/${companyId}/api-keys/${keyId}`, {}, apiKey)
}

export function createAPIKey(companyId: number, payload: APIKeyCreateInput, apiKey?: string) {
  return apiClient<any>(`/company/${companyId}/api-keys`, { method: "POST", body: JSON.stringify(payload) }, apiKey)
}

export function updateAPIKey(companyId: number, keyId: number, payload: Partial<APIKeyCreateInput> & { status?: APIKey["status"] }, apiKey?: string) {
  return apiClient<APIKey>(`/company/${companyId}/api-keys/${keyId}`, { method: "PUT", body: JSON.stringify(payload) }, apiKey)
}

export function revokeAPIKey(companyId: number, keyId: number, apiKey?: string) {
  return apiClient<APIKey>(`/company/${companyId}/api-keys/${keyId}/revoke`, { method: "POST" }, apiKey)
}

export function deleteAPIKey(companyId: number, keyId: number, apiKey?: string) {
  return apiClient<{ message: string }>(`/company/${companyId}/api-keys/${keyId}`, { method: "DELETE" }, apiKey)
}
