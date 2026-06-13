"use client"

import { useEffect, useState, useCallback } from "react"
import { useRouter } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Field, FieldLabel, FieldGroup } from "@/components/ui/field"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { getCompany } from "@/services/company.service"
import { APIError } from "@/lib/api-client"
import { getLogoUrl, uploadLogo } from "@/services/file/uploadProfile"
import { Upload, Loader2 } from "lucide-react"

export default function ProfilePage() {
  const router = useRouter()
  const [company, setCompany] = useState<any>(null)
  const [session, setSession] = useState<{ companyId: number; apiKey: string } | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [uploading, setUploading] = useState(false)
  const [logoKey, setLogoKey] = useState(0)
  const [success, setSuccess] = useState("")

  useEffect(() => {
    async function loadProfile() {
      try {
        const sess = loadAuthSession()
        if (!sess) {
          router.push("/login")
          return
        }
        setSession(sess)
        const companyData = await getCompany(sess.companyId, sessionAuth(sess))
        setCompany(companyData)
      } catch (err) {
        if (err instanceof APIError) setError(`Error: ${err.detail}`)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load profile")
      } finally {
        setLoading(false)
      }
    }

    loadProfile()
  }, [router])

  const handleUpload = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file || !session) return

    if (!file.type.startsWith("image/")) {
      setError("Only image files are accepted")
      return
    }
    if (file.size > 5 * 1024 * 1024) {
      setError("File must be under 5MB")
      return
    }

    setUploading(true)
    setError("")
    setSuccess("")

    try {
      await uploadLogo(session.companyId, file, sessionAuth(session))
      setLogoKey((k) => k + 1)
      setSuccess("Logo uploaded successfully")
    } catch (err) {
      setError(err instanceof Error ? err.message : "Upload failed")
    } finally {
      setUploading(false)
    }
  }, [session])

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

  const initials = company.company_name?.charAt(0).toUpperCase() || "?"

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
      {success && (
        <Alert>
          <AlertDescription className="text-green-700">{success}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Company Logo</CardTitle>
          <CardDescription>Upload your company logo</CardDescription>
        </CardHeader>
        <CardContent className="flex items-center gap-6">
          <Avatar className="h-20 w-20">
            <AvatarImage key={logoKey} src={getLogoUrl(company.id)} alt={company.company_name} />
            <AvatarFallback className="text-lg">{initials}</AvatarFallback>
          </Avatar>
          <div className="space-y-2">
            <Button variant="outline" className="relative" disabled={uploading}>
              {uploading ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <Upload className="mr-2 h-4 w-4" />
              )}
              {uploading ? "Uploading..." : "Choose Image"}
              <input
                type="file"
                accept="image/*"
                onChange={handleUpload}
                disabled={uploading}
                className="absolute inset-0 opacity-0 cursor-pointer"
              />
            </Button>
            <p className="text-xs text-muted-foreground">PNG, JPG, WebP up to 5MB</p>
          </div>
        </CardContent>
      </Card>

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
              <Input value={new Date(company.created_at).toLocaleDateString()} disabled />
            </Field>
            <Field>
              <FieldLabel>Last Updated</FieldLabel>
              <Input value={new Date(company.updated_at).toLocaleDateString()} disabled />
            </Field>
          </FieldGroup>
        </CardContent>
      </Card>
    </div>
  )
}
