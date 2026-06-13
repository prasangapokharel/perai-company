"use client"

import * as React from "react"

import Link from "next/link"

import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyTitle } from "@/components/ui/empty"
import { buttonVariants } from "@/components/ui/button"
import type { Ticket } from "@/services/ticket.service"

type TicketListProps = {
  tickets: Ticket[]
  title?: string
  description?: string
  emptyTitle?: string
  emptyDescription?: string
}

export function TicketList({
  tickets,
  title = "Tickets",
  description = "Manage support tickets",
  emptyTitle = "No tickets yet",
  emptyDescription = "Create the first ticket to get started.",
}: TicketListProps) {
  if (!tickets.length) {
    return (
      <Empty>
        <EmptyContent>
          <EmptyHeader>
            <EmptyTitle>{emptyTitle}</EmptyTitle>
            <EmptyDescription>{emptyDescription}</EmptyDescription>
          </EmptyHeader>
        </EmptyContent>
      </Empty>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Issue</TableHead>
              <TableHead>Category</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Created</TableHead>
              <TableHead />
            </TableRow>
          </TableHeader>
          <TableBody>
            {tickets.map((ticket) => (
              <TableRow key={ticket.id}>
                <TableCell className="max-w-md whitespace-normal">{ticket.issue}</TableCell>
                <TableCell><Badge variant="secondary">{ticket.category}</Badge></TableCell>
                <TableCell><Badge variant={ticket.status === "open" ? "default" : "outline"}>{ticket.status}</Badge></TableCell>
                <TableCell>{new Date(ticket.created_at).toLocaleDateString()}</TableCell>
                <TableCell className="text-right">
                  <Link className={buttonVariants({ variant: "outline", size: "sm" })} href={`/ticket/${ticket.id}`}>
                    View
                  </Link>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}
