"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { queryChat } from "@/services/chat.service"
import { useAuthSession } from "@/features/auth/hooks"

export function ChatPanel({ companyId, apiKey }: { companyId?: number; apiKey?: string }) {
  const { session } = useAuthSession()
  const resolvedCompanyId = companyId ?? session?.companyId
  const resolvedApiKey = apiKey ?? session?.apiKey
  const [prompt, setPrompt] = React.useState("")
  const [response, setResponse] = React.useState("")
  const [status, setStatus] = React.useState("")

  async function handleSend() {
    if (!resolvedCompanyId || !resolvedApiKey || !prompt.trim()) return
    setStatus("Sending...")
    try {
      const data = await queryChat(resolvedCompanyId, { prompt }, resolvedApiKey)
      setResponse(data.response)
      setStatus("Done")
    } catch {
      setStatus("Chat failed")
    }
  }

  return (
    <Card>
      <CardHeader>Chat</CardHeader>
      <CardContent className="space-y-3">
        <Input value={prompt} onChange={(e) => setPrompt(e.target.value)} placeholder="Ask something" />
        <Button onClick={handleSend}>Send</Button>
        {status ? <p className="text-sm text-muted-foreground">{status}</p> : null}
        {response ? <p className="text-sm text-muted-foreground">{response}</p> : null}
      </CardContent>
    </Card>
  )
}
