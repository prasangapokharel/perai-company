"use client"

import * as React from "react"
import { useRouter } from "next/navigation"

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { registerCompany, loginCompany } from "@/services/auth.service"
import { ensureDefaultApiKey } from "@/services/session-bootstrap.service"
import { saveAuthSession } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { AuthLogo } from "@/components/auth/AuthLogo"

export function SignupForm({
  className,
  ...props
}: React.ComponentProps<"div">) {
  const router = useRouter()
  const [companyName, setCompanyName] = React.useState("")
  const [email, setEmail] = React.useState("")
  const [password, setPassword] = React.useState("")
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(false)

  async function handleSignup(e: React.FormEvent) {
    e.preventDefault()
    setError("")
    setLoading(true)

    try {
      // Validate inputs
      if (!companyName.trim()) throw new Error("Company name is required")
      if (!email.trim()) throw new Error("Email is required")
      if (!password) throw new Error("Password is required (min 8 characters)")
      if (password.length < 8) throw new Error("Password must be at least 8 characters")

      // Register company
      const company = await registerCompany({
        company_name: companyName,
        company_email: email,
        password,
      })

      const login = await loginCompany(email, password)

      let session = {
        companyId: company.id,
        apiKey: "",
        accessToken: login.access_token,
        companyName: company.company_name,
        currency: "USD",
      }
      session = await ensureDefaultApiKey(session)
      saveAuthSession(session)

      router.push("/dashboard")
    } catch (err) {
      if (err instanceof APIError) {
        setError(`Error: ${err.detail}`)
      } else if (err instanceof Error) {
        setError(err.message)
      } else {
        setError("An unexpected error occurred")
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className={cn("flex flex-col gap-6", className)} {...props}>
      <form onSubmit={handleSignup}>
        <FieldGroup>
          <div className="flex flex-col items-center gap-2 text-center">
            <AuthLogo />
            <h1 className="text-xl font-bold">Create your account</h1>
            <FieldDescription>
              Already have an account?{" "}
              <a href="/login" className="font-semibold hover:underline">
                Sign in
              </a>
            </FieldDescription>
          </div>

          {error && (
            <Alert variant="destructive">
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}

          <Field>
            <FieldLabel htmlFor="company-name">Company name *</FieldLabel>
            <Input
              id="company-name"
              value={companyName}
              onChange={(e) => setCompanyName(e.target.value)}
              placeholder="Acme Corp"
              required
              minLength={1}
              disabled={loading}
            />
          </Field>

          <Field>
            <FieldLabel htmlFor="email">Email *</FieldLabel>
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="contact@acme.com"
              required
              minLength={3}
              disabled={loading}
            />
          </Field>

          <Field>
            <FieldLabel htmlFor="password">Password *</FieldLabel>
            <Input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Min 8 characters"
              required
              minLength={8}
              disabled={loading}
            />
          </Field>

          <Field>
            <Button type="submit" disabled={loading} className="w-full">
              {loading ? "Creating account..." : "Sign up"}
            </Button>
          </Field>
        </FieldGroup>
      </form>

      <FieldDescription className="px-6 text-center text-xs">
        By signing up, you agree to our{" "}
        <a href="#" className="underline hover:no-underline">
          Terms of Service
        </a>{" "}
        and{" "}
        <a href="#" className="underline hover:no-underline">
          Privacy Policy
        </a>
        .
      </FieldDescription>
    </div>
  )
}
