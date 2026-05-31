"use client"

import * as React from "react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { useAuthSession } from "@/features/auth/hooks"
import { getCompany } from "@/services/company.service"
import { APIError } from "@/lib/api-client"
import { useRouter } from "next/navigation"

export function ModelsPanel() {
  const router = useRouter()
  const { session } = useAuthSession()
  const [company, setCompany] = React.useState<any>(null)
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(true)

  React.useEffect(() => {
    async function load() {
      try {
        if (!session) return
        const data = await getCompany(session.companyId, session.apiKey)
        setCompany(data)
      } catch (err) {
        if (err instanceof APIError) setError(err.detail)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load models")
      } finally {
        setLoading(false)
      }
    }

    load()
  }, [session])

  if (loading) return <p className="text-sm text-muted-foreground">Loading models...</p>

  if (!company) {
    return (
      <Empty>
        <EmptyContent>
          <EmptyMedia variant="icon" />
          <EmptyHeader>
            <EmptyTitle>No model configured</EmptyTitle>
            <EmptyDescription>{error || "Upload finetune data to create a model."}</EmptyDescription>
          </EmptyHeader>
        </EmptyContent>
      </Empty>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Models</CardTitle>
        <CardDescription>Company model name and finetune status</CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        <div className="flex items-center justify-between gap-3">
          <div>
            <p className="text-sm text-muted-foreground">Model name</p>
            <p className="font-medium">{company.company_model_name || "Not configured"}</p>
          </div>
          <Badge variant={company.company_model_name ? "default" : "secondary"}>
            {company.company_model_name ? "Active" : "Inactive"}
          </Badge>
        </div>
        <Button variant="outline" onClick={() => router.push("/finetune") }>
          Go to finetune
        </Button>
      </CardContent>
    </Card>
  )
}
