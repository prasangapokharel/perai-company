"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { getCompanyDashboard } from "@/services/company/dashboard"
import { APIError } from "@/lib/api-client"
import { Key, Zap, BarChart3, Clock, DollarSign } from "lucide-react"

export default function DashboardPage() {
  const router = useRouter()
  const [dashboard, setDashboard] = useState<any>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [session, setSession] = useState<any>(null)

  useEffect(() => {
    async function loadDashboard() {
      try {
        const sess = loadAuthSession()
        if (!sess) {
          router.push("/login")
          return
        }
        setSession(sess)

        const dashboardData = await getCompanyDashboard(sess.companyId, sessionAuth(sess))

        setDashboard(dashboardData)
      } catch (err) {
        if (err instanceof APIError) {
          setError(`Error: ${err.detail}`)
        } else if (err instanceof Error) {
          setError(err.message)
        } else {
          setError("Failed to load dashboard")
        }
      } finally {
        setLoading(false)
      }
    }

    loadDashboard()
  }, [router])

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <p className="text-muted-foreground">Loading dashboard...</p>
      </div>
    )
  }

  if (!dashboard && error) {
    return (
      <div className="flex items-center justify-center h-96">
        <p className="text-destructive">{error}</p>
      </div>
    )
  }

  if (!dashboard) {
    return (
      <div className="flex items-center justify-center h-96">
        <p className="text-muted-foreground">No dashboard data found</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground">
          Welcome back, {dashboard.company_name}
        </p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* Top Stats */}
      <div className="grid gap-4 md:grid-cols-5">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">API Keys</CardTitle>
            <Key className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard.total_api_keys}</div>
            <p className="text-xs text-muted-foreground">
              {dashboard.active_api_keys} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Tokens</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard.total_tokens_all_time.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">All time tokens consumed</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Credit Balance</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard.current_balance ?? "0"}</div>
            <p className="text-xs text-muted-foreground">Available credits for chat</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Spent</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard.total_balance_deducted_all_time}</div>
            <p className="text-xs text-muted-foreground">All time balance deducted</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Last Request</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-sm font-semibold truncate">
              {dashboard.last_request_at
                ? new Date(dashboard.last_request_at).toLocaleString()
                : "No requests yet"}
            </div>
            <p className="text-xs text-muted-foreground">Last API call timestamp</p>
          </CardContent>
        </Card>
      </div>

      {/* Finetune Model */}
      <Card>
        <CardHeader>
          <CardTitle>Model Status</CardTitle>
          <CardDescription>Your fine-tuned model</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center gap-2">
            <Badge variant={dashboard.model_name ? "default" : "secondary"}>
              {dashboard.model_name ? "Active" : "Inactive"}
            </Badge>
            <span className="text-sm text-muted-foreground">
              {dashboard.model_name || "No model configured"}
            </span>
          </div>
        </CardContent>
      </Card>

      {/* Usage Metrics */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Today</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Requests</span>
              <span className="font-semibold">{dashboard.usage_metrics.today.total_requests}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Tokens</span>
              <span className="font-semibold">{dashboard.usage_metrics.today.total_tokens_consumed}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Balance</span>
              <span className="font-semibold">{dashboard.usage_metrics.today.total_balance_deducted}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">This Week</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Requests</span>
              <span className="font-semibold">{dashboard.usage_metrics.weekly.total_requests}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Tokens</span>
              <span className="font-semibold">{dashboard.usage_metrics.weekly.total_tokens_consumed}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Balance</span>
              <span className="font-semibold">{dashboard.usage_metrics.weekly.total_balance_deducted}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">This Month</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Requests</span>
              <span className="font-semibold">{dashboard.usage_metrics.monthly.total_requests}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Tokens</span>
              <span className="font-semibold">{dashboard.usage_metrics.monthly.total_tokens_consumed}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-muted-foreground">Balance</span>
              <span className="font-semibold">{dashboard.usage_metrics.monthly.total_balance_deducted}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Credit Deducted */}
      <Card>
        <CardHeader>
          <CardTitle>Credit Deducted</CardTitle>
          <CardDescription>Balance deducted per period</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-3">
            <div>
              <p className="text-sm text-muted-foreground">Today</p>
              <p className="text-xl font-bold">{dashboard.credit_deducted.today}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">This Week</p>
              <p className="text-xl font-bold">{dashboard.credit_deducted.weekly}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">This Month</p>
              <p className="text-xl font-bold">{dashboard.credit_deducted.monthly}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* API Keys Summary */}
      {dashboard.api_keys.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>API Keys Summary</CardTitle>
            <CardDescription>Your registered API keys</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {dashboard.api_keys.slice(0, 10).map((key: any) => (
                <div key={key.id} className="flex items-center justify-between p-3 border rounded">
                  <div className="space-y-1">
                    <p className="font-medium text-sm">{key.name}</p>
                    <p className="text-xs text-muted-foreground">{key.key_preview}</p>
                    <div className="flex gap-3 text-xs text-muted-foreground">
                      <span>Created: {new Date(key.created_at).toLocaleDateString()}</span>
                      <span>Expires: {new Date(key.expiry_date).toLocaleDateString()}</span>
                    </div>
                  </div>
                  <Badge
                    variant={
                      key.status === "active"
                        ? "default"
                        : key.status === "revoked"
                          ? "destructive"
                          : "secondary"
                    }
                  >
                    {key.status}
                  </Badge>
                </div>
              ))}
              {dashboard.api_keys.length > 10 && (
                <p className="text-xs text-muted-foreground pt-2">
                  +{dashboard.api_keys.length - 10} more keys
                </p>
              )}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
