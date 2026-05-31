import {
  LayoutDashboard,
  MessageSquare,
  Zap,
  Key,
  Ticket,
  User,
  Bot,
  Settings,
  type LucideIcon,
} from "lucide-react"

export const APP_NAME = "Perai"

export type NavItem = {
  title: string
  href: string
  icon: LucideIcon
}

export const COMPANY_NAV: NavItem[] = [
  { title: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { title: "Chat", href: "/chat", icon: MessageSquare },
  { title: "Models", href: "/models", icon: Bot },
  { title: "Finetune", href: "/finetune", icon: Zap },
  { title: "Settings", href: "/settings", icon: Settings },
  { title: "API", href: "/api", icon: Key },
  { title: "Tickets", href: "/ticket", icon: Ticket },
  { title: "Profile", href: "/profile", icon: User },
]
