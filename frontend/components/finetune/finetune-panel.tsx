"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Textarea } from "@/components/ui/textarea"
import { getCompanyFinetune, upsertCompanyFinetune } from "@/services/company.service"
import { useAuthSession } from "@/features/auth/hooks"
import { cn } from "@/lib/utils"

export function FinetunePanel({ companyId, apiKey }: { companyId?: number; apiKey?: string }) {
  const { session } = useAuthSession()
  const resolvedCompanyId = companyId ?? session?.companyId
  const resolvedApiKey = apiKey ?? session?.apiKey
  const [content, setContent] = React.useState("")
  const [status, setStatus] = React.useState("")
  const [dragActive, setDragActive] = React.useState(false)
  const fileInputRef = React.useRef<HTMLInputElement>(null)

  React.useEffect(() => {
    if (!resolvedCompanyId) return
    getCompanyFinetune(resolvedCompanyId, resolvedApiKey)
      .then((data) => setContent(data.content ?? ""))
      .catch(() => setStatus("No finetune content yet"))
  }, [resolvedCompanyId, resolvedApiKey])

  function handleFile(file: File | null) {
    if (!file) return
    const reader = new FileReader()
    reader.onload = () => {
      setContent(String(reader.result ?? ""))
      setStatus(`Loaded ${file.name}`)
    }
    reader.readAsText(file)
  }

  function handleDrop(event: React.DragEvent<HTMLDivElement>) {
    event.preventDefault()
    setDragActive(false)
    handleFile(event.dataTransfer.files?.[0] ?? null)
  }

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
        <div
          className={cn(
            "rounded-2xl border border-dashed p-6 text-center transition-colors",
            dragActive && "border-primary bg-primary/5"
          )}
          onDragEnter={(e) => { e.preventDefault(); setDragActive(true) }}
          onDragOver={(e) => { e.preventDefault(); setDragActive(true) }}
          onDragLeave={() => setDragActive(false)}
          onDrop={handleDrop}
        >
          <input
            ref={fileInputRef}
            type="file"
            accept=".txt,.md,.markdown"
            className="hidden"
            onChange={(e) => handleFile(e.target.files?.[0] ?? null)}
          />
          <p className="text-sm font-medium">Drag & drop a knowledge file</p>
          <p className="text-xs text-muted-foreground">or paste/edit content below</p>
          <div className="mt-3 flex flex-wrap justify-center gap-2">
            <Button type="button" variant="outline" onClick={() => fileInputRef.current?.click()}>
              Choose file
            </Button>
            <Button type="button" variant="ghost" onClick={() => { setContent(""); setStatus("Cleared") }}>
              Clear
            </Button>
          </div>
        </div>
        <Textarea value={content} onChange={(e) => setContent(e.target.value)} rows={14} placeholder="Markdown knowledge base" />
        <div className="flex items-center gap-3">
          <Button onClick={handleSave}>Save</Button>
          <span className="text-sm text-muted-foreground">{status}</span>
        </div>
      </CardContent>
    </Card>
  )
}
