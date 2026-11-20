import { Label } from '@/components/ui/label';
import type { ComponentProps } from 'react';

type RequiredLabelProps = Readonly<
    ComponentProps<typeof Label> & {
        /** Tampilkan tanda bintang wajib. Default `true`. */
        required?: boolean;
    }
>;

/**
 * Label form dengan penanda wajib (`*`) yang konsisten di modul Vehicle Loans.
 */
export function RequiredLabel({
    children,
    required = true,
    ...props
}: RequiredLabelProps) {
    return (
        <Label {...props}>
            {children}
            {required && (
                <span className="ml-1 text-destructive" aria-hidden="true">
                    *
                </span>
            )}
        </Label>
    );
}
