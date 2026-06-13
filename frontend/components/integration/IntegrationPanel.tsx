"use client"

import * as React from "react"
import { Copy, Check } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { API_BASE_URL } from "@/lib/api-client"
import {
  curlQuerySnippet,
  jsonlUploadSnippet,
  pythonQuerySnippet,
  pythonStreamSnippet,
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
          All integrations use <code className="text-xs">X-API-Key</code> authentication. Knowledge uploads accept
          structured <code className="text-xs">.jsonl</code> only. Retrieval is vectorless (BM25 on disk) so usage
          scales without embedding load on the database.
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
        </TabsContent>

        <TabsContent value="curl" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle>Quick test</CardTitle>
              <CardDescription>Run from terminal to verify your API key</CardDescription>
            </CardHeader>
            <CardContent>
              <CodeBlock label="chat/query" code={curlQuerySnippet(ctx)} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="jsonl" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle>Knowledge upload format</CardTitle>
              <CardDescription>
                Upload via Finetune page or POST /company/{"{id}"}/finetune with JSON body content as raw JSONL
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
