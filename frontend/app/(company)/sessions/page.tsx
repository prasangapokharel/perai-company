"use client"

import * as React from "react"
import { useRouter } from "next/navigation"
import { Loader2, Trash2 } from "lucide-react"

import { Alert, AlertDescription } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import {
  deleteSession,
  formatReview,
  listSessions,
  truncateConversation,
  type PaginatedSessions,
} from "@/services/sessions.service"

const PAGE_SIZE = 15

export default function SessionsPage() {
  const router = useRouter()
  const [loading, setLoading] = React.useState(true)
  const [error, setError] = React.useState("")
  const [page, setPage] = React.useState(1)
  const [data, setData] = React.useState<PaginatedSessions | null>(null)
  const [deletingSession, setDeletingSession] = React.useState<string | null>(null)

  const loadPage = React.useCallback(
    async (nextPage: number) => {
      const session = loadAuthSession()
      if (!session?.accessToken && !session?.apiKey) {
        router.push("/login")
        return
      }

      setLoading(true)
      setError("")
      try {
        const result = await listSessions(
          session.companyId,
          nextPage,
          PAGE_SIZE,
          sessionAuth(session),
        )
        setData(result)
        setPage(nextPage)
      } catch (err) {
        if (err instanceof APIError) setError(err.detail)
        else if (err instanceof Error) setError(err.message)
        else setError("Failed to load sessions")
      } finally {
        setLoading(false)
      }
    },
    [router],
  )

  React.useEffect(() => {
    loadPage(1)
  }, [loadPage])

  async function handleDelete(sessionId: string) {
    const session = loadAuthSession()
    if (!session) return

    setDeletingSession(sessionId)
    setError("")
    try {
      await deleteSession(session.companyId, sessionId, sessionAuth(session))
      await loadPage(page)
    } catch (err) {
      if (err instanceof APIError) setError(err.detail)
      else if (err instanceof Error) setError(err.message)
      else setError("Failed to delete session")
    } finally {
      setDeletingSession(null)
    }
  }

  const totalPages = data?.total_pages ?? 0

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Sessions</h1>
        <p className="text-muted-foreground">
          Chat sessions from your widget and API integrations
        </p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Conversation history</CardTitle>
          <CardDescription>
            {data ? `${data.total} total message${data.total === 1 ? "" : "s"}` : "Loading…"}
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {loading ? (
            <div className="flex h-40 items-center justify-center text-muted-foreground">
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Loading sessions…
            </div>
          ) : !data || data.items.length === 0 ? (
            <p className="text-sm text-muted-foreground">No chat sessions yet</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>IP</TableHead>
                    <TableHead>Session</TableHead>
                    <TableHead>Conversation</TableHead>
                    <TableHead>Review</TableHead>
                    <TableHead>Time</TableHead>
                    <TableHead className="w-[80px] text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.items.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="text-muted-foreground">
                        {row.ip ?? "—"}
                      </TableCell>
                      <TableCell className="font-mono text-xs">{row.session_id}</TableCell>
                      <TableCell className="max-w-md" title={row.conversation}>
                        {truncateConversation(row.conversation)}
                      </TableCell>
                      <TableCell>{formatReview(row.review)}</TableCell>
                      <TableCell>{new Date(row.created_at).toLocaleString()}</TableCell>
                      <TableCell className="text-right">
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          aria-label={`Delete session ${row.session_id}`}
                          disabled={deletingSession === row.session_id}
                          onClick={() => handleDelete(row.session_id)}
                        >
                          {deletingSession === row.session_id ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <Trash2 className="h-4 w-4" />
                          )}
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {totalPages > 1 && (
                <Pagination>
                  <PaginationContent>
                    <PaginationItem>
                      <PaginationPrevious
                        href="#"
                        text="Previous"
                        className={page <= 1 ? "pointer-events-none opacity-50" : undefined}
                        onClick={(e) => {
                          e.preventDefault()
                          if (page > 1) loadPage(page - 1)
                        }}
                      />
                    </PaginationItem>
                    <PaginationItem>
                      <span className="px-3 text-sm text-muted-foreground">
                        Page {page} of {totalPages}
                      </span>
                    </PaginationItem>
                    <PaginationItem>
                      <PaginationNext
                        href="#"
                        text="Next"
                        className={page >= totalPages ? "pointer-events-none opacity-50" : undefined}
                        onClick={(e) => {
                          e.preventDefault()
                          if (page < totalPages) loadPage(page + 1)
                        }}
                      />
                    </PaginationItem>
                  </PaginationContent>
                </Pagination>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
