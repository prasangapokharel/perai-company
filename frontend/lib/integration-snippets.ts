export type SnippetContext = {
  apiBaseUrl: string
  companyId: number
  apiKey: string
}

export function pythonQuerySnippet(ctx: SnippetContext): string {
  return `import requests

API_BASE = "${ctx.apiBaseUrl}"
COMPANY_ID = ${ctx.companyId}
API_KEY = "${ctx.apiKey}"

response = requests.post(
    f"{API_BASE}/company/{COMPANY_ID}/chat/query",
    headers={
        "Content-Type": "application/json",
        "X-API-Key": API_KEY,
    },
    json={"prompt": "What are your pricing plans?", "audio": True},
    timeout=120,
)
response.raise_for_status()
data = response.json()
print(data["response"])
if data.get("audio_base64"):
    import base64
    with open("reply.wav", "wb") as f:
        f.write(base64.b64decode(data["audio_base64"]))
    print("Saved reply.wav")
`
}

export function pythonStreamSnippet(ctx: SnippetContext): string {
  return `import json
import requests

API_BASE = "${ctx.apiBaseUrl}"
COMPANY_ID = ${ctx.companyId}
API_KEY = "${ctx.apiKey}"

with requests.post(
    f"{API_BASE}/company/{COMPANY_ID}/chat/stream",
    headers={
        "Content-Type": "application/json",
        "X-API-Key": API_KEY,
    },
    json={"message": "Tell me about your product"},
    stream=True,
    timeout=60,
) as response:
    response.raise_for_status()
    for line in response.iter_lines(decode_unicode=True):
        if not line or not line.startswith("data: "):
            continue
        payload = json.loads(line[6:])
        if payload.get("type") == "token":
            print(payload.get("content", ""), end="", flush=True)
`
}

export function typescriptQuerySnippet(ctx: SnippetContext): string {
  return `const API_BASE = "${ctx.apiBaseUrl}"
const COMPANY_ID = ${ctx.companyId}
const API_KEY = "${ctx.apiKey}"

const response = await fetch(\`\${API_BASE}/company/\${COMPANY_ID}/chat/query\`, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
  },
  body: JSON.stringify({ prompt: "What are your pricing plans?", audio: true }),
})

if (!response.ok) {
  throw new Error(\`Chat failed: \${response.status}\`)
}

const data = await response.json()
console.log(data.response)
`
}

export function typescriptStreamSnippet(ctx: SnippetContext): string {
  return `const API_BASE = "${ctx.apiBaseUrl}"
const COMPANY_ID = ${ctx.companyId}
const API_KEY = "${ctx.apiKey}"

const response = await fetch(\`\${API_BASE}/company/\${COMPANY_ID}/chat/stream\`, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
  },
  body: JSON.stringify({ message: "Tell me about your product" }),
})

if (!response.ok || !response.body) {
  throw new Error(\`Stream failed: \${response.status}\`)
}

const reader = response.body.getReader()
const decoder = new TextDecoder()

while (true) {
  const { value, done } = await reader.read()
  if (done) break
  for (const line of decoder.decode(value).split("\\n")) {
    if (!line.startsWith("data: ")) continue
    const payload = JSON.parse(line.slice(6))
    if (payload.type === "token") process.stdout.write(payload.content ?? "")
  }
}
`
}

export function curlQuerySnippet(ctx: SnippetContext): string {
  return `curl -X POST "${ctx.apiBaseUrl}/company/${ctx.companyId}/chat/query" \\
  -H "Content-Type: application/json" \\
  -H "X-API-Key: ${ctx.apiKey}" \\
  -d '{"prompt":"What are your pricing plans?"}'
`
}

export function pythonFinetuneUploadSnippet(ctx: SnippetContext): string {
  return `import requests

API_BASE = "${ctx.apiBaseUrl}"
COMPANY_ID = ${ctx.companyId}
API_KEY = "${ctx.apiKey}"

JSONL = """{"title":"Pricing","content":"Starter $29/month. Business $99/month."}
{"question":"What are your hours?","answer":"Monday to Friday, 9am to 5pm."}"""

response = requests.post(
    f"{API_BASE}/company/{COMPANY_ID}/finetune",
    headers={
        "Content-Type": "application/json",
        "X-API-Key": API_KEY,
    },
    json={"content": JSONL, "mode": "append"},
    timeout=60,
)
response.raise_for_status()
print("Model:", response.json()["company_model_name"])
`
}

export function pythonFinetuneGetSnippet(ctx: SnippetContext): string {
  return `import requests

API_BASE = "${ctx.apiBaseUrl}"
COMPANY_ID = ${ctx.companyId}
API_KEY = "${ctx.apiKey}"

response = requests.get(
    f"{API_BASE}/company/{COMPANY_ID}/finetune",
    headers={"X-API-Key": API_KEY},
    timeout=30,
)
response.raise_for_status()
data = response.json()
print("Model:", data["company_model_name"])
print("Records preview:", (data.get("content") or "")[:200])
`
}

export function typescriptFinetuneUploadSnippet(ctx: SnippetContext): string {
  return `const API_BASE = "${ctx.apiBaseUrl}"
const COMPANY_ID = ${ctx.companyId}
const API_KEY = "${ctx.apiKey}"

const JSONL = \`{"title":"Pricing","content":"Starter $29/month. Business $99/month."}
{"question":"What are your hours?","answer":"Monday to Friday, 9am to 5pm."}\`

const response = await fetch(\`\${API_BASE}/company/\${COMPANY_ID}/finetune\`, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY,
  },
  body: JSON.stringify({ content: JSONL, mode: "append" }),
})

if (!response.ok) {
  throw new Error(\`Finetune upload failed: \${response.status}\`)
}

const data = await response.json()
console.log("Model:", data.company_model_name)
`
}

export function typescriptFinetuneGetSnippet(ctx: SnippetContext): string {
  return `const API_BASE = "${ctx.apiBaseUrl}"
const COMPANY_ID = ${ctx.companyId}
const API_KEY = "${ctx.apiKey}"

const response = await fetch(\`\${API_BASE}/company/\${COMPANY_ID}/finetune\`, {
  headers: { "X-API-Key": API_KEY },
})

if (!response.ok) {
  throw new Error(\`Finetune fetch failed: \${response.status}\`)
}

const data = await response.json()
console.log("Model:", data.company_model_name)
`
}

export function curlFinetuneUploadSnippet(ctx: SnippetContext): string {
  return `curl -X POST "${ctx.apiBaseUrl}/company/${ctx.companyId}/finetune" \\
  -H "Content-Type: application/json" \\
  -H "X-API-Key: ${ctx.apiKey}" \\
  -d '{"content":"{\\"title\\":\\"Pricing\\",\\"content\\":\\"Starter $29/month.\\"}","mode":"append"}'
`
}

export function curlFinetuneGetSnippet(ctx: SnippetContext): string {
  return `curl -X GET "${ctx.apiBaseUrl}/company/${ctx.companyId}/finetune" \\
  -H "X-API-Key: ${ctx.apiKey}"
`
}

export function jsonlUploadSnippet(ctx: SnippetContext): string {
  return `{"question":"What is Perai?","answer":"Perai deploys company AI chat widgets from structured knowledge."}
{"title":"Pricing","content":"Starter $29/month. Business $99/month."}
{"text":"Upload only .jsonl files. Retrieval uses vectorless BM25 on disk."}
`
}
