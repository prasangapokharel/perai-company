"use client"

import * as React from "react"

import { useParams } from "next/navigation"

import { Alert, AlertDescription } from "@/components/ui/alert"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { getTicket, getTicketHistory, type Ticket, type TicketHistory } from "@/services/ticket.service"
import { TicketView } from "@/components/tickets/ticket-view"

export default function Page() {
  const params = useParams<{ id: string }>()
  const [ticket, setTicket] = React.useState<Ticket | null>(null)
  const [history, setHistory] = React.useState<TicketHistory | null>(null)
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(true)

  React.useEffect(() => {
    async function load() {
      try {
        const session = loadAuthSession()
        if (!session) return
        const ticketId = Number(params.id)
        const [ticketData, historyData] = await Promise.all([
          getTicket(session.companyId, ticketId, sessionAuth(session)),
          getTicketHistory(session.companyId, ticketId, sessionAuth(session)),
        ])
        setTicket(ticketData)
        setHistory(historyData)
      } catch (err) {
        if (err instanceof APIError) setError(err.detail)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load ticket")
      } finally {
        setLoading(false)
      }
    }

    load()
  }, [params.id])

  if (loading) return <p className="text-sm text-muted-foreground">Loading ticket...</p>

  if (!ticket || !history) {
    return <Alert variant="destructive"><AlertDescription>{error || "Ticket not found"}</AlertDescription></Alert>
  }

  return <TicketView ticket={ticket} history={history} />
}
