import type { AuthSession } from "@/features/auth/hooks"
import { sessionAuth } from "@/features/auth/hooks"
import { apiClientAuth } from "@/lib/api-auth"

export type CompanyRequestUsage = {
  id: number
  company_id: number
  token_consume: number
  balance_deducted: string
  ip: string | null
  date: string
}

export function listCompanyRequests(session: AuthSession) {
  return apiClientAuth<CompanyRequestUsage[]>(
    `/company/${session.companyId}/requests`,
    {},
    sessionAuth(session),
  )
}
