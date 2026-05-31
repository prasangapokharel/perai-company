"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyTitle } from "@/components/ui/empty"
import { useAuthSession } from "@/features/auth/hooks"
import { listTickets, type Ticket } from "@/services/ticket.service"
import { APIError } from "@/lib/api-client"

export function TicketsTable() {
  const { session } = useAuthSession()
  const [tickets, setTickets] = React.useState<Ticket[]>([])
  const [loading, setLoading] = React.useState(true)
  const [error, setError] = React.useState("")

  React.useEffect(() => {
    async function load() {
      try {
        if (!session) return
        const data = await listTickets(session.companyId, {}, session.apiKey)
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
  }, [session])

  if (loading) return <p className="text-sm text-muted-foreground">Loading tickets...</p>

  if (!tickets.length) {
    return (
      <Empty>
        <EmptyContent>
          <EmptyHeader>
            <EmptyTitle>No tickets yet</EmptyTitle>
            <EmptyDescription>{error || "Create the first ticket from support."}</EmptyDescription>
          </EmptyHeader>
        </EmptyContent>
      </Empty>
    )
  }

  return (
    <div className="space-y-4">
      {error ? <p className="text-sm text-destructive">{error}</p> : null}
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Issue</TableHead>
            <TableHead>Category</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Created</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {tickets.map((ticket) => (
            <TableRow key={ticket.id}>
              <TableCell className="max-w-md whitespace-normal">{ticket.issue}</TableCell>
              <TableCell>
                <Badge variant="secondary">{ticket.category}</Badge>
              </TableCell>
              <TableCell>
                <Badge variant={ticket.status === "open" ? "default" : "outline"}>{ticket.status}</Badge>
              </TableCell>
              <TableCell>{new Date(ticket.created_at).toLocaleDateString()}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <div className="flex gap-2">
        <Button variant="outline" size="sm">Refresh</Button>
      </div>
    </div>
  )
}
