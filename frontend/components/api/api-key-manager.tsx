"use client"

import * as React from "react"

import Link from "next/link"
import { useRouter } from "next/navigation"

import { Copy, Eye, EyeOff, Plus, Trash2 } from "lucide-react"

import { Button, buttonVariants } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { loadAuthSession, saveAuthSession, sessionAuth, type AuthSession } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import type { APIKey, APIKeyCreateResponse } from "@/services/api-key.service"
import { createAPIKey, deleteAPIKey, listAPIKeys, revokeAPIKey } from "@/services/api-key.service"

type Props = {
  title?: string
  description?: string
}

export function ApiKeyManager({
  title = "API Keys",
  description = "Manage your company API keys",
}: Props) {
  const router = useRouter()
  const [keys, setKeys] = React.useState<APIKey[]>([])
  const [loading, setLoading] = React.useState(true)
  const [error, setError] = React.useState("")
  const [success, setSuccess] = React.useState("")
  const [session, setSession] = React.useState<AuthSession | null>(null)
  const [createOpen, setCreateOpen] = React.useState(false)
  const [createName, setCreateName] = React.useState("")
  const [createLoading, setCreateLoading] = React.useState(false)
  const [newKey, setNewKey] = React.useState<APIKeyCreateResponse | null>(null)
  const [showKey, setShowKey] = React.useState(false)
  const [deleteOpen, setDeleteOpen] = React.useState(false)
  const [deleteKeyId, setDeleteKeyId] = React.useState<number | null>(null)
  const [deleteLoading, setDeleteLoading] = React.useState(false)
  const [revokeOpen, setRevokeOpen] = React.useState(false)
  const [revokeKeyId, setRevokeKeyId] = React.useState<number | null>(null)
  const [revokeLoading, setRevokeLoading] = React.useState(false)

  React.useEffect(() => {
    async function load() {
      try {
        const sess = loadAuthSession()
        if (!sess) {
          router.push("/login")
          return
        }
        setSession(sess)
        setKeys(await listAPIKeys(sess.companyId, sessionAuth(sess)))
      } catch (err) {
        if (err instanceof APIError) setError(`Error: ${err.detail}`)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load API keys")
      } finally {
        setLoading(false)
      }
    }

    load()
  }, [router])

  async function refreshKeys() {
    if (!session) return
    setKeys(await listAPIKeys(session.companyId, sessionAuth(session)))
  }

  async function handleCreateKey() {
    if (!createName.trim() || !session) return
    setCreateLoading(true)
    setError("")
    try {
      const expiry = new Date()
      expiry.setDate(expiry.getDate() + 90)
      const key = await createAPIKey(session.companyId, { name: createName, expiry_date: expiry.toISOString() }, sessionAuth(session))
      setNewKey(key)
      setCreateName("")
      setSuccess("API key created successfully!")
      const updated = {
        ...session,
        apiKey: key.key,
      }
      setSession(updated)
      saveAuthSession({
        companyId: updated.companyId,
        apiKey: key.key,
        accessToken: updated.accessToken,
        companyName: updated.companyName,
        balance: updated.balance,
        currency: updated.currency,
      })
      await refreshKeys()
    } catch (err) {
      if (err instanceof APIError) setError(`Error: ${err.detail}`)
      else if (err instanceof Error) setError(err.message)
      else setError("Failed to create API key")
    } finally {
      setCreateLoading(false)
    }
  }

  async function handleRevokeKey() {
    if (!session || revokeKeyId === null) return
    setRevokeLoading(true)
    try {
      await revokeAPIKey(session.companyId, revokeKeyId, sessionAuth(session))
      setSuccess("API key revoked successfully!")
      setRevokeOpen(false)
      setRevokeKeyId(null)
      await refreshKeys()
    } catch (err) {
      if (err instanceof APIError) setError(`Error: ${err.detail}`)
      else if (err instanceof Error) setError(err.message)
      else setError("Failed to revoke API key")
    } finally {
      setRevokeLoading(false)
    }
  }

  async function handleDeleteKey() {
    if (!session || deleteKeyId === null) return
    setDeleteLoading(true)
    try {
      await deleteAPIKey(session.companyId, deleteKeyId, sessionAuth(session))
      setSuccess("API key deleted successfully!")
      setDeleteOpen(false)
      setDeleteKeyId(null)
      await refreshKeys()
    } catch (err) {
      if (err instanceof APIError) setError(`Error: ${err.detail}`)
      else if (err instanceof Error) setError(err.message)
      else setError("Failed to delete API key")
    } finally {
      setDeleteLoading(false)
    }
  }

  if (loading) return <p className="text-muted-foreground">Loading API keys...</p>

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
          <p className="text-muted-foreground">{description}</p>
        </div>
        <div className="flex gap-2">
          <Link className={buttonVariants({ variant: "outline" })} href="/widget">Embed Widget</Link>
          <Link className={buttonVariants({ variant: "outline" })} href="/integration">Integration</Link>
          <Dialog open={createOpen} onOpenChange={setCreateOpen}>
            <DialogTrigger render={<Button type="button" />}>
              <Plus className="mr-2 h-4 w-4" />Create API Key
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Create New API Key</DialogTitle>
                <DialogDescription>Create a new API key for your application</DialogDescription>
              </DialogHeader>
              {newKey ? (
                <Alert>
                  <AlertDescription className="space-y-3">
                    <p className="font-semibold text-green-700">✓ API Key Created!</p>
                    <div className="bg-muted flex items-center justify-between gap-2 rounded p-3 font-mono text-xs break-all">
                      <span className="flex-1">{showKey ? newKey.key : "•".repeat(40)}</span>
                      <Button size="sm" variant="ghost" type="button" onClick={() => setShowKey((v) => !v)}>{showKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}</Button>
                      <Button size="sm" variant="ghost" type="button" onClick={() => { navigator.clipboard.writeText(newKey.key); setSuccess("Copied to clipboard!") }}><Copy className="h-4 w-4" /></Button>
                    </div>
                    <Button className="w-full" type="button" onClick={() => { setCreateOpen(false); setNewKey(null); setShowKey(false) }}>Done</Button>
                  </AlertDescription>
                </Alert>
              ) : (
                <FieldGroup>
                  <Field>
                    <FieldLabel htmlFor="key-name">Key Name</FieldLabel>
                    <Input id="key-name" value={createName} onChange={(e) => setCreateName(e.target.value)} placeholder="e.g., Production" disabled={createLoading} />
                  </Field>
                  <div className="flex gap-2">
                    <Button variant="outline" type="button" onClick={() => setCreateOpen(false)} disabled={createLoading}>Cancel</Button>
                    <Button type="button" onClick={handleCreateKey} disabled={createLoading || !createName.trim()}>{createLoading ? "Creating..." : "Create"}</Button>
                  </div>
                </FieldGroup>
              )}
            </DialogContent>
          </Dialog>
        </div>
      </div>

      {error && <Alert variant="destructive"><AlertDescription>{error}</AlertDescription></Alert>}
      {success && <Alert><AlertDescription className="text-green-700">{success}</AlertDescription></Alert>}

      <Card>
        <CardHeader>
          <CardTitle>API Keys</CardTitle>
          <CardDescription>Created keys and their status</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {keys.length ? keys.map((key) => (
            <div key={key.id} className="flex items-center justify-between gap-3 rounded border p-4">
              <div>
                <p className="font-medium">{key.name}</p>
                <p className="text-sm text-muted-foreground">{key.key_preview}</p>
              </div>
              <div className="flex items-center gap-2">
                <Badge variant={key.status === "active" ? "default" : key.status === "revoked" ? "destructive" : "secondary"}>{key.status}</Badge>
                {key.status === "active" && <Button size="sm" variant="outline" type="button" onClick={() => { setRevokeKeyId(key.id); setRevokeOpen(true) }}>Revoke</Button>}
                <Button size="sm" variant="destructive" type="button" onClick={() => { setDeleteKeyId(key.id); setDeleteOpen(true) }}><Trash2 className="h-4 w-4" /></Button>
              </div>
            </div>
          )) : <p className="text-sm text-muted-foreground">No API keys yet</p>}
        </CardContent>
      </Card>

      <AlertDialog open={revokeOpen} onOpenChange={setRevokeOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Revoke API Key?</AlertDialogTitle>
            <AlertDialogDescription>This will stop the key from working.</AlertDialogDescription>
          </AlertDialogHeader>
          <div className="flex gap-2">
            <AlertDialogCancel disabled={revokeLoading}>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleRevokeKey} disabled={revokeLoading}>{revokeLoading ? "Revoking..." : "Revoke"}</AlertDialogAction>
          </div>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete API Key?</AlertDialogTitle>
            <AlertDialogDescription>This action cannot be undone.</AlertDialogDescription>
          </AlertDialogHeader>
          <div className="flex gap-2">
            <AlertDialogCancel disabled={deleteLoading}>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDeleteKey} disabled={deleteLoading}>{deleteLoading ? "Deleting..." : "Delete"}</AlertDialogAction>
          </div>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
