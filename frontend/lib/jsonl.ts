export type JsonlRecord = Record<string, unknown>

export type JsonlValidationResult =
  | { ok: true; records: JsonlRecord[]; lineCount: number }
  | { ok: false; error: string }

export function validateJsonl(raw: string): JsonlValidationResult {
  const lines = raw.split("\n").map((line) => line.trim()).filter(Boolean)
  if (!lines.length) {
    return { ok: false, error: "JSONL file is empty" }
  }

  const records: JsonlRecord[] = []
  for (let i = 0; i < lines.length; i++) {
    try {
      const row = JSON.parse(lines[i]) as unknown
      if (!row || typeof row !== "object" || Array.isArray(row)) {
        return { ok: false, error: `Line ${i + 1} must be a JSON object` }
      }
      if (!isValidRecord(row as JsonlRecord)) {
        return {
          ok: false,
          error: `Line ${i + 1} needs question+answer, title+content, or text`,
        }
      }
      records.push(row as JsonlRecord)
    } catch {
      return { ok: false, error: `Invalid JSON on line ${i + 1}` }
    }
  }

  return { ok: true, records, lineCount: lines.length }
}

function isValidRecord(row: JsonlRecord): boolean {
  const question = str(row.question ?? row.q)
  const answer = str(row.answer ?? row.a)
  const title = str(row.title ?? row.topic)
  const content = str(row.content ?? row.body)
  const text = str(row.text)

  if (question && answer) return true
  if (title && content) return true
  if (text) return true
  if (question || content) return true
  return false
}

function str(value: unknown): string {
  return typeof value === "string" ? value.trim() : ""
}

export const SAMPLE_JSONL = `{"question":"What is Perai?","answer":"Perai helps companies deploy AI chat widgets trained on their knowledge base."}
{"title":"Pricing","content":"Starter $29/month, Business $99/month, Enterprise contact sales."}
{"text":"Support email: support@perai.io"}
`

export const JSONL_FORMAT_HELP = [
  "One JSON object per line",
  '{"question":"...","answer":"..."}',
  '{"title":"...","content":"..."}',
  '{"text":"..."}',
]
