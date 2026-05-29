import { Card, CardContent, CardHeader } from "@/components/ui/card"

export function DashboardShell({ title }: { title: string }) {
  return (
    <Card>
      <CardHeader>{title}</CardHeader>
      <CardContent />
    </Card>
  )
}
