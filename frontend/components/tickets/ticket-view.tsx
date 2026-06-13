"use client"

import * as React from "react"

import Link from "next/link"

import { ArrowLeft } from "lucide-react"

import { buttonVariants } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyTitle } from "@/components/ui/empty"
import { cn } from "@/lib/utils"
import type { Ticket, TicketHistory } from "@/services/ticket.service"

type TicketViewProps = {
  ticket: Ticket
  history: TicketHistory
}

export function TicketView({ ticket, history }: TicketViewProps) {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Link className={cn(buttonVariants({ variant: "outline", size: "sm" }))} href="/ticket">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back
        </Link>
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Ticket #{ticket.id}</h1>
          <p className="text-muted-foreground">Support request details</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{ticket.issue}</CardTitle>
          <CardDescription>Created {new Date(ticket.created_at).toLocaleString()}</CardDescription>
        </CardHeader>
        <CardContent className="flex flex-wrap gap-2">
          <Badge variant="secondary">{ticket.category}</Badge>
          <Badge variant={ticket.status === "open" ? "default" : "outline"}>{ticket.status}</Badge>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>History</CardTitle>
          <CardDescription>Open and close events</CardDescription>
        </CardHeader>
        <CardContent>
          {history.records.length ? (
            <div className="space-y-3">
              {history.records.map((record) => (
                <div key={record.id} className="rounded-lg border p-4 text-sm">
                  <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary">Opened {new Date(record.opened_at).toLocaleString()}</Badge>
                    <Badge variant={record.closed_at ? "outline" : "default"}>
                      {record.closed_at ? `Closed ${new Date(record.closed_at).toLocaleString()}` : "Still open"}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <Empty>
              <EmptyContent>
                <EmptyHeader>
                  <EmptyTitle>No history yet</EmptyTitle>
                  <EmptyDescription>This ticket has no history records.</EmptyDescription>
                </EmptyHeader>
              </EmptyContent>
            </Empty>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
