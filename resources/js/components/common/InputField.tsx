'use client';

import { FormField, FormItem, FormLabel, FormControl, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Control, Path } from 'react-hook-form';

interface InputFieldProps<T extends Record<string, unknown> = Record<string, unknown>> extends Omit<React.ComponentProps<'input'>, 'name'> {
    control: Control<T>;
    name: Path<T>;
    label: string;
    placeholder?: string;
}

export function InputField<T extends Record<string, any>>({
    control,
    name,
    label,
    placeholder,
    type = 'text',
    ...props
}: InputFieldProps<T>) {
    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <FormControl>
                        <Input
                            type={type}
                            placeholder={placeholder}
                            {...field}
                            {...props}
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            )}
        />
    );
}
