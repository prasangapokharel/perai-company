import { apiClientAuth } from "@/lib/api-auth"

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

export type AuthMeResponse = {
  company_id: number
  company_name: string
  company_email: string
  balance: string
  currency: string
}

export type AuthLoginResponse = {
  access_token: string
  token_type: string
  company: CompanyRead
}

export type AuthRegisterPayload = {
  company_name: string
  company_email: string
  password: string
  logo?: string
  website?: string
}

export type AuthPayload = {
  email: string
  password: string
}

export async function registerCompany(payload: AuthRegisterPayload): Promise<CompanyRead> {
  return apiClientAuth<CompanyRead>("/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
  })
}

export async function loginCompany(email: string, password: string): Promise<AuthLoginResponse> {
  return apiClientAuth<AuthLoginResponse>("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  })
}

export async function getAuthMe(auth: { apiKey?: string; accessToken?: string }) {
  return apiClientAuth<AuthMeResponse>("/auth/me", {}, auth)
}
