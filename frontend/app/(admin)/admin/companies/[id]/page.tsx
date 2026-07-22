"use client"

import * as React from "react"
import { useParams, useRouter } from "next/navigation"
import { Wallet, Trash2 } from "lucide-react"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Switch } from "@/components/ui/switch"
import { Label } from "@/components/ui/label"
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
  adjustAdminBalance,
  deleteAdminCompany,
  getAdminCompany,
  listAdminDeductions,
  listAdminKhaltiPayments,
  listAdminTopups,
  setAdminCompanyRole,
  updateAdminCompany,
  type AdminCompany,
  type AdminDeduction,
  type AdminKhaltiPayment,
  type AdminTopup,
} from "@/services/admin.service"

export default function AdminCompanyDetailPage() {
  const params = useParams<{ id: string }>()
  const router = useRouter()
  const companyId = Number(params.id)

  const [company, setCompany] = React.useState<AdminCompany | null>(null)
  const [topups, setTopups] = React.useState<AdminTopup[]>([])
  const [deductions, setDeductions] = React.useState<AdminDeduction[]>([])
  const [payments, setPayments] = React.useState<AdminKhaltiPayment[]>([])
  const [error, setError] = React.useState("")
  const [success, setSuccess] = React.useState("")
  const [loading, setLoading] = React.useState(true)
  const [busy, setBusy] = React.useState(false)

  // form state
  const [name, setName] = React.useState("")
  const [email, setEmail] = React.useState("")
  const [website, setWebsite] = React.useState("")
  const [password, setPassword] = React.useState("")
  const [adjustAmount, setAdjustAmount] = React.useState("")
  const [adjustReason, setAdjustReason] = React.useState("")

  const load = React.useCallback(async () => {
    const session = loadAuthSession()
    if (!session) return
    setError("")
    try {
      const [c, t, d, p] = await Promise.all([
        getAdminCompany(session, companyId),
        listAdminTopups(session, companyId),
        listAdminDeductions(session, companyId),
        listAdminKhaltiPayments(session, companyId),
      ])
      setCompany(c)
      setName(c.company_name)
      setEmail(c.company_email)
      setWebsite(c.website ?? "")
      setTopups(t)
      setDeductions(d)
      setPayments(p)
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Failed to load company")
    } finally {
      setLoading(false)
    }
  }, [companyId])

  React.useEffect(() => {
    load()
  }, [load])

  async function handleSave() {
    const session = loadAuthSession()
    if (!session) return
    setBusy(true)
    setError("")
    setSuccess("")
    try {
      await updateAdminCompany(session, companyId, {
        company_name: name,
        company_email: email,
        website: website || undefined,
        password: password || undefined,
      })
      setPassword("")
      setSuccess("Company updated")
      await load()
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Update failed")
    } finally {
      setBusy(false)
    }
  }

  async function handleToggleAdmin(next: boolean) {
    const session = loadAuthSession()
    if (!session) return
    setBusy(true)
    setError("")
    setSuccess("")
    try {
      const updated = await setAdminCompanyRole(session, companyId, next)
      setCompany(updated)
      setSuccess(next ? "Promoted to admin" : "Demoted to company")
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Role change failed")
    } finally {
      setBusy(false)
    }
  }

  async function handleAdjust() {
    const session = loadAuthSession()
    if (!session) return
    const amount = Number(adjustAmount)
    if (!amount || Number.isNaN(amount)) {
      setError("Enter a non-zero amount")
      return
    }
    setBusy(true)
    setError("")
    setSuccess("")
    try {
      const res = await adjustAdminBalance(session, companyId, amount, adjustReason || undefined)
      setSuccess(`Balance updated to $${Number(res.balance).toFixed(2)}`)
      setAdjustAmount("")
      setAdjustReason("")
      await load()
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Adjustment failed")
    } finally {
      setBusy(false)
    }
  }

  async function handleDelete() {
    const session = loadAuthSession()
    if (!session) return
    if (!window.confirm("Delete this company and all its data? This cannot be undone.")) return
    setBusy(true)
    setError("")
    try {
      await deleteAdminCompany(session, companyId)
      router.push("/admin/companies")
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else setError("Delete failed")
      setBusy(false)
    }
  }

  if (loading) {
    return (
      <div className="flex h-96 items-center justify-center">
        <p className="text-muted-foreground">Loading company...</p>
      </div>
    )
  }

  if (!company) {
    return (
      <Alert variant="destructive">
        <AlertDescription>{error || "Company not found"}</AlertDescription>
      </Alert>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">{company.company_name}</h1>
          <p className="text-muted-foreground">
            Company #{company.id} · {company.company_email}
          </p>
        </div>
        <Badge variant={company.is_admin ? "destructive" : "secondary"}>
          {company.is_admin ? "Admin" : "Company"}
        </Badge>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      {success && (
        <Alert>
          <AlertDescription className="text-green-700">{success}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Wallet className="h-5 w-5" />
            Balance
          </CardTitle>
          <CardDescription>Current: ${Number(company.balance).toFixed(2)} USD</CardDescription>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="grid gap-3 sm:grid-cols-3">
            <div>
              <Label htmlFor="amount">Amount (+/-)</Label>
              <Input
                id="amount"
                type="number"
                step="0.01"
                placeholder="e.g. 10 or -5"
                value={adjustAmount}
                onChange={(e) => setAdjustAmount(e.target.value)}
              />
            </div>
            <div className="sm:col-span-2">
              <Label htmlFor="reason">Reason (optional)</Label>
              <Input
                id="reason"
                placeholder="e.g. goodwill credit"
                value={adjustReason}
                onChange={(e) => setAdjustReason(e.target.value)}
              />
            </div>
          </div>
          <Button onClick={handleAdjust} disabled={busy}>
            Apply adjustment
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <Label htmlFor="name">Name</Label>
              <Input id="name" value={name} onChange={(e) => setName(e.target.value)} />
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input id="email" value={email} onChange={(e) => setEmail(e.target.value)} />
            </div>
            <div>
              <Label htmlFor="website">Website</Label>
              <Input id="website" value={website} onChange={(e) => setWebsite(e.target.value)} />
            </div>
            <div>
              <Label htmlFor="password">Reset password</Label>
              <Input
                id="password"
                type="password"
                placeholder="Leave blank to keep"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
          </div>
          <Button onClick={handleSave} disabled={busy}>
            Save changes
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Role & danger zone</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center gap-3">
            <Switch
              id="is-admin"
              checked={company.is_admin}
              onCheckedChange={(v) => handleToggleAdmin(Boolean(v))}
              disabled={busy}
            />
            <Label htmlFor="is-admin">Platform admin</Label>
          </div>
          <Button variant="destructive" onClick={handleDelete} disabled={busy}>
            <Trash2 className="mr-2 h-4 w-4" />
            Delete company
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Khalti payments</CardTitle>
        </CardHeader>
        <CardContent>
          {payments.length === 0 ? (
            <p className="text-sm text-muted-foreground">No Khalti payments</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>PIDX</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {payments.map((p) => (
                  <TableRow key={p.id}>
                    <TableCell>{new Date(p.created_at).toLocaleString()}</TableCell>
                    <TableCell>${Number(p.amount_usd).toFixed(2)}</TableCell>
                    <TableCell>
                      <Badge variant={p.status === "Completed" ? "default" : "secondary"}>{p.status}</Badge>
                    </TableCell>
                    <TableCell className="font-mono text-xs">{p.pidx}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Top-ups</CardTitle>
          </CardHeader>
          <CardContent>
            {topups.length === 0 ? (
              <p className="text-sm text-muted-foreground">No top-ups</p>
            ) : (
              <div className="space-y-2">
                {topups.map((t) => (
                  <div key={t.id} className="flex items-center justify-between rounded-md border p-2 text-sm">
                    <span className="font-medium text-green-700">+${Number(t.amount).toFixed(2)}</span>
                    <span className="text-muted-foreground">{new Date(t.created_at).toLocaleDateString()}</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Deductions</CardTitle>
          </CardHeader>
          <CardContent>
            {deductions.length === 0 ? (
              <p className="text-sm text-muted-foreground">No deductions</p>
            ) : (
              <div className="space-y-2">
                {deductions.map((d) => (
                  <div key={d.id} className="flex items-center justify-between rounded-md border p-2 text-sm">
                    <span className="font-medium text-red-600">-${Number(d.amount).toFixed(4)}</span>
                    <span className="text-muted-foreground">{new Date(d.created_at).toLocaleDateString()}</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
