'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { type ReactNode } from 'react';
import { AsyncSelect } from './AsyncSelect';
import {
    LabelText,
    type TranslatableLabel,
    useTranslatableText,
} from './TranslatedText';

interface AsyncSelectFieldProps<T extends object = Record<string, unknown>> {
    readonly name: string;
    readonly label?: TranslatableLabel;
    readonly url: string; // API URL
    readonly placeholder?: TranslatableLabel;
    readonly className?: string;
    readonly children?: ReactNode;
    readonly labelFn?: (item: T) => string;
    readonly valueFn?: (item: T) => string;
    readonly initialLabel?: string;
    readonly onItemSelect?: (item: T) => void;
    readonly preferredMetaKey?: string;
    readonly filterItems?: (item: T) => boolean;
    readonly disabled?: boolean;
}

export default function AsyncSelectField<
    T extends object = Record<string, unknown>,
>({
    name,
    label,
    url,
    placeholder = '',
    className,
    children,
    labelFn,
    valueFn,
    initialLabel,
    onItemSelect,
    preferredMetaKey,
    filterItems,
    disabled = false,
}: Readonly<AsyncSelectFieldProps<T>>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
    const translatedLabel = useTranslatableText(label ?? '');
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    {label && (
                        <FormLabel>
                            <LabelText label={label} />
                        </FormLabel>
                    )}
                    <FormControl>
                        <AsyncSelect<T>
                            value={
                                field.value ? String(field.value) : undefined
                            }
                            onValueChange={field.onChange}
                            url={url}
                            placeholder={translatedPlaceholder}
                            labelFn={labelFn}
                            valueFn={valueFn}
                            initialLabel={initialLabel}
                            onItemSelect={onItemSelect}
                            label={
                                label === undefined
                                    ? undefined
                                    : translatedLabel
                            }
                            preferredMetaKey={preferredMetaKey}
                            filterItems={filterItems}
                            disabled={disabled}
                        />
                    </FormControl>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
