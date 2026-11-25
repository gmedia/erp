'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select';
import { type ReactNode } from 'react';

import {
    LabelText,
    type TranslatableLabel,
    useTranslatableText,
} from './TranslatedText';

export interface Option {
    value: string;
    label: TranslatableLabel;
}

interface SelectFieldProps {
    name: string;
    label?: TranslatableLabel;
    options: Option[];
    placeholder?: TranslatableLabel;
    className?: string;
    children?: ReactNode;
    disabled?: boolean;
}

/**
 * Generic select component for Employee, Position, and Department forms.
 */
export default function SelectField({
    name,
    label,
    options,
    placeholder = '',
    className,
    children,
    disabled,
}: Readonly<SelectFieldProps>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
    const translatedLabel = useTranslatableText(label ?? '');
    return (
        <FormField
            name={name}
            render={({ field }) => {
                const selectedOption =
                    field.value !== undefined &&
                    field.value !== null &&
                    field.value !== ''
                        ? options.find(
                              (o) => String(o.value) === String(field.value),
                          )
                        : undefined;
                return (
                    <FormItem className={className}>
                        {label && (
                            <FormLabel>
                                <LabelText label={label} />
                            </FormLabel>
                        )}
                        <FormControl>
                            <Select
                                onValueChange={field.onChange}
                                value={
                                    field.value !== undefined &&
                                    field.value !== null
                                        ? String(field.value)
                                        : undefined
                                }
                                disabled={disabled}
                            >
                                <SelectTrigger
                                    className="w-full min-w-0 overflow-hidden"
                                    aria-label={
                                        label === undefined
                                            ? undefined
                                            : translatedLabel
                                    }
                                >
                                    <span className="flex-1 truncate text-left">
                                        {field.value !== undefined &&
                                        field.value !== null &&
                                        field.value !== ''
                                            ? selectedOption && (
                                                  <LabelText
                                                      label={
                                                          selectedOption.label
                                                      }
                                                  />
                                              )
                                            : translatedPlaceholder}
                                    </span>
                                </SelectTrigger>
                                <SelectContent>
                                    {options.map((opt) => (
                                        <SelectItem
                                            key={opt.value}
                                            value={opt.value}
                                        >
                                            <LabelText label={opt.label} />
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormControl>
                        <FormMessage />
                        {children}
                    </FormItem>
                );
            }}
        />
    );
}
