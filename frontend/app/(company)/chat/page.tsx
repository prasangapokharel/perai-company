"use client"

import { useEffect, useState, useRef } from "react"
import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Bubble, BubbleContent } from "@/components/ui/bubble"
import { Message, MessageAvatar, MessageContent } from "@/components/ui/message"
import {
  MessageScroller,
  MessageScrollerButton,
  MessageScrollerContent,
  MessageScrollerProvider,
  MessageScrollerViewport,
} from "@/components/ui/message-scroller"
import { Marker, MarkerContent } from "@/components/ui/marker"
import { InputGroup, InputGroupAddon, InputGroupButton } from "@/components/ui/input-group"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { queryChat } from "@/services/chat.service"
import { getCompanyLogoUrl } from "@/services/file.service"
import { APIError } from "@/lib/api-client"
import { Send, Loader2, MessageCircleDashed, RotateCw } from "lucide-react"

type ChatMessage = {
  id: string
  from: "user" | "assistant"
  text: string
  timestamp: number
}

const STORAGE_KEY = "perai_chat_history"
const MAX_STORED_MESSAGES = 100

export default function ChatPage() {
  const router = useRouter()
  const [messages, setMessages] = useState<ChatMessage[]>([])
  const [input, setInput] = useState("")
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState("")
  const [session, setSession] = useState<ReturnType<typeof loadAuthSession>>(null)
  const [chatSessionId, setChatSessionId] = useState<string>()
  const messageIdRef = useRef(0)
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  // Load messages from localStorage on mount
  useEffect(() => {
    const sess = loadAuthSession()
    if (!sess?.accessToken && !sess?.apiKey) {
      router.push("/login")
      return
    }
    setSession(sess)

    // Load chat history for this company
    const storageKey = `${STORAGE_KEY}_${sess.companyId}`
    const stored = localStorage.getItem(storageKey)
    if (stored) {
      try {
        const parsed = JSON.parse(stored)
        setMessages(parsed)
        messageIdRef.current = parsed.length
      } catch (e) {
        console.error("Failed to parse stored messages", e)
      }
    }
  }, [router])

  // Save messages to localStorage whenever they change
  useEffect(() => {
    if (!session?.companyId || messages.length === 0) return

    const storageKey = `${STORAGE_KEY}_${session.companyId}`
    const toStore = messages.slice(-MAX_STORED_MESSAGES)
    localStorage.setItem(storageKey, JSON.stringify(toStore))
  }, [messages, session?.companyId])

  async function handleSendMessage() {
    if (!input.trim() || !session || loading) return

    const userMessage: ChatMessage = {
      id: String(messageIdRef.current++),
      from: "user",
      text: input,
      timestamp: Date.now(),
    }

    const prompt = input
    setMessages((prev) => [...prev, userMessage])
    setInput("")
    setLoading(true)
    setError("")

    try {
      const response = await queryChat(
        session.companyId,
        { prompt, session_id: chatSessionId },
        sessionAuth(session),
      )

      if (response.session_id) setChatSessionId(response.session_id)

      setMessages((prev) => [
        ...prev,
        {
          id: String(messageIdRef.current++),
          from: "assistant",
          text: response.response,
          timestamp: Date.now(),
        },
      ])
    } catch (err) {
      if (err instanceof APIError) {
        setError(`Error: ${err.detail}`)
      } else if (err instanceof Error) {
        setError(err.message)
      } else {
        setError("Failed to send message")
      }
    } finally {
      setLoading(false)
    }
  }

  function handleReset() {
    if (confirm("Clear all chat history? This cannot be undone.")) {
      setMessages([])
      setChatSessionId(undefined)
      if (session?.companyId) {
        const storageKey = `${STORAGE_KEY}_${session.companyId}`
        localStorage.removeItem(storageKey)
      }
      messageIdRef.current = 0
    }
  }

  function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault()
      handleSendMessage()
    }
  }

  const logoUrl = session ? getCompanyLogoUrl(session.companyId) : undefined
  const companyInitial = session?.companyName?.slice(0, 1).toUpperCase() ?? "C"

  return (
    <MessageScrollerProvider>
      <div className="flex flex-col gap-4 h-[calc(100vh-120px)]">
        {error && (
          <Alert variant="destructive">
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        <Card className="flex-1 flex flex-col gap-0 overflow-hidden">
          <CardHeader className="gap-1 border-b">
            <CardTitle>Chat with {session?.companyName || "AI"}</CardTitle>
            <CardDescription>
              Ask questions about your company knowledge base
            </CardDescription>
            <div className="absolute right-4 top-4">
              <Button
                variant="outline"
                size="icon"
                onClick={handleReset}
                disabled={loading || messages.length === 0}
                aria-label="Clear chat history"
              >
                <RotateCw className="h-4 w-4" />
              </Button>
            </div>
          </CardHeader>

          <CardContent className="flex-1 overflow-hidden p-0">
            {messages.length === 0 ? (
              <div className="flex h-full items-center justify-center p-8">
                <div className="flex flex-col items-center gap-4 text-center max-w-sm">
                  <div className="rounded-full bg-muted p-4">
                    <MessageCircleDashed className="h-8 w-8 text-muted-foreground" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-lg">Start a conversation</h3>
                    <p className="text-sm text-muted-foreground mt-1">
                      Ask me anything about your company. Your chat history is saved locally.
                    </p>
                  </div>
                </div>
              </div>
            ) : (
              <MessageScroller>
                <MessageScrollerViewport>
                  <MessageScrollerContent
                    aria-busy={loading}
                    className="p-4 space-y-4"
                  >
                    {messages.map((msg) => (
                      <Message
                        key={msg.id}
                        align={msg.from === "user" ? "end" : "start"}
                      >
                        <MessageAvatar>
                          <Avatar>
                            {msg.from === "assistant" ? (
                              <>
                                {logoUrl && <AvatarImage src={logoUrl} alt={session?.companyName} />}
                                <AvatarFallback>{companyInitial}</AvatarFallback>
                              </>
                            ) : (
                              <AvatarFallback>U</AvatarFallback>
                            )}
                          </Avatar>
                        </MessageAvatar>
                        <MessageContent>
                          <Bubble variant={msg.from === "user" ? "default" : "muted"}>
                            <BubbleContent className="whitespace-pre-wrap">
                              {msg.text}
                            </BubbleContent>
                          </Bubble>
                        </MessageContent>
                      </Message>
                    ))}
                    {loading && (
                      <Marker role="status">
                        <MarkerContent className="shimmer">
                          <Loader2 className="h-4 w-4 animate-spin inline mr-2" />
                          <span className="font-medium">{session?.companyName}</span> is thinking...
                        </MarkerContent>
                      </Marker>
                    )}
                  </MessageScrollerContent>
                </MessageScrollerViewport>
                <MessageScrollerButton />
              </MessageScroller>
            )}
          </CardContent>

          <CardFooter className="border-t p-4">
            <form
              onSubmit={(e) => {
                e.preventDefault()
                handleSendMessage()
              }}
              className="w-full"
            >
              <InputGroup>
                <textarea
                  ref={textareaRef}
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  onKeyDown={handleKeyDown}
                  placeholder="Type your message..."
                  disabled={loading}
                  rows={1}
                  className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none min-h-[44px] max-h-32"
                  style={{ height: 'auto' }}
                  onInput={(e) => {
                    const target = e.target as HTMLTextAreaElement
                    target.style.height = 'auto'
                    target.style.height = `${Math.min(target.scrollHeight, 128)}px`
                  }}
                />
                <InputGroupAddon align="block-end" className="pt-1">
                  <InputGroupButton
                    type="submit"
                    variant="default"
                    size="icon-sm"
                    disabled={!input.trim() || loading}
                    className="ml-auto"
                  >
                    {loading ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <Send className="h-4 w-4" />
                    )}
                    <span className="sr-only">Send</span>
                  </InputGroupButton>
                </InputGroupAddon>
              </InputGroup>
            </form>
          </CardFooter>
        </Card>
      </div>
    </MessageScrollerProvider>
  )
}
