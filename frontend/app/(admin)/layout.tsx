import type { ReactNode } from "react"

import { RequireAdmin } from "@/components/auth/require-admin"
import { AdminShell } from "@/components/admin/admin-shell"

export default function AdminLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAdmin>
      <AdminShell title="Admin">{children}</AdminShell>
    </RequireAdmin>
  )
}
