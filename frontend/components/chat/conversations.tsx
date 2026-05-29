"use client"

import { ArrowDownIcon } from "lucide-react"
import type { ComponentProps } from "react"
import { useCallback, useEffect, useRef, useState } from "react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import { Message, MessageContent } from "@/components/ai/message"

// Context for scroll-to-bottom functionality
import { createContext, useContext } from "react"

type StickToBottomContextType = {
  isAtBottom: boolean
  scrollToBottom: () => void
}

const StickToBottomContext = createContext<StickToBottomContextType | undefined>(undefined)

export function useStickToBottomContext() {
  const context = useContext(StickToBottomContext)
  if (!context) {
    throw new Error("useStickToBottomContext must be used within StickToBottom")
  }
  return context
}

export type ConversationProps = ComponentProps<"div">

export const Conversation = ({ className, children, ...props }: ConversationProps) => {
  const containerRef = useRef<HTMLDivElement>(null)
  const [isAtBottom, setIsAtBottom] = useState(true)

  const scrollToBottom = useCallback(() => {
    if (containerRef.current) {
      containerRef.current.scrollTop = containerRef.current.scrollHeight
      setIsAtBottom(true)
    }
  }, [])

  useEffect(() => {
    const container = containerRef.current
    if (!container) return

    const handleScroll = () => {
      const { scrollTop, scrollHeight, clientHeight } = container
      const atBottom = scrollHeight - scrollTop - clientHeight < 50
      setIsAtBottom(atBottom)
    }

    container.addEventListener("scroll", handleScroll)
    return () => container.removeEventListener("scroll", handleScroll)
  }, [])

  // Auto-scroll when content changes
  useEffect(() => {
    if (isAtBottom) {
      scrollToBottom()
    }
  }, [children, isAtBottom, scrollToBottom])

  return (
    <StickToBottomContext.Provider value={{ isAtBottom, scrollToBottom }}>
      <div
        ref={containerRef}
        className={cn("relative flex-1 overflow-y-auto", className)}
        role="log"
        {...props}
      >
        {children}
      </div>
    </StickToBottomContext.Provider>
  )
}

export type ConversationContentProps = ComponentProps<"div">

export const ConversationContent = ({ className, ...props }: ConversationContentProps) => (
  <div className={cn("flex flex-col gap-4 p-4", className)} {...props} />
)

export type ConversationEmptyStateProps = ComponentProps<"div"> & {
  title?: string
  description?: string
  icon?: React.ReactNode
}

export const ConversationEmptyState = ({
  className,
  title = "No messages yet",
  description = "Start a conversation to see messages here",
  icon,
  children,
  ...props
}: ConversationEmptyStateProps) => (
  <div
    className={cn(
      "flex size-full flex-col items-center justify-center gap-3 p-8 text-center",
      className
    )}
    {...props}
  >
    {children ?? (
      <>
        {icon && <div className="text-muted-foreground">{icon}</div>}
        <div className="space-y-1">
          <h3 className="font-medium text-sm">{title}</h3>
          {description && (
            <p className="text-muted-foreground text-sm">{description}</p>
          )}
        </div>
      </>
    )}
  </div>
)

export type ConversationScrollButtonProps = ComponentProps<typeof Button>

export const ConversationScrollButton = ({
  className,
  ...props
}: ConversationScrollButtonProps) => {
  const { isAtBottom, scrollToBottom } = useStickToBottomContext()

  const handleScrollToBottom = useCallback(() => {
    scrollToBottom()
  }, [scrollToBottom])

  return !isAtBottom ? (
    <Button
      className={cn(
        "absolute bottom-4 left-[50%] translate-x-[-50%] rounded-full",
        className
      )}
      onClick={handleScrollToBottom}
      size="icon"
      type="button"
      variant="outline"
      {...props}
    >
      <ArrowDownIcon className="size-4" />
    </Button>
  ) : null
}

/** Demo component for preview */
export default function ConversationDemo() {
  const messages = [
    { id: "1", from: "user" as const, text: "Hello, how are you?" },
    {
      id: "2",
      from: "assistant" as const,
      text: "I'm good, thank you! How can I assist you today?",
    },
    {
      id: "3",
      from: "user" as const,
      text: "I'm looking for information about your services.",
    },
    {
      id: "4",
      from: "assistant" as const,
      text: "Sure! We offer a variety of AI solutions. What are you interested in?",
    },
  ]

  return (
    <Conversation className="relative size-full p-4">
      <ConversationContent>
        {messages.map((msg) => (
          <Message from={msg.from} key={msg.id}>
            <MessageContent>{msg.text}</MessageContent>
          </Message>
        ))}
      </ConversationContent>
      <ConversationScrollButton />
    </Conversation>
  )
}
