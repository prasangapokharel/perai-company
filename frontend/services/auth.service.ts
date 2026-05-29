import { apiClient } from "@/lib/api-client"

export type AuthPayload = {
  email: string
  password: string
}

export type AuthRegisterPayload = {
  company_name: string
  company_email: string
  password: string
  logo?: string
  website?: string
}

export type CompanyRead = {
  id: number
  company_name: string
  company_email: string
  logo?: string | null
  website?: string | null
  company_model_name?: string | null
  created_at: string
  updated_at: string
}

export type AuthLoginResponse = {
  message: string
  company: CompanyRead
  api_key_instruction: string
}

export type APIKeyCreateResponse = {
  id: number
  company_id: number
  name: string
  key: string
  key_preview: string
  status: string
  expiry_date?: string | null
  created_at: string
}

/**
 * Register a new company
 */
export async function registerCompany(
  payload: AuthRegisterPayload
): Promise<CompanyRead> {
  return apiClient<CompanyRead>("/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
  })
}

/**
 * Login company with email and password
 */
export async function loginCompany(
  email: string,
  password: string
): Promise<AuthLoginResponse> {
  return apiClient<AuthLoginResponse>("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  })
}

/**
 * Create an API key for a company
 */
export async function createCompanyAPIKey(
  companyId: number,
  name: string,
  expiryDate?: string
): Promise<APIKeyCreateResponse> {
  return apiClient<APIKeyCreateResponse>(
    `/company/${companyId}/api-keys`,
    {
      method: "POST",
      body: JSON.stringify({
        name,
        expiry_date: expiryDate,
      }),
    }
  )
}
