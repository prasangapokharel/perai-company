import type { ApiAuth } from "@/lib/api-auth"
import { buildAuthHeaders } from "@/lib/api-auth"
import { isAuthError, logoutAndRedirectToLogin } from "@/lib/auth-session"

export const API_BASE_URL = process.env.API_URL ?? process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api/v1"

export class APIError extends Error {
  constructor(
    public status: number,
    public detail: string,
    message?: string
  ) {
    super(message || `API Error ${status}: ${detail}`)
    this.name = "APIError"
  }
}

function resolveAuthHeaders(auth?: ApiAuth | string): Record<string, string> {
  if (!auth) return {}
  if (typeof auth === "string") return auth ? { "X-API-Key": auth } : {}
  if (auth.accessToken) return { Authorization: `Bearer ${auth.accessToken}` }
  if (auth.apiKey) return { "X-API-Key": auth.apiKey }
  return {}
}

export async function apiClient<T>(path: string, init: RequestInit = {}, auth?: ApiAuth | string) {
  const isForm = init.body instanceof FormData
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: {
      ...(isForm ? {} : { "Content-Type": "application/json" }),
      ...resolveAuthHeaders(auth),
      ...(init.headers as Record<string, string> | undefined),
    },
  })

  if (!response.ok) {
    let detail = `HTTP ${response.status}`
    try {
      const body = await response.json()
      detail = body.detail || JSON.stringify(body)
    } catch {
      detail = response.statusText || detail
    }
    if (typeof window !== "undefined" && isAuthError(response.status, detail)) {
      logoutAndRedirectToLogin()
    }
    throw new APIError(response.status, detail)
  }

  if (response.status === 204) return undefined as T

  const text = await response.text()
  if (!text) return undefined as T
  return JSON.parse(text) as T
}
