"use client"

import * as React from "react"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { useAuthSession, sessionAuth } from "@/features/auth/hooks"
import { APIError } from "@/lib/api-client"
import { createOrUpdateCompanySettings, getCompanySettings } from "@/services/companySettings.service"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { deleteCompanySettings } from "@/services/companySettings.service"

export function CompanySettingsPanel() {
  const { session } = useAuthSession()
  const [language, setLanguage] = React.useState("english")
  const [tone, setTone] = React.useState("formal")
  const [maxTokens, setMaxTokens] = React.useState("1000")
  const [status, setStatus] = React.useState("")
  const [deleteOpen, setDeleteOpen] = React.useState(false)

  React.useEffect(() => {
    async function load() {
      if (!session) return
      try {
        const data = await getCompanySettings(session.companyId, sessionAuth(session))
        setLanguage(data.language)
        setTone(data.tone)
        setMaxTokens(String(data.max_tokens))
      } catch (err) {
        if (err instanceof APIError) setStatus(err.detail)
      }
    }

    load()
  }, [session])

  async function handleSave() {
    if (!session) return
    setStatus("Saving...")
    try {
      await createOrUpdateCompanySettings(
        session.companyId,
        { language: language as "english" | "nepali", tone: tone as any, max_tokens: Number(maxTokens) },
        sessionAuth(session),
      )
      setStatus("Saved")
    } catch (err) {
      if (err instanceof APIError) setStatus(err.detail)
      else setStatus("Failed to save settings")
    }
  }

  async function handleDelete() {
    if (!session) return
    setStatus("Deleting...")
    try {
      await deleteCompanySettings(session.companyId, sessionAuth(session))
      setStatus("Deleted")
      setDeleteOpen(false)
    } catch (err) {
      if (err instanceof APIError) setStatus(err.detail)
      else setStatus("Failed to delete settings")
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Company Settings</CardTitle>
        <CardDescription>AI tone, language, and token limits</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <FieldGroup>
          <Field>
            <FieldLabel>Language</FieldLabel>
            <Select value={language} onValueChange={setLanguage}>
              <SelectTrigger><SelectValue placeholder="Language" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="english">English</SelectItem>
                <SelectItem value="nepali">Nepali</SelectItem>
              </SelectContent>
            </Select>
          </Field>
          <Field>
            <FieldLabel>Tone</FieldLabel>
            <Select value={tone} onValueChange={setTone}>
              <SelectTrigger><SelectValue placeholder="Tone" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="formal">Formal</SelectItem>
                <SelectItem value="casual">Casual</SelectItem>
                <SelectItem value="friendly">Friendly</SelectItem>
                <SelectItem value="professional">Professional</SelectItem>
              </SelectContent>
            </Select>
          </Field>
          <Field>
            <FieldLabel>Max tokens</FieldLabel>
            <Input value={maxTokens} onChange={(e) => setMaxTokens(e.target.value)} inputMode="numeric" />
          </Field>
        </FieldGroup>
        <div className="flex items-center gap-3">
          <Button onClick={handleSave}>Save settings</Button>
          <Button variant="outline" onClick={() => setDeleteOpen(true)}>Reset defaults</Button>
          <span className="text-sm text-muted-foreground">{status}</span>
        </div>
      </CardContent>

      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Reset settings?</AlertDialogTitle>
            <AlertDialogDescription>
              This deletes custom company settings and restores defaults on next load.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="flex gap-2">
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete}>Reset</AlertDialogAction>
          </div>
        </AlertDialogContent>
      </AlertDialog>
    </Card>
  )
}
