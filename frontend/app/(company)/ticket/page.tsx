"use client"

import * as React from "react"

import Link from "next/link"

import { Button, buttonVariants } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { listTickets, type Ticket } from "@/services/ticket.service"
import { TicketList } from "@/components/tickets/ticketList"

export default function Page() {
  const [tickets, setTickets] = React.useState<Ticket[]>([])
  const [loading, setLoading] = React.useState(true)
  const [error, setError] = React.useState("")

  React.useEffect(() => {
    async function load() {
      try {
        const session = loadAuthSession()
        if (!session) return
        const data = await listTickets(session.companyId, {}, sessionAuth(session))
        setTickets(data)
      } catch (err) {
        if (err instanceof APIError) setError(err.detail)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load tickets")
      } finally {
        setLoading(false)
      }
    }

    load()
  }, [])

  if (loading) return <p className="text-sm text-muted-foreground">Loading tickets...</p>

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Tickets</CardTitle>
          <CardDescription>Support requests for your company</CardDescription>
        </CardHeader>
        <CardContent className="flex gap-2">
          <Link className={buttonVariants()} href="/ticket/create">Create ticket</Link>
          <Link className={buttonVariants({ variant: "outline" })} href="/api/create">API keys</Link>
        </CardContent>
      </Card>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <TicketList tickets={tickets} />
    </div>
  )
}
