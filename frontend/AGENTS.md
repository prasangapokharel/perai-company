<!-- BEGIN:nextjs-agent-rules -->
# Agent Guidelines & Next.js Best Practices

This is NOT the Next.js you know. Breaking changes across APIs, conventions, and file structure differ from earlier versions. Always check `node_modules/next/dist/docs/` before writing code. Heed all deprecation notices.

## Core Rules

- Always use existing UI components from `components/ui/`
- Import components with clean, absolute paths: `'@/components/ui/button'`
- Never create raw HTML elements when a UI component exists
- Enforce Server Component by default; use `'use client'` only when necessary
- Validate RSC boundaries: no async client components, no non-serializable props
- Follow Next.js App Router conventions strictly

<!-- END:nextjs-agent-rules -->

## UI Component Usage

### Available Components

All UI components are in `components/ui/`. Common components:

- **Layout**: `card`, `separator`, `collapsible`
- **Input**: `input`, `button`, `checkbox`, `combobox`, `input-group`
- **Display**: `badge`, `avatar`, `alert`, `empty`
- **Navigation**: `breadcrumb`, `dropdown-menu`, `command`
- **Feedback**: `alert-dialog`, `dialog`, `toast`
- **Data**: `carousel`, `calendar`, `chart`

### Import Pattern (Clean & Scalable)

```tsx
// Good: Absolute path imports from ui components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { cn } from '@/lib/utils'

export function MyComponent() {
  return (
    <Card>
      <CardHeader>Title</CardHeader>
      <CardContent>
        <Button>Action</Button>
      </CardContent>
    </Card>
  )
}
```

```tsx
// Bad: Relative imports
import { Button } from '../../../ui/button'

// Bad: Custom HTML when Button component exists
<button className="...">Click me</button>
```

### Styling with UI Components

Always use component props and `cn()` utility for composition:

```tsx
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

export function StyledButton({ variant, size, className, ...props }) {
  return (
    <Button
      variant={variant}
      size={size}
      className={cn('custom-class', className)}
      {...props}
    />
  )
}
```

## Server & Client Component Boundaries

### RSC Rules

**Server Components (default):**
- No event listeners or hooks
- Can access databases, APIs, secrets
- Always async by default

```tsx
// Good: Server component (no 'use client')
export default async function Page() {
  const data = await fetchData()
  return <Component data={data} />
}
```

**Client Components:**
- Only when you need interactivity, hooks, state
- Cannot be async
- Cannot pass functions (except Server Actions)

```tsx
'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'

export function Counter() {
  const [count, setCount] = useState(0)
  return <Button onClick={() => setCount(count + 1)}>{count}</Button>
}
```

### Valid Prop Serialization

Props from Server → Client must be JSON-serializable:

```tsx
// Good: Primitive types, Server Actions, JSON-serializable data
export default async function Page() {
  const user = await getUser()
  return <UserCard userId={user.id} name={user.name} />
}

// Bad: Functions, Dates, class instances
export default async function Page() {
  const handler = () => {} // Non-serializable
  const date = new Date() // Non-serializable
  return <Component onClick={handler} createdAt={date} />
}
```

## Data Fetching Patterns

### Best Practice: Fetch in Server Components

```tsx
// app/dashboard/page.tsx (server component)
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { DashboardChart } from './chart'

export default async function DashboardPage() {
  const metrics = await getMetrics()
  
  return (
    <Card>
      <CardHeader>Analytics</CardHeader>
      <CardContent>
        <DashboardChart data={metrics} />
      </CardContent>
    </Card>
  )
}
```

### For Client-Side Interactivity

```tsx
'use client'

import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/button'
import { AlertDialog } from '@/components/ui/alert-dialog'

export function InteractiveCard() {
  const [isOpen, setIsOpen] = useState(false)
  
  return (
    <>
      <Button onClick={() => setIsOpen(true)}>Open</Button>
      <AlertDialog open={isOpen} onOpenChange={setIsOpen}>
        {/* Dialog content */}
      </AlertDialog>
    </>
  )
}
```

## File Conventions

### Route Structure
- `app/` - App Router root
- `app/page.tsx` - Home page (server component)
- `app/[slug]/page.tsx` - Dynamic routes
- `app/api/route.ts` - API endpoints
- `app/layout.tsx` - Root layout

### Special Files
- `layout.tsx` - Shared layout structure
- `not-found.tsx` - 404 handling
- `error.tsx` - Error boundary
- `loading.tsx` - Suspense fallback

