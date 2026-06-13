export const AUTH_COOKIE_NAME = "perai_auth"
export const AUTH_COOKIE_MAX_AGE = 60 * 60 * 24 * 7

export function setAuthCookie() {
  if (typeof document === "undefined") return
  document.cookie = `${AUTH_COOKIE_NAME}=1; path=/; SameSite=Lax; max-age=${AUTH_COOKIE_MAX_AGE}`
}

export function clearAuthCookie() {
  if (typeof document === "undefined") return
  document.cookie = `${AUTH_COOKIE_NAME}=; path=/; max-age=0`
}

export function hasAuthCookie(): boolean {
  if (typeof document === "undefined") return false
  return document.cookie.split(";").some((part) => part.trim().startsWith(`${AUTH_COOKIE_NAME}=1`))
}
