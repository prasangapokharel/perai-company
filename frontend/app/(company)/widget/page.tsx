"use client"

import { useCallback, useEffect, useState } from "react"
import Link from "next/link"

import { WidgetEmbedPanel } from "@/components/widget/WidgetEmbedPanel"
import { loadAuthSession, type AuthSession } from "@/features/auth/hooks"
import {
  createEmbedApiKey,
  ensureDefaultApiKey,
  saveEmbedApiKey,
} from "@/services/session-bootstrap.service"
import { buttonVariants } from "@/components/ui/button"
import { APIError } from "@/lib/api-client"

export default function WidgetPage() {
  const [session, setSession] = useState<AuthSession | null>(null)
  const [loading, setLoading] = useState(true)
  const [creatingKey, setCreatingKey] = useState(false)
  const [error, setError] = useState("")

  useEffect(() => {
    async function load() {
      const sess = loadAuthSession()
      if (!sess) {
        setLoading(false)
        return
      }
      try {
        const ready = await ensureDefaultApiKey(sess)
        setSession(ready)
      } catch (err) {
        if (err instanceof APIError) setError(err.detail)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load widget settings")
        setSession(sess)
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [])

  const handleSaveApiKey = useCallback(
    (apiKey: string) => {
      if (!session) return
      setSession(saveEmbedApiKey(session, apiKey))
      setError("")
    },
    [session],
  )

  const handleCreateEmbedKey = useCallback(async () => {
    if (!session) return
    setCreatingKey(true)
    setError("")
    try {
      setSession(await createEmbedApiKey(session))
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else if (err instanceof Error) setError(err.message)
      else setError("Failed to create embed API key")
    } finally {
      setCreatingKey(false)
    }
  }, [session])

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

      <WidgetEmbedPanel
        companyId={session.companyId}
        apiKey={session.apiKey || ""}
        companyName={session.companyName}
        error={error}
        creatingKey={creatingKey}
        onSaveApiKey={handleSaveApiKey}
        onCreateEmbedKey={handleCreateEmbedKey}
      />
    </div>
  )
}
