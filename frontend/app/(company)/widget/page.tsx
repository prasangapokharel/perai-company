"use client"

import { useEffect, useState } from "react"
import Link from "next/link"

import { WidgetEmbedPanel } from "@/components/widget/WidgetEmbedPanel"
import { loadAuthSession, saveAuthSession } from "@/features/auth/hooks"
import { ensureDefaultApiKey } from "@/services/session-bootstrap.service"
import { buttonVariants } from "@/components/ui/button"
import { Alert, AlertDescription } from "@/components/ui/alert"

export default function WidgetPage() {
  const [session, setSession] = useState<{
    companyId: number
    apiKey: string
    accessToken?: string
    companyName?: string
  } | null>(null)
  const [loading, setLoading] = useState(true)
  const [needsNewKey, setNeedsNewKey] = useState(false)

  useEffect(() => {
    async function load() {
      let sess = loadAuthSession()
      if (!sess) {
        setLoading(false)
        return
      }
      sess = await ensureDefaultApiKey(sess)
      saveAuthSession(sess)
      setSession(sess)
      setNeedsNewKey(!sess.apiKey)
      setLoading(false)
    }
    load()
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
          <h1 className="text-3xl font-bold tracking-tight">Chat Widget</h1>
          <p className="text-muted-foreground">
            Copy the embed script and paste it on any website. Customers chat using your API key.
          </p>
        </div>
        <Link className={buttonVariants({ variant: "outline" })} href="/integration">
          View API integration
        </Link>
      </div>

      {needsNewKey && (
        <Alert>
          <AlertDescription>
            Create an API key on the{" "}
            <Link href="/api" className="font-medium underline">
              API page
            </Link>{" "}
            to generate embed code. The full key is saved in this browser when you create it.
          </AlertDescription>
        </Alert>
      )}

      {session.apiKey ? (
        <WidgetEmbedPanel
          companyId={session.companyId}
          apiKey={session.apiKey}
          companyName={session.companyName}
        />
      ) : null}
    </div>
  )
}
