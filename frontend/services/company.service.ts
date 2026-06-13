import type { ApiAuth } from "@/lib/api-auth"
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
  content?: string | null
  created_at?: string
  updated_at?: string
}

export type CompanyListResponse = Company[]

export function listCompanies(auth?: ApiAuth | string) {
  return apiClient<Company[]>("/company", {}, auth)
}

export function createCompany(payload: CompanyCreateInput) {
  return apiClient<Company>("/company", { method: "POST", body: JSON.stringify(payload) })
}

export function getCompany(companyId: number, auth?: ApiAuth | string) {
  return apiClient<Company>(`/company/${companyId}`, {}, auth)
}

export function updateCompany(companyId: number, payload: CompanyUpdateInput, auth?: ApiAuth | string) {
  return apiClient<Company>(`/company/${companyId}`, { method: "PUT", body: JSON.stringify(payload) }, auth)
}

export function getCompanyFinetune(companyId: number, auth?: ApiAuth | string) {
  return apiClient<CompanyFinetune>(`/company/${companyId}/finetune`, {}, auth)
}

export function upsertCompanyFinetune(companyId: number, content: string, auth?: ApiAuth | string) {
  return apiClient<CompanyFinetune>(`/company/${companyId}/finetune`, { method: "POST", body: JSON.stringify({ content }) }, auth)
}
