'use client';

import { TextareaField } from './TextareaField';
import type { TranslatableLabel } from './TranslatedText';

interface NotesTextareaFieldProps {
    readonly label: TranslatableLabel;
    readonly placeholder: TranslatableLabel;
    readonly rows?: number;
    readonly spanTwoColumns?: boolean;
}

export function NotesTextareaField({
    label,
    placeholder,
    rows = 2,
    spanTwoColumns = true,
}: Readonly<NotesTextareaFieldProps>) {
    const field = (
        <TextareaField
            name="notes"
            label={label}
            placeholder={placeholder}
            rows={rows}
        />
    );

    return spanTwoColumns ? (
        <div className="md:col-span-2">{field}</div>
    ) : (
        field
    );
}
