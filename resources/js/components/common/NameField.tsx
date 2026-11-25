import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { type ReactNode } from 'react';

import {
    LabelText,
    type TranslatableLabel,
    useTranslatableText,
} from './TranslatedText';

interface NameFieldProps {
    name: string;
    label?: TranslatableLabel;
    placeholder?: TranslatableLabel;
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
}: Readonly<NameFieldProps>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
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
                        <Input placeholder={translatedPlaceholder} {...field} />
                    </FormControl>
                    {children}
                </FormItem>
            )}
        />
    );
}
