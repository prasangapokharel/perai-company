"use client"

import { useEffect, useState, useRef } from "react"
import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Textarea } from "@/components/ui/textarea"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Card } from "@/components/ui/card"
import {
  Conversation,
  ConversationContent,
  ConversationEmptyState,
  ConversationScrollButton,
} from "@/components/chat/conversations"
import { Message, MessageContent } from "@/components/ai/message"
import { loadAuthSession, sessionAuth } from "@/features/auth/hooks"
import { queryChat } from "@/services/chat.service"
import { getCompanyLogoUrl } from "@/services/file.service"
import { APIError } from "@/lib/api-client"
import { Send, Loader2 } from "lucide-react"

type ChatMessage = {
  id: string
  from: "user" | "assistant"
  text: string
}

export default function ChatPage() {
  const router = useRouter()
  const [messages, setMessages] = useState<ChatMessage[]>([])
  const [input, setInput] = useState("")
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState("")
  const [session, setSession] = useState<ReturnType<typeof loadAuthSession>>(null)
  const [chatSessionId, setChatSessionId] = useState<string>()
  const messageIdRef = useRef(0)

  useEffect(() => {
    const sess = loadAuthSession()
    if (!sess?.accessToken && !sess?.apiKey) {
      router.push("/login")
      return
    }
    setSession(sess)
  }, [router])

  async function handleSendMessage() {
    if (!input.trim() || !session || loading) return

    const userMessage: ChatMessage = {
      id: String(messageIdRef.current++),
      from: "user",
      text: input,
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

  function handleKeyDown(e: React.KeyboardEvent) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault()
      handleSendMessage()
    }
  }

  const logoUrl = session ? getCompanyLogoUrl(session.companyId) : undefined
  const companyInitial = session?.companyName?.slice(0, 1).toUpperCase() ?? "C"

  return (
    <div className="flex flex-col h-[calc(100vh-120px)] gap-4">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Chat</h1>
        <p className="text-muted-foreground">
          Ask questions about your company knowledge base
        </p>
        <Button variant="outline" size="sm" className="mt-3" onClick={() => router.push("/models")}>
          View model
        </Button>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card className="flex-1 flex flex-col overflow-hidden">
        {messages.length === 0 ? (
          <Conversation>
            <ConversationEmptyState
              title="Start a conversation"
              description="Ask me anything about your company"
            />
          </Conversation>
        ) : (
          <Conversation>
            <ConversationContent>
              {messages.map((msg) => (
                <Message
                  from={msg.from}
                  key={msg.id}
                  avatarSrc={msg.from === "assistant" ? logoUrl : undefined}
                  avatarFallback={msg.from === "assistant" ? companyInitial : "U"}
                >
                  <MessageContent markdown={msg.from === "assistant"}>
                    {msg.text}
                  </MessageContent>
                </Message>
              ))}
              {loading && (
                <Message
                  from="assistant"
                  avatarSrc={logoUrl}
                  avatarFallback={companyInitial}
                >
                  <MessageContent className="flex items-center gap-2">
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Thinking...
                  </MessageContent>
                </Message>
              )}
            </ConversationContent>
            <ConversationScrollButton />
          </Conversation>
        )}

        <div className="border-t p-4 bg-background">
          <div className="flex gap-2 items-end">
            <Textarea
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Type your message..."
              disabled={loading}
              rows={2}
              className="flex-1 min-h-[44px] max-h-32 resize-none"
            />
            <Button
              onClick={handleSendMessage}
              disabled={loading || !input.trim()}
              size="icon"
            >
              {loading ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <Send className="h-4 w-4" />
              )}
            </Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
