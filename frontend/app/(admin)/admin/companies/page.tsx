"use client"

import * as React from "react"
import Link from "next/link"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Input } from "@/components/ui/input"
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
import { listAdminCompanies, type AdminCompany } from "@/services/admin.service"

export default function AdminCompaniesPage() {
  const [companies, setCompanies] = React.useState<AdminCompany[]>([])
  const [search, setSearch] = React.useState("")
  const [error, setError] = React.useState("")
  const [loading, setLoading] = React.useState(true)

  const load = React.useCallback(async (term?: string) => {
    const session = loadAuthSession()
    if (!session) return
    setLoading(true)
    setError("")
    try {
      const rows = await listAdminCompanies(session, term)
      setCompanies(rows)
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Failed to load companies")
    } finally {
      setLoading(false)
    }
  }, [])

  React.useEffect(() => {
    load()
  }, [load])

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    load(search.trim() || undefined)
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Companies</h1>
        <p className="text-muted-foreground">Manage every company on the platform</p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle>All companies</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <form onSubmit={handleSearch} className="flex gap-2">
            <Input
              placeholder="Search by name or email"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
            <Button type="submit" disabled={loading}>
              Search
            </Button>
          </form>

          {loading ? (
            <p className="text-sm text-muted-foreground">Loading...</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>ID</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Balance</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead />
                </TableRow>
              </TableHeader>
              <TableBody>
                {companies.map((c) => (
                  <TableRow key={c.id}>
                    <TableCell className="text-muted-foreground">{c.id}</TableCell>
                    <TableCell className="font-medium">{c.company_name}</TableCell>
                    <TableCell className="text-muted-foreground">{c.company_email}</TableCell>
                    <TableCell>${Number(c.balance).toFixed(2)}</TableCell>
                    <TableCell>
                      {c.is_admin ? (
                        <Badge variant="destructive">Admin</Badge>
                      ) : (
                        <Badge variant="secondary">Company</Badge>
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="outline" size="sm" render={<Link href={`/admin/companies/${c.id}`} />}>
                        Manage
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
                {companies.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                      No companies found
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
