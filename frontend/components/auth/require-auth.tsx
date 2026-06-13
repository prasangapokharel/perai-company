"use client"

import * as React from "react"
import { useRouter } from "next/navigation"

import { isAuthenticatedSession, loadAuthSession } from "@/features/auth/hooks"

export function RequireAuth({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const [allowed, setAllowed] = React.useState(false)

  React.useEffect(() => {
    const session = loadAuthSession()
    if (!isAuthenticatedSession(session)) {
      router.replace("/login")
      return
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
