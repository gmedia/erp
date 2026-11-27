import * as React from "react"

import { cn } from "@/lib/utils"

function Textarea({
  className,
  ...props
}: Readonly<React.ComponentProps<"textarea">>) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        "placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring/40 aria-invalid:ring-2 aria-invalid:ring-destructive/40 flex field-sizing-content min-h-16 w-full rounded-[6px] border border-transparent bg-secondary px-2.5 py-2 text-sm transition-[color,box-shadow] outline-none disabled:cursor-not-allowed disabled:opacity-50",
        className
      )}
      {...props}
    />
  )
}

export { Textarea }
