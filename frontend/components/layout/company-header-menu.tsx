"use client"

import Link from "next/link"
import { useRouter } from "next/navigation"
import { LogOut, User } from "lucide-react"

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { clearAuthSession, useAuthSession } from "@/features/auth/hooks"
import { getCompanyLogoUrl } from "@/services/file.service"

function companyInitials(name?: string) {
  if (!name) return "C"
  return name
    .split(/\s+/)
    .map((part) => part[0])
    .join("")
    .slice(0, 2)
    .toUpperCase()
}

export function CompanyHeaderMenu() {
  const router = useRouter()
  const { session } = useAuthSession()

  if (!session) return null

  const logoUrl = getCompanyLogoUrl(session.companyId)
  const label = session.companyName ?? "Company"

  function handleLogout() {
    if (session?.companyId) {
      localStorage.removeItem(`perai_api_key_${session.companyId}`)
    }
    clearAuthSession()
    router.push("/login")
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        render={
          <Button
            variant="ghost"
            size="icon"
            className="size-9 rounded-full p-0"
            aria-label="Open account menu"
          />
        }
      >
        <Avatar className="size-9">
          <AvatarImage src={logoUrl} alt={label} />
          <AvatarFallback className="text-xs font-medium">
            {companyInitials(label)}
          </AvatarFallback>
        </Avatar>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" sideOffset={8} className="w-44">
        <DropdownMenuItem render={<Link href="/profile" />}>
          <User />
          Profile
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem variant="destructive" onClick={handleLogout}>
          <LogOut />
          Logout
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
