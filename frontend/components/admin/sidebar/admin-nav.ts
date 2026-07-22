import {
  LayoutDashboard,
  Building2,
  Wallet,
  Ticket,
  Key,
  CreditCard,
  type LucideIcon,
} from "lucide-react"

export type AdminNavItem = {
  title: string
  href: string
  icon: LucideIcon
}

export type AdminNavGroup = {
  label: string
  items: AdminNavItem[]
}

export const ADMIN_NAV_GROUPS: AdminNavGroup[] = [
  {
    label: "Dashboard",
    items: [{ title: "Overview", href: "/admin", icon: LayoutDashboard }],
  },
  {
    label: "Management",
    items: [
      { title: "Companies", href: "/admin/companies", icon: Building2 },
      { title: "Tickets", href: "/admin/tickets", icon: Ticket },
      { title: "API Keys", href: "/admin/apikeys", icon: Key },
    ],
  },
  {
    label: "Billing",
    items: [
      { title: "Payments", href: "/admin/payments", icon: CreditCard },
      { title: "Balances", href: "/admin/companies", icon: Wallet },
    ],
  },
]
