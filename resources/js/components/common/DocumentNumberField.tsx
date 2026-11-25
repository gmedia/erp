'use client';

import { InputField } from '@/components/common/InputField';
import type { FormFieldTranslator } from '@/components/common/StatusSelectField';

interface DocumentNumberFieldProps {
    readonly fl: FormFieldTranslator;
    readonly name: string;
    readonly label: string;
    readonly placeholderKey: string;
}

/**
 * Document number input whose empty value is filled by the backend
 * (e.g. GR/SR numbers), so the placeholder reads "Auto-generated if empty".
 */
export function DocumentNumberField({
    fl,
    name,
    label,
    placeholderKey,
}: Readonly<DocumentNumberFieldProps>) {
    return (
        <InputField
            name={name}
            label={label}
            placeholder={fl(placeholderKey, 'Auto-generated if empty')}
        />
    );
}
