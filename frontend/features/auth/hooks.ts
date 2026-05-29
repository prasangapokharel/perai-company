"use client"

import * as React from "react"

export type AuthSession = {
  companyId: number
  apiKey: string
  companyName?: string
}

const AUTH_SESSION_KEY = "perai_auth_session"

export function loadAuthSession(): AuthSession | null {
  if (typeof window === "undefined") return null
  const raw = window.sessionStorage.getItem(AUTH_SESSION_KEY)
  return raw ? (JSON.parse(raw) as AuthSession) : null
}

export function saveAuthSession(session: AuthSession) {
  window.sessionStorage.setItem(AUTH_SESSION_KEY, JSON.stringify(session))
}

export { saveAuthSession as persistAuthSession }

export function clearAuthSession() {
  window.sessionStorage.removeItem(AUTH_SESSION_KEY)
}

export function useAuthSession() {
  const [session, setSession] = React.useState<AuthSession | null>(null)

  React.useEffect(() => {
    setSession(loadAuthSession())
  }, [])

  const refresh = React.useCallback(() => {
    setSession(loadAuthSession())
  }, [])

  return {
    session,
    isReady: session !== null,
    refresh,
    clear: () => {
      clearAuthSession()
      setSession(null)
    },
  }
}
