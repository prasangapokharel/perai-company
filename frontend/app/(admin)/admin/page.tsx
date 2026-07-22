"use client"

import * as React from "react"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { loadAuthSession } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { getAdminOverview, type AdminOverview } from "@/services/admin.service"

function Stat({ label, value, hint }: { label: string; value: string; hint?: string }) {
  return (
    <Card>
      <CardHeader className="pb-2">
        <CardDescription>{label}</CardDescription>
        <CardTitle className="text-3xl">{value}</CardTitle>
      </CardHeader>
      {hint && (
        <CardContent>
          <p className="text-xs text-muted-foreground">{hint}</p>
        </CardContent>
      )}
    </Card>
  )
}

export default function AdminOverviewPage() {
  const [data, setData] = React.useState<AdminOverview | null>(null)
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(true)

  React.useEffect(() => {
    const session = loadAuthSession()
    if (!session) return
    getAdminOverview(session)
      .then(setData)
      .catch((err) => {
        if (err instanceof APIError) setError(err.detail)
        else setError("Failed to load overview")
      })
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="flex h-96 items-center justify-center">
        <p className="text-muted-foreground">Loading overview...</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Overview</h1>
        <p className="text-muted-foreground">Platform-wide administration</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {data && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Stat label="Companies" value={String(data.total_companies)} hint={`${data.admin_companies} admin`} />
            <Stat label="Total balance" value={`$${Number(data.total_balance).toFixed(2)}`} />
            <Stat label="Total top-ups" value={`$${Number(data.total_topups).toFixed(2)}`} />
            <Stat label="Total deducted" value={`$${Number(data.total_deducted).toFixed(2)}`} />
          </div>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Stat label="Tickets" value={String(data.total_tickets)} hint={`${data.open_tickets} open`} />
            <Stat label="API keys" value={String(data.total_api_keys)} hint={`${data.active_api_keys} active`} />
            <Stat
              label="Khalti payments"
              value={String(data.total_khalti_payments)}
              hint={`${data.completed_khalti_payments} completed`}
            />
          </div>
        </>
      )}
    </div>
  )
}
