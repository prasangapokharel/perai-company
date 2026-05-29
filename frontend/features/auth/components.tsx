"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { createCompany } from "@/services/company.service"
import { createAPIKey } from "@/services/api-key.service"
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
  const [apiKeyName, setApiKeyName] = React.useState("production")
  const [content, setContent] = React.useState("# Company Knowledge Base\n\n## Overview\n")
  const [message, setMessage] = React.useState("")

  async function handleRegister() {
    const company = await createCompany({ company_name: companyName, company_email: companyEmail, password })
    const expiry = new Date()
    expiry.setDate(expiry.getDate() + 90)
    const key = await createAPIKey(company.id, { name: apiKeyName, expiry_date: expiry.toISOString() })
    saveAuthSession({ companyId: company.id, apiKey: key.key, companyName: company.company_name })
    setMessage("Session saved")
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
            <Input value={apiKeyName} onChange={(e) => setApiKeyName(e.target.value)} placeholder="API key name" />
            <Textarea value={content} onChange={(e) => setContent(e.target.value)} rows={8} />
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
