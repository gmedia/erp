import * as React from "react"

import { cn } from "@/lib/utils"

// Indikator tepi kanan (W4-03): strip gradient non-scrolling di tepi viewport
// muncul hanya saat masih ada konten di luar viewport kanan.
const TABLE_EDGE_RIGHT =
  "after:pointer-events-none after:absolute after:inset-y-0 after:right-0 after:w-6 after:bg-linear-to-r after:from-background after:to-transparent"

function Table({
  className,
  ...props
}: Readonly<React.ComponentProps<"table">>) {
  const scrollRef = React.useRef<HTMLDivElement>(null)
  const [canScrollRight, setCanScrollRight] = React.useState(false)

  React.useEffect(() => {
    const el = scrollRef.current
    if (!el) return
    const update = () =>
      setCanScrollRight(
        el.scrollLeft + el.clientWidth < el.scrollWidth - 1
      )
    update()
    el.addEventListener("scroll", update, { passive: true })
    window.addEventListener("resize", update)
    return () => {
      el.removeEventListener("scroll", update)
      window.removeEventListener("resize", update)
    }
  }, [])

  return (
    <div
      data-slot="table-container"
      data-overflow-right={canScrollRight || undefined}
      className={cn(
        "relative w-full min-w-0",
        canScrollRight && TABLE_EDGE_RIGHT
      )}
    >
      <div
        ref={scrollRef}
        className="w-full min-w-0 overflow-x-auto"
      >
        <table
          data-slot="table"
          className={cn("w-full caption-bottom text-sm", className)}
          {...props}
        />
      </div>
    </div>
  )
}

function TableHeader({
  className,
  ...props
}: Readonly<React.ComponentProps<"thead">>) {
  return (
    <thead
      data-slot="table-header"
      className={cn("[&_tr]:border-b", className)}
      {...props}
    />
  )
}

function TableBody({
  className,
  ...props
}: Readonly<React.ComponentProps<"tbody">>) {
  return (
    <tbody
      data-slot="table-body"
      className={cn("[&_tr:last-child]:border-0", className)}
      {...props}
    />
  )
}

function TableFooter({
  className,
  ...props
}: Readonly<React.ComponentProps<"tfoot">>) {
  return (
    <tfoot
      data-slot="table-footer"
      className={cn(
        "bg-muted/50 border-t font-medium [&>tr]:last:border-b-0",
        className
      )}
      {...props}
    />
  )
}

function TableRow({
  className,
  ...props
}: Readonly<React.ComponentProps<"tr">>) {
  return (
    <tr
      data-slot="table-row"
      className={cn(
        "hover:bg-muted/50 data-[state=selected]:bg-muted border-b transition-colors",
        className
      )}
      {...props}
    />
  )
}

function TableHead({
  className,
  ...props
}: Readonly<React.ComponentProps<"th">>) {
  return (
    <th
      data-slot="table-head"
      className={cn(
        "text-foreground h-10 px-2 text-left align-middle font-medium whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]",
        className
      )}
      {...props}
    />
  )
}

function TableCell({
  className,
  ...props
}: Readonly<React.ComponentProps<"td">>) {
  return (
    <td
      data-slot="table-cell"
      className={cn(
        "p-2 align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]",
        className
      )}
      {...props}
    />
  )
}

function TableCaption({
  className,
  ...props
}: Readonly<React.ComponentProps<"caption">>) {
  return (
    <caption
      data-slot="table-caption"
      className={cn("text-muted-foreground mt-4 text-sm", className)}
      {...props}
    />
  )
}

export {
  Table,
  TableHeader,
  TableBody,
  TableFooter,
  TableHead,
  TableRow,
  TableCell,
  TableCaption,
}
