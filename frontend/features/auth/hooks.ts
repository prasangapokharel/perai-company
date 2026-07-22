"use client"

import * as React from "react"

import type { ApiAuth } from "@/lib/api-auth"

import { clearAuthCookie, setAuthCookie } from "@/lib/auth-cookie"

export type AuthSession = {
  companyId: number
  apiKey: string
  accessToken?: string
  balance?: string
  currency?: string
  companyName?: string
  isAdmin?: boolean
}

const AUTH_SESSION_KEY = "perai_auth_session"

function apiKeyStorageKey(companyId: number) {
  return `perai_api_key_${companyId}`
}

export function loadAuthSession(): AuthSession | null {
  if (typeof window === "undefined") return null
  const raw = window.sessionStorage.getItem(AUTH_SESSION_KEY)
  if (!raw) return null
  const session = JSON.parse(raw) as AuthSession
  if (!session.apiKey) {
    const stored = localStorage.getItem(apiKeyStorageKey(session.companyId))
    if (stored) session.apiKey = stored
  }
  return session
}

export function saveAuthSession(session: AuthSession) {
  window.sessionStorage.setItem(AUTH_SESSION_KEY, JSON.stringify(session))
  if (session.apiKey) {
    localStorage.setItem(apiKeyStorageKey(session.companyId), session.apiKey)
  }
  if (session.accessToken || session.apiKey) {
    setAuthCookie()
  }
}

export { saveAuthSession as persistAuthSession }

export function clearAuthSession() {
  window.sessionStorage.removeItem(AUTH_SESSION_KEY)
  clearAuthCookie()
}

export function sessionAuth(session: AuthSession): ApiAuth {
  return {
    apiKey: session.apiKey || undefined,
    accessToken: session.accessToken,
  }
}

export function isAuthenticatedSession(session: AuthSession | null): boolean {
  return !!session && (!!session.accessToken || !!session.apiKey)
}

export function useAuthSession() {
  const [session, setSession] = React.useState<AuthSession | null>(() => loadAuthSession())
  const [checked, setChecked] = React.useState(false)

  React.useEffect(() => {
    setSession(loadAuthSession())
    setChecked(true)
  }, [])

  const refresh = React.useCallback(() => {
    setSession(loadAuthSession())
  }, [])

  return {
    session,
    checked,
    isAuthenticated: isAuthenticatedSession(session),
    refresh,
    clear: () => {
      clearAuthSession()
      setSession(null)
    },
  }
}
