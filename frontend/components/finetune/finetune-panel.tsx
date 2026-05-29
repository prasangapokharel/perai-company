"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Textarea } from "@/components/ui/textarea"
import { getCompanyFinetune, upsertCompanyFinetune } from "@/services/company.service"
import { useAuthSession } from "@/features/auth/hooks"

export function FinetunePanel({ companyId, apiKey }: { companyId?: number; apiKey?: string }) {
  const { session } = useAuthSession()
  const resolvedCompanyId = companyId ?? session?.companyId
  const resolvedApiKey = apiKey ?? session?.apiKey
  const [content, setContent] = React.useState("")
  const [status, setStatus] = React.useState("")

  React.useEffect(() => {
    if (!resolvedCompanyId) return
    getCompanyFinetune(resolvedCompanyId, resolvedApiKey)
      .then((data) => setContent((data as { content?: string }).content ?? ""))
      .catch(() => setStatus("No finetune content yet"))
  }, [resolvedCompanyId, resolvedApiKey])

  async function handleSave() {
    if (!resolvedCompanyId) return
    setStatus("Saving...")
    try {
      await upsertCompanyFinetune(resolvedCompanyId, content, resolvedApiKey)
      setStatus("Saved")
    } catch {
      setStatus("Save failed")
    }
  }

  return (
    <Card>
      <CardHeader>Finetune</CardHeader>
      <CardContent className="space-y-3">
        <Textarea value={content} onChange={(e) => setContent(e.target.value)} rows={14} placeholder="Markdown knowledge base" />
        <div className="flex items-center gap-3">
          <Button onClick={handleSave}>Save</Button>
          <span className="text-sm text-muted-foreground">{status}</span>
        </div>
      </CardContent>
    </Card>
  )
}
