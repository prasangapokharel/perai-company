import {
  LayoutDashboard,
  MessageSquare,
  Zap,
  Key,
  Ticket,
  Bot,
  Settings,
  Code2,
  Puzzle,
  Wallet,
  BarChart3,
  History,
  type LucideIcon,
} from "lucide-react"

export const APP_NAME = "Perai"

export type NavItem = {
  title: string
  href: string
  icon: LucideIcon
}

export type NavGroup = {
  label: string
  items: NavItem[]
}

export const COMPANY_NAV_GROUPS: NavGroup[] = [
  {
    label: "Workspace",
    items: [
      { title: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
      { title: "Finetune", href: "/finetune", icon: Zap },
      { title: "Models", href: "/models", icon: Bot },
      { title: "Chat", href: "/chat", icon: MessageSquare },
      { title: "Sessions", href: "/sessions", icon: History },
    ],
  },
  {
    label: "Billing",
    items: [
      { title: "Balance", href: "/balance", icon: Wallet },
      { title: "Usage", href: "/usages", icon: BarChart3 },
    ],
  },
  {
    label: "Develop",
    items: [
      { title: "Integration", href: "/integration", icon: Code2 },
      { title: "Widget", href: "/widget", icon: Puzzle },
      { title: "API", href: "/api", icon: Key },
    ],
  },
  {
    label: "Account",
    items: [
      { title: "Settings", href: "/settings", icon: Settings },
      { title: "Tickets", href: "/ticket", icon: Ticket },
    ],
  },
]

/** @deprecated use COMPANY_NAV_GROUPS */
export const COMPANY_NAV: NavItem[] = COMPANY_NAV_GROUPS.flatMap((g) => g.items)
