import { GuestOnly } from "@/components/auth/guest-only"
import { SignupForm } from "@/components/auth/signup-form"

export default function Page() {
  return (
    <GuestOnly>
      <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div className="w-full max-w-sm">
          <SignupForm />
        </div>
      </div>
    </GuestOnly>
  )
}
