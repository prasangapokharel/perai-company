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
import { listAdminKhaltiPayments, type AdminKhaltiPayment } from "@/services/admin.service"

export default function AdminPaymentsPage() {
  const [payments, setPayments] = React.useState<AdminKhaltiPayment[]>([])
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(true)

  React.useEffect(() => {
    const session = loadAuthSession()
    if (!session) return
    listAdminKhaltiPayments(session)
      .then(setPayments)
      .catch((err) => {
        if (err instanceof APIError) setError(err.detail)
        else setError("Failed to load payments")
      })
      .finally(() => setLoading(false))
  }, [])

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Payments</h1>
        <p className="text-muted-foreground">All Khalti top-up transactions</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Khalti payments</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <p className="text-sm text-muted-foreground">Loading...</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Company</TableHead>
                  <TableHead>Amount (USD)</TableHead>
                  <TableHead>NPR (paisa)</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Transaction</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {payments.map((p) => (
                  <TableRow key={p.id}>
                    <TableCell>{new Date(p.created_at).toLocaleString()}</TableCell>
                    <TableCell>
                      <Button variant="link" size="sm" render={<Link href={`/admin/companies/${p.company_id}`} />}>
                        #{p.company_id}
                      </Button>
                    </TableCell>
                    <TableCell>${Number(p.amount_usd).toFixed(2)}</TableCell>
                    <TableCell className="text-muted-foreground">{p.amount_npr_paisa}</TableCell>
                    <TableCell>
                      <Badge variant={p.status === "Completed" ? "default" : "secondary"}>{p.status}</Badge>
                    </TableCell>
                    <TableCell className="font-mono text-xs">{p.transaction_id ?? "—"}</TableCell>
                  </TableRow>
                ))}
                {payments.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                      No payments yet
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
