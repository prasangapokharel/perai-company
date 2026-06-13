"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import type { Ticket, TicketCreateInput, TicketUpdateInput } from "@/services/ticket.service"

type TicketFormValue = {
  issue: string
  category: Ticket["category"]
  status?: Ticket["status"]
}

type TicketFormProps = {
  title: string
  description: string
  initialValue?: Partial<TicketFormValue>
  submitLabel: string
  onSubmit: (payload: TicketCreateInput | TicketUpdateInput) => Promise<void>
  loading?: boolean
}

export function TicketForm({
  title,
  description,
  initialValue,
  submitLabel,
  onSubmit,
  loading = false,
}: TicketFormProps) {
  const [issue, setIssue] = React.useState(initialValue?.issue ?? "")
  const [category, setCategory] = React.useState<Ticket["category"]>(initialValue?.category ?? "general")
  const [status, setStatus] = React.useState<Ticket["status"]>(initialValue?.status ?? "open")
  const [busy, setBusy] = React.useState(false)
  const [error, setError] = React.useState("")

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setBusy(true)
    setError("")

    try {
      await onSubmit(
        initialValue
          ? { issue, category, status }
          : { issue, category },
      )
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to save ticket")
    } finally {
      setBusy(false)
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent>
        <form className="space-y-4" onSubmit={handleSubmit}>
          <FieldGroup>
            <Field>
              <FieldLabel htmlFor="issue">Issue</FieldLabel>
              <Textarea id="issue" value={issue} onChange={(e) => setIssue(e.target.value)} required placeholder="Describe the problem" />
            </Field>
          <Field>
            <FieldLabel>Category</FieldLabel>
            <Select value={category} onValueChange={(value) => setCategory(value as Ticket["category"])}>
              <SelectTrigger><SelectValue placeholder="Category" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="general">General</SelectItem>
                  <SelectItem value="technical">Technical</SelectItem>
                  <SelectItem value="payment">Payment</SelectItem>
                </SelectContent>
              </Select>
            </Field>
            {initialValue && (
              <Field>
                <FieldLabel>Status</FieldLabel>
                <Select value={status} onValueChange={(value) => setStatus(value as Ticket["status"])}>
                  <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="open">Open</SelectItem>
                    <SelectItem value="closed">Closed</SelectItem>
                  </SelectContent>
                </Select>
              </Field>
            )}
          </FieldGroup>

          {error && <p className="text-sm text-destructive">{error}</p>}

          <div className="flex gap-2">
            <Button type="submit" disabled={busy || loading}>{busy ? "Saving..." : submitLabel}</Button>
          </div>
        </form>
      </CardContent>
    </Card>
  )
}
