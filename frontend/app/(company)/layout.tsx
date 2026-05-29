import type { ReactNode } from "react"

import { CompanyShell } from "@/components/layout/company-shell"

export default function CompanyLayout({ children }: { children: ReactNode }) {
  return <CompanyShell title="Company">{children}</CompanyShell>
}
