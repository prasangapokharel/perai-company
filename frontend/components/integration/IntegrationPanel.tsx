"use client"

import * as React from "react"
import { Copy, Check } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { API_BASE_URL } from "@/lib/api-client"
import {
  curlFinetuneGetSnippet,
  curlFinetuneUploadSnippet,
  curlQuerySnippet,
  jsonlUploadSnippet,
  pythonFinetuneGetSnippet,
  pythonFinetuneUploadSnippet,
  pythonQuerySnippet,
  pythonStreamSnippet,
  typescriptFinetuneGetSnippet,
  typescriptFinetuneUploadSnippet,
  typescriptQuerySnippet,
  typescriptStreamSnippet,
} from "@/lib/integration-snippets"

type Props = {
  companyId: number
  apiKey: string
}

function CodeBlock({ code, label }: { code: string; label: string }) {
  const [copied, setCopied] = React.useState(false)

  async function handleCopy() {
    await navigator.clipboard.writeText(code)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium">{label}</p>
        <Button type="button" size="sm" variant="outline" onClick={handleCopy}>
          {copied ? <Check className="mr-1 h-3 w-3" /> : <Copy className="mr-1 h-3 w-3" />}
          {copied ? "Copied" : "Copy"}
        </Button>
      </div>
      <pre className="max-h-80 overflow-auto rounded-md bg-muted p-4 text-xs whitespace-pre-wrap">{code}</pre>
    </div>
  )
}

export function IntegrationPanel({ companyId, apiKey }: Props) {
  const ctx = { apiBaseUrl: API_BASE_URL, companyId, apiKey }

  return (
    <div className="space-y-6">
      <Alert>
        <AlertDescription>
          All integrations use <code className="text-xs">X-API-Key</code> authentication. Upload knowledge with
          POST <code className="text-xs">/company/{"{id}"}/finetune</code> (append or replace), then query chat.
          Only structured <code className="text-xs">.jsonl</code> is accepted (max 10MB, 5000 lines).
        </AlertDescription>
      </Alert>

      <Tabs defaultValue="typescript">
        <TabsList>
          <TabsTrigger value="typescript">TypeScript</TabsTrigger>
          <TabsTrigger value="python">Python</TabsTrigger>
          <TabsTrigger value="curl">cURL</TabsTrigger>
          <TabsTrigger value="jsonl">JSONL</TabsTrigger>
        </TabsList>

        <TabsContent value="typescript" className="mt-4 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Query chat</CardTitle>
              <CardDescription>Non-streaming response with usage tracking</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="chat/query" code={typescriptQuerySnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Stream chat (SSE)</CardTitle>
              <CardDescription>Token-by-token streaming for live UI</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="chat/stream" code={typescriptStreamSnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Upload knowledge (finetune)</CardTitle>
              <CardDescription>Append or replace JSONL knowledge via API key</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="POST /finetune" code={typescriptFinetuneUploadSnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Get knowledge (finetune)</CardTitle>
              <CardDescription>Verify uploaded model and content</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="GET /finetune" code={typescriptFinetuneGetSnippet(ctx)} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="python" className="mt-4 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Query chat</CardTitle>
              <CardDescription>Requires requests: pip install requests</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="chat/query" code={pythonQuerySnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Stream chat (SSE)</CardTitle>
              <CardDescription>Parse SSE data lines from the stream endpoint</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="chat/stream" code={pythonStreamSnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Upload knowledge (finetune)</CardTitle>
              <CardDescription>Same auth as chat — use X-API-Key header</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="POST /finetune" code={pythonFinetuneUploadSnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Get knowledge (finetune)</CardTitle>
              <CardDescription>Read back model name and stored content</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="GET /finetune" code={pythonFinetuneGetSnippet(ctx)} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="curl" className="mt-4 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Quick test</CardTitle>
              <CardDescription>Run from terminal to verify your API key</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="chat/query" code={curlQuerySnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Upload knowledge</CardTitle>
              <CardDescription>POST finetune with append mode</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="POST /finetune" code={curlFinetuneUploadSnippet(ctx)} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Get knowledge</CardTitle>
              <CardDescription>Confirm finetune is active</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="GET /finetune" code={curlFinetuneGetSnippet(ctx)} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="jsonl" className="mt-4 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Knowledge upload format</CardTitle>
              <CardDescription>
                Upload via Finetune page or POST /company/{"{id}"}/finetune with X-API-Key and JSON body
                {" "}{`{"content":"...jsonl...","mode":"append"}`}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="example.jsonl" code={jsonlUploadSnippet(ctx)} />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
