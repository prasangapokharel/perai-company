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
import { listAdminApiKeys, revokeAdminApiKey, type AdminApiKey } from "@/services/admin.service"

export default function AdminApiKeysPage() {
  const [keys, setKeys] = React.useState<AdminApiKey[]>([])
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(true)
  const [busy, setBusy] = React.useState(false)

  const load = React.useCallback(async () => {
    const session = loadAuthSession()
    if (!session) return
    setLoading(true)
    setError("")
    try {
      setKeys(await listAdminApiKeys(session))
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Failed to load API keys")
    } finally {
      setLoading(false)
    }
  }, [])

  React.useEffect(() => {
    load()
  }, [load])

  async function revoke(k: AdminApiKey) {
    const session = loadAuthSession()
    if (!session) return
    if (!window.confirm(`Revoke key "${k.name}" for company #${k.company_id}?`)) return
    setBusy(true)
    try {
      await revokeAdminApiKey(session, k.id)
      await load()
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Revoke failed")
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">API Keys</h1>
        <p className="text-muted-foreground">All API keys across the platform</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle>API keys</CardTitle>
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
                  <TableHead>Name</TableHead>
                  <TableHead>Preview</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead />
                </TableRow>
              </TableHeader>
              <TableBody>
                {keys.map((k) => (
                  <TableRow key={k.id}>
                    <TableCell className="text-muted-foreground">{k.id}</TableCell>
                    <TableCell>
                      <Button variant="link" size="sm" render={<Link href={`/admin/companies/${k.company_id}`} />}>
                        #{k.company_id}
                      </Button>
                    </TableCell>
                    <TableCell className="font-medium">{k.name}</TableCell>
                    <TableCell className="font-mono text-xs">{k.key_preview}</TableCell>
                    <TableCell>
                      <Badge variant={k.status === "active" ? "default" : "secondary"}>{k.status}</Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      {k.status === "active" && (
                        <Button variant="destructive" size="sm" disabled={busy} onClick={() => revoke(k)}>
                          Revoke
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
                {keys.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                      No API keys
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
