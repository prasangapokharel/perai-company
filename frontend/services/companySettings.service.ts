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

export function getCompanySettings(companyId: number, apiKey?: string) {
  return apiClient<CompanySettings>(`/company/${companyId}/settings`, {}, apiKey)
}

export function createOrUpdateCompanySettings(companyId: number, payload: Required<Pick<CompanySettingsInput, "language" | "tone" | "max_tokens">>, apiKey?: string) {
  return apiClient<CompanySettings>(`/company/${companyId}/settings`, { method: "POST", body: JSON.stringify(payload) }, apiKey)
}

export function updateCompanySettings(companyId: number, payload: CompanySettingsInput, apiKey?: string) {
  return apiClient<CompanySettings>(`/company/${companyId}/settings`, { method: "PUT", body: JSON.stringify(payload) }, apiKey)
}

export function deleteCompanySettings(companyId: number, apiKey?: string) {
  return apiClient<void>(`/company/${companyId}/settings`, { method: "DELETE" }, apiKey)
}
