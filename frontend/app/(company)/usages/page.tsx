"use client"

import * as React from "react"
import { useRouter } from "next/navigation"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { loadAuthSession } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { listBalanceDeducted } from "@/services/balance.service"
import { listCompanyRequests } from "@/services/usage.service"

export default function UsagesPage() {
  const router = useRouter()
  const [loading, setLoading] = React.useState(true)
  const [error, setError] = React.useState("")
  const [requests, setRequests] = React.useState<
    Array<{ id: number; token_consume: number; balance_deducted: string; date: string; ip: string | null }>
  >([])
  const [deductions, setDeductions] = React.useState<
    Array<{
      id: number
      amount: string
      session_id: string | null
      token_consume: number
      model_name: string | null
      chat_message_id: number | null
      created_at: string
    }>
  >([])

  React.useEffect(() => {
    async function load() {
      const session = loadAuthSession()
      if (!session?.accessToken && !session?.apiKey) {
        router.push("/login")
        return
      }
      try {
        const [reqs, deducts] = await Promise.all([
          listCompanyRequests(session),
          listBalanceDeducted(session),
        ])
        setRequests(reqs)
        setDeductions(deducts)
      } catch (err) {
        if (err instanceof APIError) setError(err.detail)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load usage data")
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [router])

  if (loading) {
    return (
      <div className="flex h-96 items-center justify-center">
        <p className="text-muted-foreground">Loading usage...</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Usage</h1>
        <p className="text-muted-foreground">Token consumption and balance deductions (USD)</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Tabs defaultValue="requests">
        <TabsList>
          <TabsTrigger value="requests">Chat requests</TabsTrigger>
          <TabsTrigger value="deductions">Balance deducted</TabsTrigger>
        </TabsList>

        <TabsContent value="requests" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle>Chat requests</CardTitle>
              <CardDescription>Tokens used per API call</CardDescription>
            </CardHeader>
            <CardContent>
              {requests.length === 0 ? (
                <p className="text-sm text-muted-foreground">No chat usage yet</p>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date</TableHead>
                      <TableHead>Tokens</TableHead>
                      <TableHead>Cost (USD)</TableHead>
                      <TableHead>IP</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {requests.map((row) => (
                      <TableRow key={row.id}>
                        <TableCell>{new Date(row.date).toLocaleString()}</TableCell>
                        <TableCell>{row.token_consume.toLocaleString()}</TableCell>
                        <TableCell>${row.balance_deducted}</TableCell>
                        <TableCell className="text-muted-foreground">{row.ip ?? "—"}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="deductions" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle>Balance deducted</CardTitle>
              <CardDescription>USD charged from your credit balance</CardDescription>
            </CardHeader>
            <CardContent>
              {deductions.length === 0 ? (
                <p className="text-sm text-muted-foreground">No deductions yet</p>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date</TableHead>
                      <TableHead>Amount (USD)</TableHead>
                      <TableHead>Tokens</TableHead>
                      <TableHead>Model</TableHead>
                      <TableHead>Session</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {deductions.map((row) => (
                      <TableRow key={row.id}>
                        <TableCell>{new Date(row.created_at).toLocaleString()}</TableCell>
                        <TableCell>${row.amount}</TableCell>
                        <TableCell>{row.token_consume.toLocaleString()}</TableCell>
                        <TableCell className="text-muted-foreground">{row.model_name ?? "—"}</TableCell>
                        <TableCell className="font-mono text-xs">{row.session_id ?? "—"}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
