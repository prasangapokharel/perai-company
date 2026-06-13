"use client"

import * as React from "react"
import { useRouter } from "next/navigation"

import { loadAuthSession } from "@/features/auth/hooks"
import { hasAuthCookie, setAuthCookie } from "@/lib/auth-cookie"

export function RequireAuth({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const [allowed, setAllowed] = React.useState(false)

  React.useEffect(() => {
    const session = loadAuthSession()
    const hasSession = !!(session?.accessToken || session?.apiKey)
    if (!hasSession) {
      router.replace("/login")
      return
    }
    if (!hasAuthCookie()) {
      setAuthCookie()
    }
    setAllowed(true)
  }, [router])

  if (!allowed) {
    return (
      <div className="flex h-96 items-center justify-center">
        <p className="text-muted-foreground">Loading...</p>
      </div>
    )
  }

  return <>{children}</>
}
