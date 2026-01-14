import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';
import { AsyncSelect } from './AsyncSelect';

interface AsyncSelectFieldProps {
    name: string;
    label?: string;
    url: string; // API URL
    placeholder?: string;
    className?: string;
    children?: ReactNode;
    labelFn?: (item: any) => string;
    valueFn?: (item: any) => string;
}

export default function AsyncSelectField({
    name,
    label,
    url,
    placeholder = '',
    className,
    children,
    labelFn,
    valueFn,
}: AsyncSelectFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={cn('space-y-2', className)}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <AsyncSelect
                            value={field.value?.toString()}
                            onValueChange={field.onChange}
                            url={url}
                            placeholder={placeholder}
                            labelFn={labelFn}
                            valueFn={valueFn}
                        />
                    </FormControl>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
