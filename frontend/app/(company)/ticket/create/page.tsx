"use client"

import * as React from "react"

import { useRouter } from "next/navigation"

import { Alert, AlertDescription } from "@/components/ui/alert"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { createTicket } from "@/services/ticket.service"
import type { TicketCreateInput, TicketUpdateInput } from "@/services/ticket.service"
import { TicketForm } from "@/components/tickets/ticketForm"

export default function Page() {
  const router = useRouter()
  const [error, setError] = React.useState("")

  async function handleSubmit(payload: TicketCreateInput | TicketUpdateInput) {
    const session = loadAuthSession()
    if (!session) {
      throw new Error("Please log in again")
    }
    if (!payload.issue) {
      throw new Error("Issue is required")
    }

    try {
      await createTicket(session.companyId, { ...payload, issue: payload.issue }, sessionAuth(session))
      router.push("/ticket")
    } catch (err) {
      if (err instanceof APIError) {
        setError(err.detail)
      } else if (err instanceof Error) {
        setError(err.message)
      }
      throw err
    }
  }

  return (
    <div className="space-y-6">
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      <TicketForm
        title="Create ticket"
        description="Submit a new support request"
        submitLabel="Create ticket"
        onSubmit={handleSubmit}
      />
    </div>
  )
}
