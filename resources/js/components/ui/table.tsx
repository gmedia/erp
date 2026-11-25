import * as React from "react"

import { cn } from "@/lib/utils"

// Indikator tepi kanan (W4-03): strip gradient non-scrolling di tepi viewport
// muncul hanya saat masih ada konten di luar viewport kanan. Sel terakhir diberi
// ruang (W5-02) agar fade tidak menutupi angka/header.
const TABLE_EDGE_RIGHT = [
  "after:pointer-events-none after:absolute after:inset-y-0 after:right-0",
  "after:z-10 after:w-6 after:bg-linear-to-r after:from-transparent after:to-background",
].join(" ")

const ACTIONS_SHADOW =
  "[&_[data-sticky-column=actions]]:before:content-[''] [&_[data-sticky-column=actions]]:before:pointer-events-none [&_[data-sticky-column=actions]]:before:absolute [&_[data-sticky-column=actions]]:before:inset-y-0 [&_[data-sticky-column=actions]]:before:-left-3 [&_[data-sticky-column=actions]]:before:w-3 [&_[data-sticky-column=actions]]:before:bg-linear-to-l [&_[data-sticky-column=actions]]:before:from-foreground/15 [&_[data-sticky-column=actions]]:before:to-transparent"

type TableProps = React.ComponentProps<"table"> & { edgeFade?: boolean }

function Table({
  className,
  edgeFade = true,
  ...props
}: Readonly<TableProps>) {
  const scrollRef = React.useRef<HTMLDivElement>(null)
  const [canScrollRight, setCanScrollRight] = React.useState(false)
  const showEdgeFade = edgeFade && canScrollRight

  React.useEffect(() => {
    const el = scrollRef.current
    if (!el) return

    const update = () => {
      const next = el.scrollLeft + el.clientWidth < el.scrollWidth - 1
      setCanScrollRight((prev) => (prev === next ? prev : next))
    }

    update()
    el.addEventListener("scroll", update, { passive: true })
    window.addEventListener("resize", update)

    // Lebar tabel bertambah setelah data async masuk; observer menjaga
    // indikator tetap akurat tanpa menunggu scroll (W6-01).
    const observer =
      typeof ResizeObserver === "undefined"
        ? null
        : new ResizeObserver(update)
    observer?.observe(el)
    const table = el.querySelector("table")
    if (table) observer?.observe(table)

    return () => {
      el.removeEventListener("scroll", update)
      window.removeEventListener("resize", update)
      observer?.disconnect()
    }
  }, [])

  return (
    <div
      data-slot="table-container"
      data-overflow-right={canScrollRight || undefined}
      className={cn(
        "relative @container w-full min-w-0",
        showEdgeFade && TABLE_EDGE_RIGHT,
        showEdgeFade &&
          "[&_td:last-child]:pr-5 [&_th:last-child]:pr-5",
        showEdgeFade && ACTIONS_SHADOW
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
        "relative z-10 bg-muted/50 border-t font-medium [&>tr]:last:border-b-0",
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
