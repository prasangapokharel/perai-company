import type { ReactNode } from "react"

import { RequireAuth } from "@/components/auth/require-auth"
import { CompanyShell } from "@/components/layout/company-shell"

export default function CompanyLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <CompanyShell title="Company">{children}</CompanyShell>
    </RequireAuth>
  )
}
