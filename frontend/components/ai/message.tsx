import { cn } from "@/lib/utils"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { MarkdownContent } from "@/components/ai/markdown-content"

export type MessageProps = {
  from: "user" | "assistant"
  children?: React.ReactNode
  className?: string
  avatarSrc?: string
  avatarFallback?: string
}

export function Message({ from, children, className, avatarSrc, avatarFallback }: MessageProps) {
  const isUser = from === "user"
  const fallback = avatarFallback ?? (isUser ? "U" : "A")

  return (
    <div
      className={cn(
        "flex gap-3 animate-in fade-in slide-in-from-bottom-3",
        isUser && "flex-row-reverse",
        className
      )}
    >
      <Avatar className="h-8 w-8 flex-shrink-0">
        {avatarSrc ? <AvatarImage src={avatarSrc} alt={fallback} /> : null}
        <AvatarFallback className={isUser ? "bg-primary text-primary-foreground" : "bg-muted"}>
          {fallback.slice(0, 2).toUpperCase()}
        </AvatarFallback>
      </Avatar>
      <div
        className={cn(
          "flex-1 rounded-lg px-4 py-2",
          isUser ? "max-w-xs lg:max-w-md" : "max-w-[min(42rem,92%)]",
          isUser
            ? "bg-primary text-primary-foreground"
            : "bg-muted text-foreground"
        )}
      >
        {children}
      </div>
    </div>
  )
}

export type MessageContentProps = {
  children?: React.ReactNode
  className?: string
  markdown?: boolean
}

export function MessageContent({ children, className, markdown }: MessageContentProps) {
  if (markdown && typeof children === "string") {
    return <MarkdownContent className={cn("text-sm", className)}>{children}</MarkdownContent>
  }

  return (
    <div className={cn("text-sm whitespace-pre-wrap break-words", className)}>
      {children}
    </div>
  )
}
