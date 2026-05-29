import { apiClient } from "@/lib/api-client"

export type Company = {
  id: number
  company_name: string
  company_email: string
  logo?: string | null
  website?: string | null
}

export type CompanyCreateInput = {
  company_name: string
  company_email: string
  password: string
  logo?: string
  website?: string
}

export type CompanyUpdateInput = Partial<CompanyCreateInput>

export type CompanyFinetune = {
  id: number
  company_id: number
  company_model_name: string
  rag_company_path: string
}

export type CompanyListResponse = Company[]

export function listCompanies(apiKey?: string) {
  return apiClient<Company[]>("/company", {}, apiKey)
}

export function createCompany(payload: CompanyCreateInput) {
  return apiClient<Company>("/company", { method: "POST", body: JSON.stringify(payload) })
}

export function getCompany(companyId: number, apiKey?: string) {
  return apiClient<Company>(`/company/${companyId}`, {}, apiKey)
}

export function updateCompany(companyId: number, payload: CompanyUpdateInput, apiKey?: string) {
  return apiClient<Company>(`/company/${companyId}`, { method: "PUT", body: JSON.stringify(payload) }, apiKey)
}

export function getCompanyFinetune(companyId: number, apiKey?: string) {
  return apiClient<CompanyFinetune>(`/company/${companyId}/finetune`, {}, apiKey)
}

export function upsertCompanyFinetune(companyId: number, content: string, apiKey?: string) {
  return apiClient<CompanyFinetune>(`/company/${companyId}/finetune`, { method: "POST", body: JSON.stringify({ content }) }, apiKey)
}
