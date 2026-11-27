import * as React from "react"
import * as CheckboxPrimitive from "@radix-ui/react-checkbox"
import { CheckIcon } from "lucide-react"

import { cn } from "@/lib/utils"

function Checkbox({
  className,
  ...props
}: Readonly<React.ComponentProps<typeof CheckboxPrimitive.Root>>) {
  return (
    <CheckboxPrimitive.Root
      data-slot="checkbox"
      className={cn(
        "peer border-border bg-background dark:border-input dark:bg-input/30 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary dark:data-[state=checked]:bg-primary dark:data-[state=checked]:border-primary size-4 shrink-0 rounded-[4px] border transition-shadow outline-none focus-visible:ring-2 focus-visible:ring-ring/40 aria-invalid:ring-2 aria-invalid:ring-destructive/40 aria-invalid:border-destructive disabled:cursor-not-allowed disabled:opacity-50",
        className
      )}
      {...props}
    >
      <CheckboxPrimitive.Indicator
        data-slot="checkbox-indicator"
        className="flex items-center justify-center text-current transition-none"
      >
        <CheckIcon className="size-3.5" />
      </CheckboxPrimitive.Indicator>
    </CheckboxPrimitive.Root>
  )
}

export { Checkbox }
