import { clearAuthCookie } from "@/lib/auth-cookie"
import { clearAuthSession } from "@/features/auth/hooks"

export function logoutAndRedirectToLogin() {
  if (typeof window === "undefined") return
  clearAuthSession()
  clearAuthCookie()
  window.location.href = "/login"
}

export function isAuthError(status: number, detail: string) {
  if (status === 401) return true
  const lower = detail.toLowerCase()
  return lower.includes("invalid or expired token") || lower.includes("authentication required")
}
