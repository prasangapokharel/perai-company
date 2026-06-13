"use client"

import { useEffect, useState } from "react"
import Link from "next/link"

import { IntegrationPanel } from "@/components/integration/IntegrationPanel"
import { loadAuthSession } from "@/features/auth/hooks"
import { buttonVariants } from "@/components/ui/button"
import { Alert, AlertDescription } from "@/components/ui/alert"

export default function IntegrationPage() {
  const [session, setSession] = useState<{ companyId: number; apiKey: string } | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const sess = loadAuthSession()
    if (sess) setSession(sess)
    setLoading(false)
  }, [])

  if (loading) {
    return (
      <div className="flex h-96 items-center justify-center">
        <p className="text-muted-foreground">Loading...</p>
      </div>
    )
  }

  if (!session) return null

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Integration</h1>
          <p className="text-muted-foreground">
            Python, TypeScript, and cURL examples for chat, streaming, and JSONL knowledge upload.
          </p>
        </div>
        <Link className={buttonVariants({ variant: "outline" })} href="/widget">
          Get embed code
        </Link>
      </div>

      {!session.apiKey && (
        <Alert>
          <AlertDescription>
            Create an API key on the{" "}
            <Link href="/api" className="font-medium underline">
              API page
            </Link>{" "}
            to see live integration snippets with your key.
          </AlertDescription>
        </Alert>
      )}

      <IntegrationPanel companyId={session.companyId} apiKey={session.apiKey || "YOUR_API_KEY"} />
    </div>
  )
}
