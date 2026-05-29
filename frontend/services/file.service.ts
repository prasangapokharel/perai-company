import { apiClient } from "@/lib/api-client"

export type CompanyFile = {
  filename: string
  file_size: number
  mime_type: string
  storage_path: string
  url?: string
  uploaded_at?: string
}

export function uploadCompanyLogo(companyId: number, file: File, apiKey?: string) {
  const formData = new FormData()
  formData.append("file", file)

  return apiClient<{ company_id: number; logo_path: string; message: string }>(
    `/files/companies/${companyId}/logo`,
    { method: "POST", body: formData },
    apiKey
  )
}

export function downloadCompanyLogo(companyId: number) {
  return `${process.env.API_URL ?? "http://localhost:8000/api/v1"}/files/companies/${companyId}/logo`
}

export function uploadCompanyContent(companyId: number, file: File, apiKey?: string) {
  const formData = new FormData()
  formData.append("file", file)

  return apiClient<{ company_id: number; file_path: string; filename: string; message: string }>(
    `/files/companies/${companyId}/content`,
    { method: "POST", body: formData },
    apiKey
  )
}

export function getCompanyLogoUrl(companyId: number) {
  return `${process.env.API_URL ?? "http://localhost:8000/api/v1"}/files/companies/${companyId}/logo`
}

export function listCompanyFiles(companyId: number, apiKey?: string) {
  return apiClient<{ company_id: number; files: CompanyFile[] }>(
    `/files/companies/${companyId}/list`,
    {},
    apiKey
  )
}
