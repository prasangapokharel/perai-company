"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Field, FieldLabel, FieldGroup } from "@/components/ui/field"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { loadAuthSession } from "@/features/auth/hooks"
import { getCompany } from "@/services/company.service"
import { APIError } from "@/lib/api-client"

export default function ProfilePage() {
  const router = useRouter()
  const [company, setCompany] = useState<any>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")

  useEffect(() => {
    async function loadProfile() {
      try {
        const session = loadAuthSession()
        if (!session) {
          router.push("/login")
          return
        }

        const companyData = await getCompany(session.companyId, session.apiKey)
        setCompany(companyData)
      } catch (err) {
        if (err instanceof APIError) {
          setError(`Error: ${err.detail}`)
        } else if (err instanceof Error) {
          setError(err.message)
        } else {
          setError("Failed to load profile")
        }
      } finally {
        setLoading(false)
      }
    }

    loadProfile()
  }, [router])

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <p className="text-muted-foreground">Loading profile...</p>
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

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Profile</h1>
        <p className="text-muted-foreground">Manage your company profile</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Company Information</CardTitle>
          <CardDescription>View your company details</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <FieldGroup>
            <Field>
              <FieldLabel>Company Name</FieldLabel>
              <Input value={company.company_name} disabled />
            </Field>

            <Field>
              <FieldLabel>Email</FieldLabel>
              <Input value={company.company_email} type="email" disabled />
            </Field>

            <Field>
              <FieldLabel>Website</FieldLabel>
              <Input value={company.website || "Not set"} disabled />
            </Field>

            <Field>
              <FieldLabel>Model Name</FieldLabel>
              <Input value={company.company_model_name || "Not configured"} disabled />
            </Field>

            <Field>
              <FieldLabel>Created</FieldLabel>
              <Input
                value={new Date(company.created_at).toLocaleDateString()}
                disabled
              />
            </Field>

            <Field>
              <FieldLabel>Last Updated</FieldLabel>
              <Input
                value={new Date(company.updated_at).toLocaleDateString()}
                disabled
              />
            </Field>
          </FieldGroup>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Account Settings</CardTitle>
          <CardDescription>Manage your account</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <Button variant="outline" className="w-full">
            Change Password
          </Button>
          <Button variant="destructive" className="w-full">
            Logout
          </Button>
        </CardContent>
      </Card>
    </div>
  )
}
