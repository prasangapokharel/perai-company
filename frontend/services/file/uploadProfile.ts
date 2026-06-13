import type { ApiAuth } from "@/lib/api-auth"
import { buildAuthHeaders } from "@/lib/api-auth"
import { API_BASE_URL } from "@/lib/api-client"

export { API_BASE_URL }

export function getLogoUrl(companyId: number) {
  return `${API_BASE_URL}/files/companies/${companyId}/logo`
}

export async function uploadLogo(companyId: number, file: File, auth?: ApiAuth | string) {
  const formData = new FormData()
  formData.append("file", file)

  const headers =
    typeof auth === "string"
      ? auth
        ? { "X-API-Key": auth }
        : {}
      : buildAuthHeaders(auth)

  const response = await fetch(`${API_BASE_URL}/files/companies/${companyId}/logo`, {
    method: "POST",
    headers,
    body: formData,
  })

  if (!response.ok) {
    let detail = `Upload failed: ${response.status}`
    try {
      const body = await response.json()
      detail = body.detail || detail
    } catch {}
    throw new Error(detail)
  }

  return response.json() as Promise<{ company_id: number; logo_path: string; message: string }>
}
