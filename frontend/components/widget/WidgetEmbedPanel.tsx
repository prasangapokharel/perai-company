"use client"

import * as React from "react"
import { Copy, Check, Eye, EyeOff } from "lucide-react"

import { Button, buttonVariants } from "@/components/ui/button"
import Link from "next/link"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { buildEmbedScript } from "@/lib/embed"
import { API_BASE_URL } from "@/lib/api-client"
import { getCompanyLogoUrl } from "@/services/file.service"

type Props = {
  companyId: number
  apiKey: string
  companyName?: string
  error?: string
  creatingKey?: boolean
  onSaveApiKey: (apiKey: string) => void
  onCreateEmbedKey: () => void
}

export function WidgetEmbedPanel({
  companyId,
  apiKey,
  companyName,
  error,
  creatingKey = false,
  onSaveApiKey,
  onCreateEmbedKey,
}: Props) {
  const [title, setTitle] = React.useState(companyName ? `Chat with ${companyName}` : "Chat with us")
  const [color, setColor] = React.useState("#2563eb")
  const [showKey, setShowKey] = React.useState(false)
  const [copied, setCopied] = React.useState(false)
  const [origin, setOrigin] = React.useState("http://localhost:3000")
  const [draftKey, setDraftKey] = React.useState(apiKey)

  React.useEffect(() => {
    setDraftKey(apiKey)
  }, [apiKey])

  React.useEffect(() => {
    if (typeof window !== "undefined") {
      setOrigin(window.location.origin)
    }
  }, [])

  const resolvedKey = apiKey.trim()
  const embedCode = buildEmbedScript({
    widgetOrigin: origin,
    apiBaseUrl: API_BASE_URL,
    companyId,
    apiKey: resolvedKey || "YOUR_API_KEY",
    title,
    color,
  })

  async function handleCopy() {
    await navigator.clipboard.writeText(embedCode)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  const logoUrl = getCompanyLogoUrl(companyId)

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      {!resolvedKey && (
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>API key required</CardTitle>
            <CardDescription>
              Full keys are only shown once at creation. Paste an existing key or generate a new embed key.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {error && (
              <Alert variant="destructive">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}
            <div className="space-y-2">
              <Label htmlFor="widget-api-key">Paste API key</Label>
              <Input
                id="widget-api-key"
                value={draftKey}
                onChange={(e) => setDraftKey(e.target.value)}
                placeholder="sk_..."
                className="font-mono text-sm"
              />
            </div>
            <div className="flex flex-wrap gap-2">
              <Button
                type="button"
                onClick={() => onSaveApiKey(draftKey)}
                disabled={!draftKey.trim().startsWith("sk_")}
              >
                Use this key
              </Button>
              <Button type="button" variant="outline" onClick={onCreateEmbedKey} disabled={creatingKey}>
                {creatingKey ? "Creating..." : "Create new embed key"}
              </Button>
              <Link className={buttonVariants({ variant: "ghost" })} href="/api">
                Manage keys
              </Link>
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Embed configuration</CardTitle>
          <CardDescription>
            Paste the script on any website. Queries run through your API key with vectorless BM25 retrieval.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="widget-title">Widget title</Label>
            <Input id="widget-title" value={title} onChange={(e) => setTitle(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="widget-color">Accent color</Label>
            <Input id="widget-color" type="color" value={color} onChange={(e) => setColor(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>API key in embed</Label>
            <div className="flex items-center gap-2 rounded-md border bg-muted p-2 font-mono text-xs break-all">
              <span className="flex-1">{showKey ? resolvedKey || "YOUR_API_KEY" : "•".repeat(24)}</span>
              {resolvedKey ? (
                <Button type="button" size="icon" variant="ghost" onClick={() => setShowKey((v) => !v)}>
                  {showKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </Button>
              ) : null}
            </div>
          </div>
          {!resolvedKey && (
            <Alert>
              <AlertDescription className="text-sm">
                Replace YOUR_API_KEY in the embed script after you paste or create a key above.
              </AlertDescription>
            </Alert>
          )}
          {resolvedKey && (
            <Alert>
              <AlertDescription className="text-sm">
                Use a production API key with limited scope. Rotate keys from the API page if exposed.
              </AlertDescription>
            </Alert>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Embed code</CardTitle>
          <CardDescription>Copy and paste before the closing body tag on your site</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <pre className="max-h-72 overflow-auto rounded-md bg-muted p-4 text-xs whitespace-pre-wrap break-all">
            {embedCode}
          </pre>
          <Button type="button" onClick={handleCopy} className="w-full">
            {copied ? <Check className="mr-2 h-4 w-4" /> : <Copy className="mr-2 h-4 w-4" />}
            {copied ? "Copied" : "Copy embed code"}
          </Button>
        </CardContent>
      </Card>

      <Card className="lg:col-span-2">
        <CardHeader>
          <CardTitle>Live preview</CardTitle>
          <CardDescription>This is how the floating widget appears on external sites</CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs defaultValue="script">
            <TabsList>
              <TabsTrigger value="script">Script widget</TabsTrigger>
            </TabsList>
            <TabsContent value="script" className="mt-4">
              <div className="relative min-h-[420px] rounded-lg border bg-slate-50 dark:bg-slate-900">
                <p className="p-6 text-sm text-muted-foreground">Your website content area</p>
                <div className="absolute bottom-5 right-5 flex flex-col items-end gap-3">
                  <div className="flex h-[320px] w-[320px] flex-col overflow-hidden rounded-xl border bg-background shadow-xl">
                    <div className="px-3 py-2 text-sm font-semibold text-white" style={{ background: color }}>
                      {title}
                    </div>
                    <div className="flex-1 space-y-3 overflow-auto bg-slate-50 p-3">
                      <div className="ml-auto max-w-[85%] rounded-lg rounded-br-sm bg-slate-200 px-3 py-2 text-xs">
                        Hello!
                      </div>
                      <div className="flex items-end gap-2">
                        <img
                          src={logoUrl}
                          alt={title}
                          className="size-7 shrink-0 rounded-full border object-cover bg-background"
                        />
                        <div className="max-w-[85%] rounded-lg rounded-bl-sm border bg-background px-3 py-2 text-xs">
                          <p className="mb-0 font-medium">How can I help you today?</p>
                        </div>
                      </div>
                    </div>
                    <div className="flex gap-2 border-t p-2">
                      <div className="h-8 flex-1 rounded-md border bg-background" />
                      <div className="h-8 w-14 rounded-md" style={{ background: color }} />
                    </div>
                  </div>
                  <div
                    className="flex h-14 w-14 items-center justify-center rounded-full text-sm font-semibold text-white shadow-lg"
                    style={{ background: color }}
                  >
                    AI
                  </div>
                </div>
              </div>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  )
}
