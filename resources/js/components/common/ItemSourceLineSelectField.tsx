'use client';

import { type FieldValues, type Path, useFormContext } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { useTranslation } from '@/contexts/i18n-context';

export interface SourceLineOption {
    id: number | string;
    product_id?: number | string | null;
    product_name?: string | null;
    unit_id?: number | string | null;
    unit_name?: string | null;
    unit_price?: string | null;
    remaining_quantity?: string | null;
}

export function formatSourceLineLabel(
    line: SourceLineOption,
    t: (key: string, params?: Record<string, string>) => string,
): string {
    return t('itemForm.source_line_remaining', {
        product: String(line.product_name ?? line.product_id ?? ''),
        count: String(line.remaining_quantity ?? '0'),
    });
}

interface ItemSourceLineSelectFieldProps<TFormValues extends FieldValues> {
    readonly open: boolean;
    readonly name: Path<TFormValues>;
    readonly url: string | null;
    readonly label: string;
    readonly placeholder: string;
    readonly emptyHint: string;
    readonly initialId?: string;
    readonly initialLabel?: string;
    /** Field penampung label baris terpilih. */
    readonly labelName: Path<TFormValues>;
}

export function ItemSourceLineSelectField<TFormValues extends FieldValues>({
    name,
    url,
    label,
    placeholder,
    emptyHint,
    initialId,
    initialLabel,
    open,
    labelName,
}: Readonly<ItemSourceLineSelectFieldProps<TFormValues>>) {
    const { t } = useTranslation();
    const { setValue } = useFormContext();

    // Baris sumber menentukan produk, unit, dan harga item.
    const applyLine = (line: SourceLineOption) => {
        const values: Record<string, string | number> = {
            [labelName]: formatSourceLineLabel(line, t),
            product_id: String(line.product_id ?? ''),
            product_label: line.product_name ?? '',
            unit_id: String(line.unit_id ?? ''),
            unit_label: line.unit_name ?? '',
            unit_price: Number(line.unit_price ?? 0),
        };

        for (const [field, value] of Object.entries(values)) {
            setValue(field, value, { shouldDirty: true });
        }
    };

    if (!url) {
        return <p className="text-sm text-muted-foreground">{emptyHint}</p>;
    }

    return (
        <AsyncSelectField<SourceLineOption>
            key={`${String(name)}-${url}-${initialId || 'new'}-${open ? 'open' : 'closed'}`}
            name={String(name)}
            label={label}
            url={url}
            placeholder={placeholder}
            initialLabel={initialLabel}
            labelFn={(line) => formatSourceLineLabel(line, t)}
            valueFn={(line) => String(line.id)}
            onItemSelect={applyLine}
        />
    );
}
