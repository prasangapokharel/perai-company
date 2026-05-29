import { cn } from "@/lib/utils"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"

export type MessageProps = {
  from: "user" | "assistant"
  children?: React.ReactNode
  className?: string
}

export function Message({ from, children, className }: MessageProps) {
  const isUser = from === "user"

  return (
    <div
      className={cn(
        "flex gap-3 animate-in fade-in slide-in-from-bottom-3",
        isUser && "flex-row-reverse",
        className
      )}
    >
      <Avatar className="h-8 w-8 flex-shrink-0">
        <AvatarFallback className={isUser ? "bg-primary text-primary-foreground" : "bg-muted"}>
          {isUser ? "U" : "A"}
        </AvatarFallback>
      </Avatar>
      <div
        className={cn(
          "flex-1 rounded-lg px-4 py-2 max-w-xs lg:max-w-md",
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
}

export function MessageContent({ children, className }: MessageContentProps) {
  return (
    <div className={cn("text-sm whitespace-pre-wrap break-words", className)}>
      {children}
    </div>
  )
}
