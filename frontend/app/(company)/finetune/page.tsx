"use client"

import { useEffect, useState, useCallback } from "react"
import { useRouter } from "next/navigation"
import Link from "next/link"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { FinetuneDetails, FinetuneUpload } from "@/components/finetune"
import { buttonVariants } from "@/components/ui/button"

export default function FinetunePage() {
  const router = useRouter()
  const [session, setSession] = useState<{ companyId: number; apiKey: string; accessToken?: string } | null>(null)
  const [loading, setLoading] = useState(true)
  const [refreshKey, setRefreshKey] = useState(0)

  useEffect(() => {
    const sess = loadAuthSession()
    if (!sess?.accessToken && !sess?.apiKey) {
      router.push("/login")
      return
    }
    setSession(sess)
    setLoading(false)
  }, [router])

  const handleUploadSuccess = useCallback(() => {
    setRefreshKey((k) => k + 1)
  }, [])

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <p className="text-muted-foreground">Loading...</p>
      </div>
    )
  }

  if (!session) return null

  return (
    <div className="space-y-8">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Knowledge Base</h1>
          <p className="text-muted-foreground">
            Upload structured JSONL data. Retrieval uses vectorless BM25 on disk — no embedding load on the server.
          </p>
        </div>
        <Link className={buttonVariants({ variant: "outline" })} href="/widget">
          Get embed code
        </Link>
      </div>

      <FinetuneDetails key={refreshKey} companyId={session.companyId} apiKey={sessionAuth(session)} />

      <div className="space-y-4">
        <h2 className="text-lg font-semibold">Upload JSONL</h2>
        <FinetuneUpload
          companyId={session.companyId}
          apiKey={sessionAuth(session)}
          onSuccess={handleUploadSuccess}
        />
      </div>
    </div>
  )
}
