'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

import {
    LabelText,
    type TranslatableLabel,
    useTranslatableText,
} from './TranslatedText';

interface InputFieldProps extends Omit<
    React.ComponentProps<'input'>,
    'name' | 'prefix' | 'placeholder'
> {
    name: string;
    label: TranslatableLabel;
    placeholder?: TranslatableLabel;
    className?: string;
    prefix?: ReactNode;
    suffix?: ReactNode;
    children?: ReactNode;
}

export function InputField({
    name,
    label,
    placeholder,
    type = 'text',
    className,
    prefix,
    suffix,
    children,
    ...props
}: Readonly<InputFieldProps>) {
    const translatedPlaceholder = useTranslatableText(placeholder ?? '');
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    <FormLabel>
                        <LabelText label={label} />
                    </FormLabel>
                    <div className="relative flex items-center">
                        {prefix && (
                            <div className="pointer-events-none absolute left-3 flex items-center text-muted-foreground select-none">
                                {prefix}
                            </div>
                        )}
                        <FormControl>
                            <Input
                                type={type}
                                placeholder={
                                    placeholder === undefined
                                        ? undefined
                                        : translatedPlaceholder
                                }
                                className={cn(
                                    prefix && 'pl-10',
                                    suffix && 'pr-10',
                                )}
                                value={
                                    field.value as
                                        | string
                                        | number
                                        | readonly string[]
                                        | undefined
                                }
                                onChange={field.onChange}
                                onBlur={field.onBlur}
                                name={field.name}
                                ref={field.ref}
                                {...props}
                            />
                        </FormControl>
                        {suffix && (
                            <div className="pointer-events-none absolute right-3 flex items-center text-muted-foreground select-none">
                                {suffix}
                            </div>
                        )}
                    </div>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
