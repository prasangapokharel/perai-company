"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import type { ComponentProps } from "react"

import { APP_NAME, COMPANY_NAV } from "@/lib/constants"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
} from "@/components/ui/sidebar"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

export function AppSidebar({ ...props }: ComponentProps<typeof Sidebar>) {
  const pathname = usePathname()

  return (
    <Sidebar collapsible="icon" {...props}>
      <SidebarHeader>
        <div className="px-2 py-1.5 text-sm font-semibold flex items-center gap-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-primary-foreground font-bold">
            P
          </div>
          <span className="group-data-[state=collapsed]:hidden">{APP_NAME}</span>
        </div>
      </SidebarHeader>
      <SidebarContent>
        <SidebarMenu>
          {COMPANY_NAV.map((item) => {
            const isActive = pathname === item.href || pathname.startsWith(item.href + "/")
            return (
              <SidebarMenuItem key={item.href}>
                <SidebarMenuButton
                  render={<Link href={item.href} />}
                  className={cn(
                    "transition-colors",
                    isActive && "bg-accent text-accent-foreground"
                  )}
                  tooltip={item.title}
                >
                  <item.icon className="h-4 w-4 flex-shrink-0" />
                  <span className="group-data-[state=collapsed]:hidden">
                    {item.title}
                  </span>
                </SidebarMenuButton>
              </SidebarMenuItem>
            )
          })}
        </SidebarMenu>
      </SidebarContent>
      <SidebarFooter>
        <Badge variant="secondary" className="mx-2 w-fit group-data-[state=collapsed]:hidden">
          Company
        </Badge>
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
  )
}
