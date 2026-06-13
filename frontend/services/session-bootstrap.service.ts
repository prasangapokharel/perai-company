import type { AuthSession } from "@/features/auth/hooks"
import { saveAuthSession, sessionAuth } from "@/features/auth/hooks"
import { createAPIKey, listAPIKeys } from "@/services/api-key.service"

function readStoredApiKey(companyId: number): string {
  if (typeof window === "undefined") return ""
  return localStorage.getItem(`perai_api_key_${companyId}`) ?? ""
}

export async function ensureDefaultApiKey(session: AuthSession): Promise<AuthSession> {
  if (session.apiKey) return session

  const stored = readStoredApiKey(session.companyId)
  if (stored) {
    const updated = { ...session, apiKey: stored }
    saveAuthSession(updated)
    return updated
  }

  const auth = sessionAuth(session)
  const keys = await listAPIKeys(session.companyId, auth)
  const active = keys.filter((k) => k.status === "active")

  if (active.length > 0) {
    return session
  }

  const expiry = new Date()
  expiry.setDate(expiry.getDate() + 90)
  const created = await createAPIKey(
    session.companyId,
    { name: "default", expiry_date: expiry.toISOString() },
    auth,
  )

  const updated: AuthSession = {
    ...session,
    apiKey: created.key,
  }
  saveAuthSession(updated)
  return updated
}

export async function createEmbedApiKey(session: AuthSession): Promise<AuthSession> {
  const auth = sessionAuth(session)
  const expiry = new Date()
  expiry.setDate(expiry.getDate() + 90)
  const created = await createAPIKey(
    session.companyId,
    { name: "widget-embed", expiry_date: expiry.toISOString() },
    auth,
  )
  const updated: AuthSession = { ...session, apiKey: created.key }
  saveAuthSession(updated)
  return updated
}

export function saveEmbedApiKey(session: AuthSession, apiKey: string): AuthSession {
  const updated = { ...session, apiKey: apiKey.trim() }
  saveAuthSession(updated)
  return updated
}
