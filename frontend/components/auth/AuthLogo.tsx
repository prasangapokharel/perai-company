import Image from "next/image"
import Link from "next/link"

import { APP_NAME } from "@/lib/constants"

export function AuthLogo() {
  return (
    <Link href="/login" className="flex flex-col items-center gap-2 font-medium">
      <Image
        src="/images/logo/logo.svg"
        alt={APP_NAME}
        width={40}
        height={40}
        className="h-10 w-10"
        priority
      />
      <span className="text-lg font-semibold tracking-tight">{APP_NAME}</span>
    </Link>
  )
}
