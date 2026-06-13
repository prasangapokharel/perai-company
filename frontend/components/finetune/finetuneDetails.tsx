"use client"

import { useEffect, useState } from "react"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { getCompanyFinetune } from "@/services/company.service"
import type { CompanyFinetune } from "@/services/company.service"

import type { ApiAuth } from "@/lib/api-auth"

interface FinetuneDetailsProps {
  companyId: number
  apiKey: ApiAuth | string
}

export function FinetuneDetails({ companyId, apiKey }: FinetuneDetailsProps) {
  const [data, setData] = useState<CompanyFinetune | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function fetch() {
      try {
        const result = await getCompanyFinetune(companyId, apiKey)
        setData(result)
      } catch (e) {
        setError(e instanceof Error ? e.message : "Failed to load finetune")
      } finally {
        setLoading(false)
      }
    }
    fetch()
  }, [companyId, apiKey])

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Finetune Details</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">Loading...</p>
        </CardContent>
      </Card>
    )
  }

  if (error) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Finetune Details</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-red-500">{error}</p>
        </CardContent>
      </Card>
    )
  }

  if (!data) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Finetune Details</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">No finetune configuration found</p>
        </CardContent>
      </Card>
    )
  }

  const rows = [
    { label: "ID", value: data.id },
    { label: "Company ID", value: data.company_id },
    { label: "Model Name", value: data.company_model_name, badge: true },
    { label: "RAG Path", value: data.rag_company_path },
    { label: "Created At", value: new Date(data.created_at!).toLocaleString() },
    { label: "Updated At", value: new Date(data.updated_at!).toLocaleString() },
  ]

  return (
    <Card>
      <CardHeader>
        <CardTitle>Finetune Details</CardTitle>
        <CardDescription>Your company&apos;s fine-tuned model configuration</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <table className="w-full text-sm">
          <tbody>
            {rows.map((row) => (
              <tr key={row.label} className="border-b last:border-0">
                <td className="py-3 pr-4 text-muted-foreground w-36">{row.label}</td>
                <td className="py-3">
                  {row.badge ? (
                    <Badge variant="secondary">{row.value}</Badge>
                  ) : (
                    <span className="font-medium">{row.value}</span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {data.content && (
          <div>
            <p className="text-sm text-muted-foreground mb-2">Knowledge Base Content</p>
            <pre className="text-xs bg-muted p-3 rounded-md max-h-48 overflow-y-auto whitespace-pre-wrap break-words">
              {data.content}
            </pre>
          </div>
        )}
      </CardContent>
    </Card>
  )
}
