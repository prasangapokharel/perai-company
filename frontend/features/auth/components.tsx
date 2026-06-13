"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { createCompany } from "@/services/company.service"
import { clearAuthSession, saveAuthSession, useAuthSession } from "@/features/auth/hooks"

export function AuthCard({ title }: { title: string }) {
  return (
    <Card className="mx-auto w-full max-w-sm">
      <CardHeader>{title}</CardHeader>
      <CardContent />
    </Card>
  )
}

export function AuthFlowCard({ mode }: { mode: "login" | "register" }) {
  const { session, clear } = useAuthSession()
  const [companyName, setCompanyName] = React.useState("")
  const [companyEmail, setCompanyEmail] = React.useState("")
  const [password, setPassword] = React.useState("")
  const [message, setMessage] = React.useState("")

  async function handleRegister() {
    const company = await createCompany({ company_name: companyName, company_email: companyEmail, password })
    saveAuthSession({ companyId: company.id, apiKey: "", companyName: company.company_name })
    setMessage("Company created. Create an API key from the API page.")
  }

  return (
    <Card className="mx-auto w-full max-w-lg">
      <CardHeader>{mode === "register" ? "Create company" : "Use saved session"}</CardHeader>
      <CardContent className="space-y-3">
        {mode === "register" ? (
          <>
            <Input value={companyName} onChange={(e) => setCompanyName(e.target.value)} placeholder="Company name" />
            <Input value={companyEmail} onChange={(e) => setCompanyEmail(e.target.value)} placeholder="Company email" />
            <Input value={password} onChange={(e) => setPassword(e.target.value)} type="password" placeholder="Password" />
            <Button onClick={handleRegister}>Create company session</Button>
          </>
        ) : (
          <>
            <p className="text-sm text-muted-foreground">
              {session ? `Connected to ${session.companyName ?? "company"}` : "No session saved"}
            </p>
            <Button onClick={() => { clearAuthSession(); clear(); }}>Clear session</Button>
          </>
        )}
        {message ? <p className="text-sm text-muted-foreground">{message}</p> : null}
      </CardContent>
    </Card>
  )
}
