'use client';

import SelectField, { type Option } from '@/components/common/SelectField';

export type FormFieldTranslator = (key: string, fallback: string) => string;

/**
 * Builds select options for a status list. Each option label is translated
 * through `fl` using `status_<value>` keys, with the English fallback given here.
 */
export function buildStatusOptions(
    fl: FormFieldTranslator,
    statuses: ReadonlyArray<readonly [value: string, fallback: string]>,
): Option[] {
    return statuses.map(([value, fallback]) => ({
        value,
        label: fl(`status_${value}`, fallback),
    }));
}

interface StatusSelectFieldProps {
    readonly fl: FormFieldTranslator;
    readonly label: string;
    readonly placeholder: string;
    readonly statuses: ReadonlyArray<
        readonly [value: string, fallback: string]
    >;
}

/**
 * Status SelectField whose options come from a (value, English fallback) list.
 */
export function StatusSelectField({
    fl,
    label,
    placeholder,
    statuses,
}: Readonly<StatusSelectFieldProps>) {
    return (
        <SelectField
            name="status"
            label={label}
            options={buildStatusOptions(fl, statuses)}
            placeholder={placeholder}
        />
    );
}
