import type { ApiAuth } from "@/lib/api-auth"
import { apiClient } from "@/lib/api-client"

export type CompanySettings = {
  id: number
  company_id: number
  language: "english" | "nepali"
  tone: "formal" | "casual" | "friendly" | "professional"
  max_tokens: number
  message?: string
}

export type CompanySettingsInput = {
  language?: CompanySettings["language"]
  tone?: CompanySettings["tone"]
  max_tokens?: number
}

export function getCompanySettings(companyId: number, auth?: ApiAuth | string) {
  return apiClient<CompanySettings>(`/company/${companyId}/settings`, {}, auth)
}

export function createOrUpdateCompanySettings(companyId: number, payload: Required<Pick<CompanySettingsInput, "language" | "tone" | "max_tokens">>, auth?: ApiAuth | string) {
  return apiClient<CompanySettings>(`/company/${companyId}/settings`, { method: "POST", body: JSON.stringify(payload) }, auth)
}

export function updateCompanySettings(companyId: number, payload: CompanySettingsInput, auth?: ApiAuth | string) {
  return apiClient<CompanySettings>(`/company/${companyId}/settings`, { method: "PUT", body: JSON.stringify(payload) }, auth)
}

export function deleteCompanySettings(companyId: number, auth?: ApiAuth | string) {
  return apiClient<void>(`/company/${companyId}/settings`, { method: "DELETE" }, auth)
}
