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
import { loadAuthSession } from "@/features/auth/hooks"
import { getCompany } from "@/services/company.service"
import { listAPIKeys } from "@/services/api-key.service"
import { APIError } from "@/lib/api-client"
import { Key, MessageSquare, Zap, Activity } from "lucide-react"

export default function DashboardPage() {
  const router = useRouter()
  const [company, setCompany] = useState<any>(null)
  const [apiKeys, setApiKeys] = useState<any[]>([])
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

        const [companyData, keys] = await Promise.all([
          getCompany(sess.companyId, sess.apiKey),
          listAPIKeys(sess.companyId, sess.apiKey),
        ])

        setCompany(companyData)
        setApiKeys(keys)
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

  if (!company) {
    return (
      <div className="flex items-center justify-center h-96">
        <p className="text-muted-foreground">No company data found</p>
      </div>
    )
  }

  const activeKeys = apiKeys.filter((k) => k.status === "active").length

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground">
          Welcome back, {company.company_name}
        </p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* Stats Grid */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">API Keys</CardTitle>
            <Key className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{apiKeys.length}</div>
            <p className="text-xs text-muted-foreground">
              {activeKeys} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Finetune Status</CardTitle>
            <Zap className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {company.company_model_name ? "Active" : "Inactive"}
            </div>
            <p className="text-xs text-muted-foreground">
              {company.company_model_name || "Not configured"}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Model Name</CardTitle>
            <Badge variant="secondary">Model</Badge>
          </CardHeader>
          <CardContent>
            <div className="text-sm font-semibold truncate">
              {company.company_model_name || "Not configured"}
            </div>
            <p className="text-xs text-muted-foreground">Current company model</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Email</CardTitle>
            <MessageSquare className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-sm font-semibold truncate">{company.company_email}</div>
            <p className="text-xs text-muted-foreground">
              Company email
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Status</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">Active</div>
            <p className="text-xs text-muted-foreground">
              Account active
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Company Info */}
      <Card>
        <CardHeader>
          <CardTitle>Company Information</CardTitle>
          <CardDescription>Your company details</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div>
              <p className="text-sm text-muted-foreground">Company Name</p>
              <p className="font-semibold">{company.company_name}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Email</p>
              <p className="font-semibold">{company.company_email}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Website</p>
              <p className="font-semibold">{company.website || "Not set"}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Model Name</p>
              <p className="font-semibold">
                {company.company_model_name || "Not configured"}
              </p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Created</p>
              <p className="font-semibold">
                {new Date(company.created_at).toLocaleDateString()}
              </p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Last Updated</p>
              <p className="font-semibold">
                {new Date(company.updated_at).toLocaleDateString()}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* API Keys Summary */}
      {apiKeys.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>API Keys Summary</CardTitle>
            <CardDescription>Your active API keys</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {apiKeys.slice(0, 5).map((key) => (
                <div key={key.id} className="flex items-center justify-between p-2 border rounded">
                  <div>
                    <p className="font-medium text-sm">{key.name}</p>
                    <p className="text-xs text-muted-foreground">{key.key_preview}</p>
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
              {apiKeys.length > 5 && (
                <p className="text-xs text-muted-foreground pt-2">
                  +{apiKeys.length - 5} more keys
                </p>
              )}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
