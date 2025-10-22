import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface FilterFieldProps {
    name: string;
    label?: string;
    placeholder?: string;
    className?: string;
    children?: ReactNode;
}

/**
 * Generic filter input used in DataTables for quick searching.
 */
export default function FilterField({
    name,
    label = '',
    placeholder = '',
    className,
    children,
}: FilterFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={cn('flex flex-col space-y-1', className)}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <Input
                            placeholder={placeholder}
                            value={field.value ?? ''}
                            onChange={(e) => field.onChange(e.target.value)}
                        />
                    </FormControl>
                    {children}
                </FormItem>
            )}
        />
    );
}
