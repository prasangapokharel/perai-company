import type { ApiAuth } from "@/lib/api-auth"
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

export type APIKeyCreateResponse = APIKey & {
  key: string
  message?: string
}

export function listAPIKeys(companyId: number, auth?: ApiAuth | string) {
  return apiClient<APIKey[]>(`/company/${companyId}/api-keys`, {}, auth)
}

export function getAPIKey(companyId: number, keyId: number, auth?: ApiAuth | string) {
  return apiClient<APIKey>(`/company/${companyId}/api-keys/${keyId}`, {}, auth)
}

export function createAPIKey(companyId: number, payload: APIKeyCreateInput, auth?: ApiAuth | string) {
  return apiClient<APIKeyCreateResponse>(`/company/${companyId}/api-keys`, { method: "POST", body: JSON.stringify(payload) }, auth)
}

export function updateAPIKey(companyId: number, keyId: number, payload: Partial<APIKeyCreateInput> & { status?: APIKey["status"] }, auth?: ApiAuth | string) {
  return apiClient<APIKey>(`/company/${companyId}/api-keys/${keyId}`, { method: "PUT", body: JSON.stringify(payload) }, auth)
}

export function revokeAPIKey(companyId: number, keyId: number, auth?: ApiAuth | string) {
  return apiClient<APIKey>(`/company/${companyId}/api-keys/${keyId}/revoke`, { method: "POST" }, auth)
}

export function deleteAPIKey(companyId: number, keyId: number, auth?: ApiAuth | string) {
  return apiClient<void>(`/company/${companyId}/api-keys/${keyId}`, { method: "DELETE" }, auth)
}
