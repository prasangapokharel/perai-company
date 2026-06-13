import { API_BASE_URL, APIError, apiClient } from "@/lib/api-client"

export type ApiAuth = {
  apiKey?: string
  accessToken?: string
}

export function buildAuthHeaders(auth?: ApiAuth): Record<string, string> {
  if (auth?.accessToken) return { Authorization: `Bearer ${auth.accessToken}` }
  if (auth?.apiKey) return { "X-API-Key": auth.apiKey }
  return {}
}

export async function apiClientAuth<T>(
  path: string,
  init: RequestInit = {},
  auth?: ApiAuth,
) {
  const isForm = init.body instanceof FormData
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: {
      ...(isForm ? {} : { "Content-Type": "application/json" }),
      ...buildAuthHeaders(auth),
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
    throw new APIError(response.status, detail)
  }

  if (response.status === 204) return undefined as T
  return response.json() as Promise<T>
}
