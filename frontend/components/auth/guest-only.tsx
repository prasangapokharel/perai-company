"use client"

import * as React from "react"
import { useRouter } from "next/navigation"

import { isAuthenticatedSession, loadAuthSession } from "@/features/auth/hooks"

export function GuestOnly({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const [allowed, setAllowed] = React.useState(false)

  React.useEffect(() => {
    if (isAuthenticatedSession(loadAuthSession())) {
      router.replace("/dashboard")
      return
    }
    setAllowed(true)
  }, [router])

  if (!allowed) {
    return (
      <div className="flex min-h-svh items-center justify-center">
        <p className="text-muted-foreground">Redirecting...</p>
      </div>
    )
  }

  return <>{children}</>
}
