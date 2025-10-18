import { cn } from '@/lib/utils';
import { SelectTrigger, SelectContent } from '@/components/ui/select';
import { type ReactNode } from 'react';

/**
 * Wrapper that applies common Tailwind classes to SelectTrigger.
 */
export function StyledSelectTrigger({
  className,
  ...props
}: React.ComponentProps<'button'>) {
  return (
    <SelectTrigger
      className={cn('w-full border-input bg-background', className)}
      {...props}
    />
  );
}

/**
 * Wrapper that applies common Tailwind classes to SelectContent.
 */
export function StyledSelectContent({
  className,
  ...props
}: React.ComponentProps<'div'>) {
  return (
    <SelectContent
      className={cn('bg-popover text-popover-foreground border border-input', className)}
      {...props}
    />
  );
}

/**
 * Convenience component that composes the styled trigger and content.
 * Use it inside a `Select` component.
 */
export default function StyledSelect({
  children,
  triggerClassName,
  contentClassName,
}: {
  children: ReactNode;
  triggerClassName?: string;
  contentClassName?: string;
}) {
  return (
    <>
      <StyledSelectTrigger className={triggerClassName} />
      <StyledSelectContent className={contentClassName}>{children}</StyledSelectContent>
    </>
  );
}
