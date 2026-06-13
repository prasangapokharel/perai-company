import type { AuthSession } from "@/features/auth/hooks"
import { saveAuthSession, sessionAuth } from "@/features/auth/hooks"
import { createAPIKey, listAPIKeys } from "@/services/api-key.service"

export async function ensureDefaultApiKey(session: AuthSession): Promise<AuthSession> {
  if (session.apiKey) return session

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
