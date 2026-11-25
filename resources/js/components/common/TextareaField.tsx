'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Textarea } from '@/components/ui/textarea';
import { type ReactNode } from 'react';

import {
    LabelText,
    type TranslatableLabel,
    useTranslatableText,
} from './TranslatedText';

interface TextareaFieldProps extends Omit<
    React.ComponentProps<'textarea'>,
    'name' | 'placeholder'
> {
    name: string;
    label: TranslatableLabel;
    placeholder?: TranslatableLabel;
    className?: string;
    children?: ReactNode;
    rows?: number;
}

export function TextareaField({
    name,
    label,
    placeholder,
    className,
    children,
    rows = 3,
    ...props
}: Readonly<TextareaFieldProps>) {
    const translatedPlaceholder = useTranslatableText(placeholder ?? '');
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    <FormLabel>
                        <LabelText label={label} />
                    </FormLabel>
                    <FormControl>
                        <Textarea
                            placeholder={
                                placeholder === undefined
                                    ? undefined
                                    : translatedPlaceholder
                            }
                            value={field.value as string | undefined}
                            onChange={field.onChange}
                            onBlur={field.onBlur}
                            name={field.name}
                            ref={field.ref}
                            rows={rows}
                            {...props}
                        />
                    </FormControl>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
