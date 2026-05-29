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
import { loginCompany, createCompanyAPIKey } from "@/services/auth.service"
import { saveAuthSession } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { HugeiconsIcon } from "@hugeicons/react"
import { LayoutBottomIcon } from "@hugeicons/core-free-icons"

export function LoginForm({
  className,
  ...props
}: React.ComponentProps<"div">) {
  const router = useRouter()
  const [email, setEmail] = React.useState("")
  const [password, setPassword] = React.useState("")
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(false)

  async function handleLogin(e: React.FormEvent) {
    e.preventDefault()
    setError("")
    setLoading(true)

    try {
      // Validate inputs
      if (!email.trim()) throw new Error("Email is required")
      if (!password) throw new Error("Password is required")

      // Login company
      const response = await loginCompany(email, password)
      const company = response.company

      // Create API key
      const expiry = new Date()
      expiry.setDate(expiry.getDate() + 90)
      const apiKey = await createCompanyAPIKey(
        company.id,
        "default",
        expiry.toISOString()
      )

      // Save session
      saveAuthSession({
        companyId: company.id,
        apiKey: apiKey.key,
        companyName: company.company_name,
      })

      // Redirect to dashboard
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
      <form onSubmit={handleLogin}>
        <FieldGroup>
          <div className="flex flex-col items-center gap-2 text-center">
            <a
              href="#"
              className="flex flex-col items-center gap-2 font-medium"
            >
              <div className="flex size-8 items-center justify-center rounded-md">
                <HugeiconsIcon icon={LayoutBottomIcon} strokeWidth={2} className="size-6" />
              </div>
              <span className="sr-only">Perai</span>
            </a>
            <h1 className="text-xl font-bold">Welcome back</h1>
            <FieldDescription>
              Don&apos;t have an account?{" "}
              <a href="/register" className="font-semibold hover:underline">
                Sign up
              </a>
            </FieldDescription>
          </div>

          {error && (
            <Alert variant="destructive">
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}

          <Field>
            <FieldLabel htmlFor="email">Email *</FieldLabel>
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="contact@acme.com"
              required
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
              placeholder="Enter your password"
              required
              disabled={loading}
            />
          </Field>

          <Field>
            <Button type="submit" disabled={loading} className="w-full">
              {loading ? "Signing in..." : "Sign in"}
            </Button>
          </Field>
        </FieldGroup>
      </form>

      <FieldDescription className="px-6 text-center text-xs">
        Your credentials are secure and only used for authentication.
      </FieldDescription>
    </div>
  )
}
