"use client"

import { useState, useCallback } from "react"
import { Upload, FileText, X, AlertCircle, Check, Download } from "lucide-react"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { upsertCompanyFinetune } from "@/services/company.service"
import { JSONL_FORMAT_HELP, SAMPLE_JSONL, validateJsonl } from "@/lib/jsonl"

import type { ApiAuth } from "@/lib/api-auth"

interface FinetuneUploadProps {
  companyId: number
  apiKey: ApiAuth | string
  onSuccess?: () => void
}

export function FinetuneUpload({ companyId, apiKey, onSuccess }: FinetuneUploadProps) {
  const [file, setFile] = useState<File | null>(null)
  const [recordCount, setRecordCount] = useState(0)
  const [isDragActive, setIsDragActive] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [status, setStatus] = useState<"idle" | "success" | "error">("idle")
  const [message, setMessage] = useState("")

  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragActive(e.type === "dragenter" || e.type === "dragover")
  }, [])

  const validateFile = useCallback(async (f: File): Promise<boolean> => {
    const ext = "." + (f.name.split(".").pop()?.toLowerCase() ?? "")
    if (ext !== ".jsonl") {
      setStatus("error")
      setMessage("Only .jsonl files are accepted")
      return false
    }
    if (f.size > 10 * 1024 * 1024) {
      setStatus("error")
      setMessage("File must be under 10MB")
      return false
    }

    const raw = await f.text()
    const result = validateJsonl(raw)
    if (!result.ok) {
      setStatus("error")
      setMessage(result.error)
      return false
    }

    setRecordCount(result.lineCount)
    return true
  }, [])

  const handleSelect = useCallback(async (f: File) => {
    setStatus("idle")
    setMessage("")
    setRecordCount(0)
    if (await validateFile(f)) {
      setFile(f)
    }
  }, [validateFile])

  const handleDrop = useCallback(async (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragActive(false)
    const f = e.dataTransfer.files[0]
    if (f) await handleSelect(f)
  }, [handleSelect])

  const handleChange = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
    const f = e.target.files?.[0]
    if (f) await handleSelect(f)
  }, [handleSelect])

  const handleUpload = useCallback(async () => {
    if (!file) return
    setIsLoading(true)
    setStatus("idle")
    setMessage("")

    try {
      const raw = await file.text()
      const result = validateJsonl(raw)
      if (!result.ok) {
        setStatus("error")
        setMessage(result.error)
        return
      }

      await upsertCompanyFinetune(companyId, raw, apiKey)
      setStatus("success")
      setMessage(`Uploaded ${result.lineCount} JSONL records. Vectorless BM25 index updated on disk.`)
      setFile(null)
      setRecordCount(0)
      onSuccess?.()
    } catch (e) {
      setStatus("error")
      setMessage(e instanceof Error ? e.message : "Upload failed")
    } finally {
      setIsLoading(false)
    }
  }, [file, companyId, apiKey, onSuccess])

  function downloadSample() {
    const blob = new Blob([SAMPLE_JSONL], { type: "application/jsonl" })
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = "perai-sample.jsonl"
    link.click()
    URL.revokeObjectURL(url)
  }

  return (
    <div className="space-y-4">
      <div className="rounded-lg border bg-muted/40 p-4 text-sm">
        <p className="font-medium mb-2">JSONL format (required)</p>
        <ul className="space-y-1 text-muted-foreground">
          {JSONL_FORMAT_HELP.map((line) => (
            <li key={line} className="font-mono text-xs">{line}</li>
          ))}
        </ul>
        <Button type="button" variant="outline" size="sm" className="mt-3" onClick={downloadSample}>
          <Download className="mr-2 h-4 w-4" />
          Download sample.jsonl
        </Button>
      </div>

      <div
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
        className={cn(
          "relative rounded-lg border-2 border-dashed transition-colors p-8 text-center",
          isDragActive
            ? "border-blue-500 bg-blue-50 dark:bg-blue-950"
            : "border-muted-foreground/25 hover:border-muted-foreground/50",
          isLoading && "opacity-50 pointer-events-none"
        )}
      >
        <input
          type="file"
          accept=".jsonl,application/jsonl,application/x-ndjson"
          onChange={handleChange}
          disabled={isLoading}
          className="absolute inset-0 opacity-0 cursor-pointer"
        />
        <Upload className="h-8 w-8 mx-auto mb-3 text-muted-foreground" />
        <p className="text-sm font-medium">Drag & drop or click to browse</p>
        <p className="text-xs text-muted-foreground mt-1">.jsonl only, up to 10MB</p>
      </div>

      {file && (
        <div className="flex items-center justify-between bg-muted p-3 rounded-lg">
          <div className="flex items-center gap-3 min-w-0">
            <FileText className="h-5 w-5 text-blue-500 shrink-0" />
            <div className="min-w-0">
              <p className="text-sm font-medium truncate">{file.name}</p>
              <p className="text-xs text-muted-foreground">
                {(file.size / 1024).toFixed(1)} KB · {recordCount} records
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => { setFile(null); setRecordCount(0); setStatus("idle"); setMessage("") }}
            disabled={isLoading}
            className="text-muted-foreground hover:text-foreground disabled:opacity-50"
          >
            <X className="h-4 w-4" />
          </button>
        </div>
      )}

      {message && (
        <div
          className={cn(
            "flex items-center gap-2 p-3 rounded-lg text-sm",
            status === "success" && "bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-200 border border-green-200 dark:border-green-800",
            status === "error" && "bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-200 border border-red-200 dark:border-red-800"
          )}
        >
          {status === "success" ? <Check className="h-4 w-4" /> : <AlertCircle className="h-4 w-4" />}
          {message}
        </div>
      )}

      <Button type="button" onClick={handleUpload} disabled={!file || isLoading} className="w-full">
        {isLoading ? "Uploading..." : "Upload JSONL Knowledge Base"}
      </Button>
    </div>
  )
}
