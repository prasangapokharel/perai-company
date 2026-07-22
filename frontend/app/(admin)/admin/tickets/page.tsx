"use client"

import * as React from "react"
import Link from "next/link"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { loadAuthSession } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import {
  deleteAdminTicket,
  listAdminTickets,
  updateAdminTicket,
  type AdminTicket,
} from "@/services/admin.service"

export default function AdminTicketsPage() {
  const [tickets, setTickets] = React.useState<AdminTicket[]>([])
  const [filter, setFilter] = React.useState<"all" | "open" | "closed">("all")
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(true)
  const [busy, setBusy] = React.useState(false)

  const load = React.useCallback(async (f: "all" | "open" | "closed") => {
    const session = loadAuthSession()
    if (!session) return
    setLoading(true)
    setError("")
    try {
      const rows = await listAdminTickets(session, f === "all" ? undefined : f)
      setTickets(rows)
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Failed to load tickets")
    } finally {
      setLoading(false)
    }
  }, [])

  React.useEffect(() => {
    load(filter)
  }, [filter, load])

  async function toggleStatus(t: AdminTicket) {
    const session = loadAuthSession()
    if (!session) return
    setBusy(true)
    try {
      await updateAdminTicket(session, t.id, t.status === "open" ? "closed" : "open")
      await load(filter)
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Update failed")
    } finally {
      setBusy(false)
    }
  }

  async function remove(t: AdminTicket) {
    const session = loadAuthSession()
    if (!session) return
    if (!window.confirm("Delete this ticket?")) return
    setBusy(true)
    try {
      await deleteAdminTicket(session, t.id)
      await load(filter)
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Delete failed")
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Tickets</h1>
        <p className="text-muted-foreground">Support tickets across all companies</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <div className="flex gap-2">
        {(["all", "open", "closed"] as const).map((f) => (
          <Button
            key={f}
            variant={filter === f ? "default" : "outline"}
            size="sm"
            onClick={() => setFilter(f)}
          >
            {f[0].toUpperCase() + f.slice(1)}
          </Button>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Tickets</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <p className="text-sm text-muted-foreground">Loading...</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>ID</TableHead>
                  <TableHead>Company</TableHead>
                  <TableHead>Issue</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead />
                </TableRow>
              </TableHeader>
              <TableBody>
                {tickets.map((t) => (
                  <TableRow key={t.id}>
                    <TableCell className="text-muted-foreground">{t.id}</TableCell>
                    <TableCell>
                      <Button variant="link" size="sm" render={<Link href={`/admin/companies/${t.company_id}`} />}>
                        #{t.company_id}
                      </Button>
                    </TableCell>
                    <TableCell className="max-w-xs truncate">{t.issue}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{t.category}</Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={t.status === "open" ? "default" : "secondary"}>{t.status}</Badge>
                    </TableCell>
                    <TableCell className="flex justify-end gap-2">
                      <Button variant="outline" size="sm" disabled={busy} onClick={() => toggleStatus(t)}>
                        {t.status === "open" ? "Close" : "Reopen"}
                      </Button>
                      <Button variant="destructive" size="sm" disabled={busy} onClick={() => remove(t)}>
                        Delete
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
                {tickets.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                      No tickets
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
