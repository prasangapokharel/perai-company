"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Field, FieldLabel, FieldGroup } from "@/components/ui/field"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { loadAuthSession } from "@/features/auth/hooks"
import {
  listAPIKeys,
  revokeAPIKey,
  deleteAPIKey,
  createAPIKey,
} from "@/services/api-key.service"
import { APIError } from "@/lib/api-client"
import { Copy, Trash2, Eye, EyeOff, Plus } from "lucide-react"

type APIKey = {
  id: number
  company_id: number
  name: string
  key_preview: string
  status: string
  expiry_date?: string | null
  last_used_at?: string | null
  created_at: string
  updated_at: string
}

export default function APIPage() {
  const router = useRouter()
  const [keys, setKeys] = useState<APIKey[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [success, setSuccess] = useState("")
  const [session, setSession] = useState<any>(null)

  // Create dialog state
  const [createOpen, setCreateOpen] = useState(false)
  const [createName, setCreateName] = useState("")
  const [createLoading, setCreateLoading] = useState(false)
  const [newKey, setNewKey] = useState<any>(null)
  const [showKey, setShowKey] = useState(false)

  // Delete dialog state
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [deleteKeyId, setDeleteKeyId] = useState<number | null>(null)
  const [deleteLoading, setDeleteLoading] = useState(false)

  // Revoke dialog state
  const [revokeOpen, setRevokeOpen] = useState(false)
  const [revokeKeyId, setRevokeKeyId] = useState<number | null>(null)
  const [revokeLoading, setRevokeLoading] = useState(false)

  useEffect(() => {
    async function loadKeys() {
      try {
        const sess = loadAuthSession()
        if (!sess) {
          router.push("/login")
          return
        }
        setSession(sess)

        const apiKeys = await listAPIKeys(sess.companyId, sess.apiKey)
        setKeys(apiKeys)
      } catch (err) {
        if (err instanceof APIError) {
          setError(`Error: ${err.detail}`)
        } else if (err instanceof Error) {
          setError(err.message)
        } else {
          setError("Failed to load API keys")
        }
      } finally {
        setLoading(false)
      }
    }

    loadKeys()
  }, [router])

  async function handleCreateKey() {
    if (!createName.trim() || !session) return

    setCreateLoading(true)
    setError("")

    try {
      const expiry = new Date()
      expiry.setDate(expiry.getDate() + 90)

      const key = await createAPIKey(session.companyId, {
        name: createName,
        expiry_date: expiry.toISOString(),
      })

      setNewKey(key)
      setCreateName("")
      setSuccess("API key created successfully!")

      // Reload keys
      const apiKeys = await listAPIKeys(session.companyId, session.apiKey)
      setKeys(apiKeys)
    } catch (err) {
      if (err instanceof APIError) {
        setError(`Error: ${err.detail}`)
      } else if (err instanceof Error) {
        setError(err.message)
      } else {
        setError("Failed to create API key")
      }
    } finally {
      setCreateLoading(false)
    }
  }

  async function handleRevokeKey() {
    if (!revokeKeyId || !session) return

    setRevokeLoading(true)
    setError("")

    try {
      await revokeAPIKey(session.companyId, revokeKeyId, session.apiKey)
      setSuccess("API key revoked successfully!")
      setRevokeOpen(false)
      setRevokeKeyId(null)

      // Reload keys
      const apiKeys = await listAPIKeys(session.companyId, session.apiKey)
      setKeys(apiKeys)
    } catch (err) {
      if (err instanceof APIError) {
        setError(`Error: ${err.detail}`)
      } else if (err instanceof Error) {
        setError(err.message)
      } else {
        setError("Failed to revoke API key")
      }
    } finally {
      setRevokeLoading(false)
    }
  }

  async function handleDeleteKey() {
    if (!deleteKeyId || !session) return

    setDeleteLoading(true)
    setError("")

    try {
      await deleteAPIKey(session.companyId, deleteKeyId, session.apiKey)
      setSuccess("API key deleted successfully!")
      setDeleteOpen(false)
      setDeleteKeyId(null)

      // Reload keys
      const apiKeys = await listAPIKeys(session.companyId, session.apiKey)
      setKeys(apiKeys)
    } catch (err) {
      if (err instanceof APIError) {
        setError(`Error: ${err.detail}`)
      } else if (err instanceof Error) {
        setError(err.message)
      } else {
        setError("Failed to delete API key")
      }
    } finally {
      setDeleteLoading(false)
    }
  }

  function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text)
    setSuccess("Copied to clipboard!")
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <p className="text-muted-foreground">Loading API keys...</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">API Keys</h1>
          <p className="text-muted-foreground">Manage your company API keys</p>
        </div>
        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
          <DialogTrigger>
            <Button type="button">
              <Plus className="h-4 w-4 mr-2" />
              Create API Key
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create New API Key</DialogTitle>
              <DialogDescription>
                Create a new API key for your application
              </DialogDescription>
            </DialogHeader>

            {newKey && (
              <Alert>
                <AlertDescription className="space-y-3">
                  <p className="font-semibold text-green-700">✓ API Key Created!</p>
                  <p className="text-sm">
                    Save this key securely. You won't be able to see it again.
                  </p>
                  <div className="bg-muted p-3 rounded font-mono text-xs break-all flex items-center justify-between gap-2">
                    <span className="flex-1">
                      {showKey ? newKey.key : "•".repeat(40)}
                    </span>
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => setShowKey(!showKey)}
                    >
                      {showKey ? (
                        <EyeOff className="h-4 w-4" />
                      ) : (
                        <Eye className="h-4 w-4" />
                      )}
                    </Button>
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => copyToClipboard(newKey.key)}
                    >
                      <Copy className="h-4 w-4" />
                    </Button>
                  </div>
                  <Button
                    className="w-full"
                    onClick={() => {
                      setCreateOpen(false)
                      setNewKey(null)
                      setShowKey(false)
                    }}
                  >
                    Done
                  </Button>
                </AlertDescription>
              </Alert>
            )}

            {!newKey && (
              <FieldGroup>
                <Field>
                  <FieldLabel htmlFor="key-name">Key Name</FieldLabel>
                  <Input
                    id="key-name"
                    value={createName}
                    onChange={(e) => setCreateName(e.target.value)}
                    placeholder="e.g., Production, Development"
                    disabled={createLoading}
                  />
                </Field>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    onClick={() => setCreateOpen(false)}
                    disabled={createLoading}
                  >
                    Cancel
                  </Button>
                  <Button
                    onClick={handleCreateKey}
                    disabled={createLoading || !createName.trim()}
                  >
                    {createLoading ? "Creating..." : "Create"}
                  </Button>
                </div>
              </FieldGroup>
            )}
          </DialogContent>
        </Dialog>
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

      {keys.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <p className="text-muted-foreground mb-4">No API keys yet</p>
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
              <DialogTrigger>
                <Button type="button">
                  <Plus className="h-4 w-4 mr-2" />
                  Create Your First API Key
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Create New API Key</DialogTitle>
                  <DialogDescription>
                    Create a new API key for your application
                  </DialogDescription>
                </DialogHeader>

                {newKey && (
                  <Alert>
                    <AlertDescription className="space-y-3">
                      <p className="font-semibold text-green-700">✓ API Key Created!</p>
                      <p className="text-sm">
                        Save this key securely. You won't be able to see it again.
                      </p>
                      <div className="bg-muted p-3 rounded font-mono text-xs break-all flex items-center justify-between gap-2">
                        <span className="flex-1">
                          {showKey ? newKey.key : "•".repeat(40)}
                        </span>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => setShowKey(!showKey)}
                        >
                          {showKey ? (
                            <EyeOff className="h-4 w-4" />
                          ) : (
                            <Eye className="h-4 w-4" />
                          )}
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => copyToClipboard(newKey.key)}
                        >
                          <Copy className="h-4 w-4" />
                        </Button>
                      </div>
                      <Button
                        className="w-full"
                        onClick={() => {
                          setCreateOpen(false)
                          setNewKey(null)
                          setShowKey(false)
                        }}
                      >
                        Done
                      </Button>
                    </AlertDescription>
                  </Alert>
                )}

                {!newKey && (
                  <FieldGroup>
                    <Field>
                      <FieldLabel htmlFor="key-name">Key Name</FieldLabel>
                      <Input
                        id="key-name"
                        value={createName}
                        onChange={(e) => setCreateName(e.target.value)}
                        placeholder="e.g., Production, Development"
                        disabled={createLoading}
                      />
                    </Field>
                    <div className="flex gap-2">
                      <Button
                        variant="outline"
                        onClick={() => setCreateOpen(false)}
                        disabled={createLoading}
                      >
                        Cancel
                      </Button>
                      <Button
                        onClick={handleCreateKey}
                        disabled={createLoading || !createName.trim()}
                      >
                        {createLoading ? "Creating..." : "Create"}
                      </Button>
                    </div>
                  </FieldGroup>
                )}
              </DialogContent>
            </Dialog>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {keys.map((key) => (
            <Card key={key.id}>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="font-semibold">{key.name}</h3>
                      <Badge
                        variant={
                          key.status === "active"
                            ? "default"
                            : key.status === "revoked"
                              ? "destructive"
                              : "secondary"
                        }
                      >
                        {key.status}
                      </Badge>
                    </div>
                    <div className="space-y-1 text-sm text-muted-foreground">
                      <p>Key: {key.key_preview}</p>
                      <p>Created: {new Date(key.created_at).toLocaleDateString()}</p>
                      {key.expiry_date && (
                        <p>
                          Expires:{" "}
                          {new Date(key.expiry_date).toLocaleDateString()}
                        </p>
                      )}
                      {key.last_used_at && (
                        <p>
                          Last used:{" "}
                          {new Date(key.last_used_at).toLocaleDateString()}
                        </p>
                      )}
                    </div>
                  </div>
                  <div className="flex gap-2">
                    {key.status === "active" && (
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                          setRevokeKeyId(key.id)
                          setRevokeOpen(true)
                        }}
                      >
                        Revoke
                      </Button>
                    )}
                    <Button
                      size="sm"
                      variant="destructive"
                      onClick={() => {
                        setDeleteKeyId(key.id)
                        setDeleteOpen(true)
                      }}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Revoke Dialog */}
      <AlertDialog open={revokeOpen} onOpenChange={setRevokeOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Revoke API Key?</AlertDialogTitle>
            <AlertDialogDescription>
              This will revoke the API key and it will no longer work. You can
              create a new one if needed.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="flex gap-2">
            <AlertDialogCancel disabled={revokeLoading}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={handleRevokeKey}
              disabled={revokeLoading}
              className="bg-yellow-600 hover:bg-yellow-700"
            >
              {revokeLoading ? "Revoking..." : "Revoke"}
            </AlertDialogAction>
          </div>
        </AlertDialogContent>
      </AlertDialog>

      {/* Delete Dialog */}
      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete API Key?</AlertDialogTitle>
            <AlertDialogDescription>
              This action cannot be undone. The API key will be permanently
              deleted.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="flex gap-2">
            <AlertDialogCancel disabled={deleteLoading}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteKey}
              disabled={deleteLoading}
              className="bg-destructive hover:bg-destructive/90"
            >
              {deleteLoading ? "Deleting..." : "Delete"}
            </AlertDialogAction>
          </div>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
