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

export async function apiClient<T>(path: string, init: RequestInit = {}, apiKey?: string) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: {
      "Content-Type": "application/json",
      ...(apiKey ? { "X-API-Key": apiKey } : {}),
      ...init.headers,
    },
  })

  if (!response.ok) {
    let detail = `HTTP ${response.status}`
    try {
      const body = await response.json()
      detail = body.detail || JSON.stringify(body)
    } catch {
      // Response is not JSON, use status text
      detail = response.statusText || detail
    }
    throw new APIError(response.status, detail)
  }

  return response.json() as Promise<T>
}
