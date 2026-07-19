import { NextResponse } from "next/server"
import type { NextRequest } from "next/server"

import { AUTH_COOKIE_NAME } from "@/lib/auth-cookie"

const GUEST_PATHS = ["/login", "/register"]

const PROTECTED_PREFIXES = [
  "/dashboard",
  "/finetune",
  "/models",
  "/chat",
  "/sessions",
  "/balance",
  "/usages",
  "/integration",
  "/widget",
  "/api",
  "/settings",
  "/ticket",
  "/profile",
]

function isGuestPath(pathname: string) {
  return GUEST_PATHS.some((path) => pathname === path || pathname.startsWith(`${path}/`))
}

function isProtectedPath(pathname: string) {
  return PROTECTED_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`),
  )
}

function isAuthenticated(request: NextRequest) {
  return request.cookies.get(AUTH_COOKIE_NAME)?.value === "1"
}

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl
  const authenticated = isAuthenticated(request)

  if (pathname === "/") {
    const target = authenticated ? "/dashboard" : "/login"
    return NextResponse.redirect(new URL(target, request.url))
  }

  if (authenticated && isGuestPath(pathname)) {
    return NextResponse.redirect(new URL("/dashboard", request.url))
  }

  if (!authenticated && isProtectedPath(pathname)) {
    const loginUrl = new URL("/login", request.url)
    loginUrl.searchParams.set("next", pathname)
    return NextResponse.redirect(loginUrl)
  }

  return NextResponse.next()
}

export const config = {
  matcher: [
    "/",
    "/login",
    "/register",
    "/dashboard/:path*",
    "/finetune/:path*",
    "/models/:path*",
    "/chat/:path*",
    "/sessions/:path*",
    "/balance/:path*",
    "/usages/:path*",
    "/integration/:path*",
    "/widget/:path*",
    "/api/:path*",
    "/settings/:path*",
    "/ticket/:path*",
    "/profile/:path*",
  ],
}
