"use client"

import * as React from "react"
import { useRouter } from "next/navigation"
import { CreditCard, Wallet } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { loadAuthSession, saveAuthSession } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { getAuthMe } from "@/services/auth.service"
import {
  CREDIT_PACKAGES,
  getCompanyBalance,
  listBalanceTopups,
  topupCompanyBalance,
} from "@/services/balance.service"

export default function BalancePage() {
  const router = useRouter()
  const [session, setSession] = React.useState(loadAuthSession())
  const [balance, setBalance] = React.useState<string>("0")
  const [currency, setCurrency] = React.useState("USD")
  const [selected, setSelected] = React.useState<number>(10)
  const [topups, setTopups] = React.useState<Array<{ id: number; amount: string; created_at: string }>>([])
  const [loading, setLoading] = React.useState(true)
  const [paying, setPaying] = React.useState(false)
  const [error, setError] = React.useState("")
  const [success, setSuccess] = React.useState("")

  const load = React.useCallback(async () => {
    const sess = loadAuthSession()
    if (!sess?.accessToken && !sess?.apiKey) {
      router.push("/login")
      return
    }
    setSession(sess)
    setError("")
    try {
      const [bal, me, history] = await Promise.all([
        getCompanyBalance(sess!),
        getAuthMe({ apiKey: sess!.apiKey, accessToken: sess!.accessToken }),
        listBalanceTopups(sess!),
      ])
      setBalance(bal.balance)
      setCurrency(bal.currency)
      saveAuthSession({ ...sess!, balance: me.balance, currency: me.currency })
      setTopups(history)
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else if (err instanceof Error) setError(err.message)
      else setError("Failed to load balance")
    } finally {
      setLoading(false)
    }
  }, [router])

  React.useEffect(() => {
    load()
  }, [load])

  async function handlePay() {
    if (!session) return
    setPaying(true)
    setError("")
    setSuccess("")
    try {
      const result = await topupCompanyBalance(session, selected)
      setBalance(result.balance)
      setSuccess(`Added $${selected.toFixed(2)} ${result.currency} to your account.`)
      await load()
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else if (err instanceof Error) setError(err.message)
      else setError("Payment failed")
    } finally {
      setPaying(false)
    }
  }

  if (loading) {
    return (
      <div className="flex h-96 items-center justify-center">
        <p className="text-muted-foreground">Loading balance...</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Balance</h1>
        <p className="text-muted-foreground">Load USD credits for chat usage</p>
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
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2">
              <Wallet className="h-5 w-5" />
              Current balance
            </CardTitle>
            <CardDescription>Available credits for AI chat requests</CardDescription>
          </div>
          <Badge variant="secondary">{currency}</Badge>
        </CardHeader>
        <CardContent>
          <p className="text-4xl font-bold">${balance}</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <CreditCard className="h-5 w-5" />
            Load credits
          </CardTitle>
          <CardDescription>Choose a package and confirm payment</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            {CREDIT_PACKAGES.map((pkg) => (
              <button
                key={pkg.amount}
                type="button"
                onClick={() => setSelected(pkg.amount)}
                className={`rounded-lg border p-4 text-left transition-colors ${
                  selected === pkg.amount
                    ? "border-primary bg-primary/5 ring-2 ring-primary"
                    : "hover:border-muted-foreground/50"
                }`}
              >
                <p className="text-lg font-semibold">{pkg.label}</p>
                <p className="text-xs text-muted-foreground">{pkg.description}</p>
              </button>
            ))}
          </div>
          <Button type="button" className="w-full sm:w-auto" disabled={paying} onClick={handlePay}>
            {paying ? "Processing..." : `Pay $${selected.toFixed(2)}`}
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Recent top-ups</CardTitle>
        </CardHeader>
        <CardContent>
          {topups.length === 0 ? (
            <p className="text-sm text-muted-foreground">No top-ups yet</p>
          ) : (
            <div className="space-y-2">
              {topups.map((row) => (
                <div key={row.id} className="flex items-center justify-between rounded-md border p-3 text-sm">
                  <span className="text-green-700 font-medium">+${row.amount}</span>
                  <span className="text-muted-foreground">
                    {new Date(row.created_at).toLocaleString()}
                  </span>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
