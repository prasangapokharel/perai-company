import type { ApiAuth } from "@/lib/api-auth"
import { apiClient } from "@/lib/api-client"

export type DashboardAPIKey = {
  id: number
  name: string
  key_preview: string
  status: "active" | "revoked" | "expired" | string
  created_at: string
  expiry_date: string
}

export type DashboardUsagePeriod = {
  total_requests: number
  total_tokens_consumed: number
  total_balance_deducted: string
}

export type CompanyDashboard = {
  company_id: number
  company_name: string
  model_name: string | null
  total_api_keys: number
  active_api_keys: number
  api_keys: DashboardAPIKey[]
  usage_metrics: {
    today: DashboardUsagePeriod
    weekly: DashboardUsagePeriod
    monthly: DashboardUsagePeriod
  }
  credit_deducted: {
    today: string
    weekly: string
    monthly: string
  }
  last_request_at: string | null
  total_tokens_all_time: number
  total_balance_deducted_all_time: string
  current_balance: string
}

export function getCompanyDashboard(companyId: number, auth?: ApiAuth | string) {
  return apiClient<CompanyDashboard>(`/company/${companyId}/dashboard`, {}, auth)
}
