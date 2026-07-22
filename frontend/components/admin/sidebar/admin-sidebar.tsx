"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import type { ComponentProps } from "react"
import { ShieldCheck } from "lucide-react"

import { APP_NAME } from "@/lib/constants"
import { ADMIN_NAV_GROUPS } from "@/components/admin/sidebar/admin-nav"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
} from "@/components/ui/sidebar"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

function isItemActive(pathname: string, href: string) {
  if (href === "/admin") return pathname === "/admin"
  return pathname === href || pathname.startsWith(href + "/")
}

export function AdminSidebar({ ...props }: ComponentProps<typeof Sidebar>) {
  const pathname = usePathname()

  return (
    <Sidebar collapsible="icon" {...props}>
      <SidebarHeader>
        <div className="flex items-center gap-2 px-2 py-1.5 text-sm font-semibold">
          <div className="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
            <ShieldCheck className="h-4 w-4" />
          </div>
          <span className="group-data-[state=collapsed]:hidden">{APP_NAME} Admin</span>
        </div>
      </SidebarHeader>
      <SidebarContent>
        {ADMIN_NAV_GROUPS.map((group) => (
          <SidebarGroup key={group.label}>
            <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
            <SidebarGroupContent>
              <SidebarMenu>
                {group.items.map((item) => {
                  const active = isItemActive(pathname, item.href)
                  return (
                    <SidebarMenuItem key={`${group.label}-${item.href}`}>
                      <SidebarMenuButton
                        render={<Link href={item.href} />}
                        className={cn(
                          "transition-colors",
                          active && "bg-accent text-accent-foreground",
                        )}
                        tooltip={item.title}
                      >
                        <item.icon className="h-4 w-4 flex-shrink-0" />
                        <span className="group-data-[state=collapsed]:hidden">{item.title}</span>
                      </SidebarMenuButton>
                    </SidebarMenuItem>
                  )
                })}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        ))}
      </SidebarContent>
      <SidebarFooter>
        <Badge variant="destructive" className="mx-2 w-fit group-data-[state=collapsed]:hidden">
          Admin
        </Badge>
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
  )
}
