import type { AuthSession } from "@/features/auth/hooks"
import { sessionAuth } from "@/features/auth/hooks"
import { apiClientAuth } from "@/lib/api-auth"

export type CompanyBalance = {
  company_id: number
  balance: string
  currency: string
}

export type BalanceTopup = {
  id: number
  company_id: number
  amount: string
  reference: string | null
  created_at: string
}

export type BalanceDeducted = {
  id: number
  company_id: number
  chat_message_id: number | null
  session_id: string | null
  amount: string
  token_consume: number
  model_name: string | null
  created_at: string
}

export const CREDIT_PACKAGES = [
  { amount: 5, label: "$5", description: "Starter credits" },
  { amount: 10, label: "$10", description: "Light usage" },
  { amount: 25, label: "$25", description: "Recommended" },
  { amount: 50, label: "$50", description: "Growing teams" },
  { amount: 100, label: "$100", description: "High volume" },
] as const

export function getCompanyBalance(session: AuthSession) {
  return apiClientAuth<CompanyBalance>(
    `/companyBalance/${session.companyId}`,
    {},
    sessionAuth(session),
  )
}

export function topupCompanyBalance(session: AuthSession, amount: number) {
  return apiClientAuth<CompanyBalance>(
    `/companyBalance/${session.companyId}/topup`,
    { method: "POST", body: JSON.stringify({ amount }) },
    sessionAuth(session),
  )
}

export function listBalanceTopups(session: AuthSession) {
  return apiClientAuth<BalanceTopup[]>(
    `/companyBalance/${session.companyId}/topups`,
    {},
    sessionAuth(session),
  )
}

export function listBalanceDeducted(session: AuthSession) {
  return apiClientAuth<BalanceDeducted[]>(
    `/balanceDeducted/${session.companyId}`,
    {},
    sessionAuth(session),
  )
}
