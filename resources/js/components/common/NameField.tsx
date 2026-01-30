import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface NameFieldProps {
    name: string;
    label?: string;
    placeholder?: string;
    className?: string;
    children?: ReactNode;
}

/**
 * Reusable name input field used across Employee, Position, and Department forms.
 */
export default function NameField({
    name,
    label = 'Name',
    placeholder = '',
    className,
    children,
}: NameFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <Input placeholder={placeholder} {...field} />
                    </FormControl>
                    {children}
                </FormItem>
            )}
        />
    );
}
