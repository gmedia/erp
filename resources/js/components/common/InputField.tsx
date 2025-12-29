'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Control, Path } from 'react-hook-form';

interface InputFieldProps<
    TFieldValues extends Record<string, unknown> = Record<string, unknown>,
> extends Omit<React.ComponentProps<'input'>, 'name'> {
    control: Control<TFieldValues>;
    name: Path<TFieldValues>;
    label: string;
    placeholder?: string;
}

export function InputField<
    TFieldValues extends Record<string, unknown> = Record<string, unknown>,
>({
    control,
    name,
    label,
    placeholder,
    type = 'text',
    ...props
}: InputFieldProps<TFieldValues>) {
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
