"use client"

import * as React from "react"
import { useRouter } from "next/navigation"

import { loadAuthSession } from "@/features/auth/hooks"
import { hasAuthCookie, setAuthCookie } from "@/lib/auth-cookie"
import { getAuthMe } from "@/services/auth.service"

export function RequireAdmin({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const [state, setState] = React.useState<"checking" | "allowed" | "denied">("checking")

  React.useEffect(() => {
    const session = loadAuthSession()
    const hasSession = !!(session?.accessToken || session?.apiKey)
    if (!hasSession) {
      router.replace("/login")
      return
    }
    if (!hasAuthCookie()) setAuthCookie()

    // Trust the cached flag for a fast path, but always confirm with the server
    // so a stale/forged local flag can never unlock the admin console.
    getAuthMe({ apiKey: session!.apiKey, accessToken: session!.accessToken })
      .then((me) => {
        if (me.is_admin) {
          setState("allowed")
        } else {
          setState("denied")
          router.replace("/dashboard")
        }
      })
      .catch(() => {
        setState("denied")
        router.replace("/login")
      })
  }, [router])

  if (state !== "allowed") {
    return (
      <div className="flex h-96 items-center justify-center">
        <p className="text-muted-foreground">
          {state === "denied" ? "Redirecting..." : "Verifying admin access..."}
        </p>
      </div>
    )
  }

  return <>{children}</>
}