```tsx
// Good: Proper file structure
// app/products/page.tsx (server)
export default async function Page() {
  const products = await getProducts()
  return <ProductList products={products} />
}

// app/products/error.tsx
'use client'
export default function Error({ error, reset }) {
  return (
    <div>
      <p>Error: {error.message}</p>
      <button onClick={reset}>Retry</button>
    </div>
  )
}
```

## Metadata & Optimization

### Static Metadata

```tsx
import { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Products',
  description: 'Browse our products',
}

export default function Page() {
  return <div>Products</div>
}
```

### Dynamic Metadata

```tsx
import { Metadata } from 'next'

export async function generateMetadata({ params }): Promise<Metadata> {
  const post = await getPost(params.slug)
  return {
    title: post.title,
    description: post.excerpt,
  }
}

export default async function PostPage({ params }) {
  const post = await getPost(params.slug)
  return <Article post={post} />
}
```

### Image Optimization

Always use `next/image` over `<img>`:

```tsx
import Image from 'next/image'

export function HeroImage() {
  return (
    <Image
      src="/hero.jpg"
      alt="Hero image"
      width={1200}
      height={600}
      priority // For LCP images
      className="rounded-lg"
    />
  )
}
```

## Form Handling

### Server Actions (Recommended)

```tsx
// app/actions.ts
'use server'

export async function createPost(formData: FormData) {
  const title = formData.get('title')
  const content = formData.get('content')
  
  const post = await db.posts.create({ title, content })
  revalidatePath('/posts')
  redirect(`/posts/${post.id}`)
}

// app/posts/new/page.tsx
'use client'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { createPost } from '@/app/actions'

export function NewPostForm() {
  return (
    <form action={createPost}>
      <Input name="title" placeholder="Title" required />
      <textarea name="content" placeholder="Content" required />
      <Button type="submit">Create</Button>
    </form>
  )
}
```

## Performance Patterns

### Avoid Waterfalls with Promise.all

```tsx
// Bad: Sequential fetches (waterfall)
export default async function Dashboard() {
  const user = await getUser()
  const posts = await getPosts(user.id)
  const comments = await getComments(posts[0].id)
  return <div>...</div>
}

// Good: Parallel fetches
export default async function Dashboard() {
  const [user, posts, comments] = await Promise.all([
    getUser(),
    getPosts(),
    getComments(),
  ])
  return <div>...</div>
}
```

### Suspense Boundaries

```tsx
import { Suspense } from 'react'
import { Card } from '@/components/ui/card'

function SkeletonCard() {
  return <Card className="animate-pulse" />
}

export default function Dashboard() {
  return (
    <Suspense fallback={<SkeletonCard />}>
      <DataCard />
    </Suspense>
  )
}
```

## Error Handling

### Global Error Handler

```tsx
// app/error.tsx
'use client'

import { Button } from '@/components/ui/button'
import { AlertDialog } from '@/components/ui/alert-dialog'

export default function Error({ error, reset }) {
  return (
    <AlertDialog>
      <div>
        <h1>Something went wrong</h1>
        <p>{error.message}</p>
        <Button onClick={reset}>Try again</Button>
      </div>
    </AlertDialog>
  )
}
```

### Graceful 404s

```tsx
// app/not-found.tsx
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import { Empty } from '@/components/ui/empty'

export default function NotFound() {
  return (
    <Empty
      title="Page not found"
      description="The page you're looking for doesn't exist."
      action={<Button asChild><Link href="/">Go home</Link></Button>}
    />
  )
}
```

## Reference Docs

For detailed guidance, see:
- `.agents/next-skills/skills/next-best-practices/functions.md` - Navigation hooks, server functions, generate functions
- `.agents/next-skills/skills/next-best-practices/rsc-boundaries.md` - RSC validation rules
- `.agents/next-skills/skills/next-best-practices/data-patterns.md` - Data fetching strategies
- `.agents/next-skills/skills/next-best-practices/error-handling.md` - Error boundaries and fallbacks
- `.agents/next-skills/skills/next-best-practices/route-handlers.md` - API routes and route handlers
- `.agents/next-skills/skills/next-best-practices/metadata.md` - SEO and metadata
- `.agents/next-skills/skills/next-best-practices/image.md` - Image optimization
- `.agents/next-skills/skills/next-best-practices/suspense-boundaries.md` - Suspense patterns
- `.agents/next-skills/skills/next-best-practices/hydration-error.md` - Debugging hydration issues
