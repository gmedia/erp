'use client';

import { type FieldValues, type Path } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';

export interface SourceLineOption {
    id: number | string;
    product_id?: number | string | null;
    product_name?: string | null;
    unit_id?: number | string | null;
    unit_name?: string | null;
    unit_price?: string | null;
    remaining_quantity?: string | null;
}

export function formatSourceLineLabel(line: SourceLineOption): string {
    return `${line.product_name ?? line.product_id ?? ''} · ${line.remaining_quantity ?? '0'}`;
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
    readonly onLineSelect: (line: SourceLineOption) => void;
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
    onLineSelect,
}: Readonly<ItemSourceLineSelectFieldProps<TFormValues>>) {
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
            labelFn={formatSourceLineLabel}
            valueFn={(line) => String(line.id)}
            onItemSelect={onLineSelect}
        />
    );
}
