'use client';

import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    Controller,
    type Control,
    type FieldValues,
    type Path,
} from 'react-hook-form';

interface ControlledAsyncSelectFieldProps<
    TFieldValues extends FieldValues,
    TItem extends object = Record<string, unknown>,
> {
    readonly control: Control<TFieldValues>;
    readonly name: Path<TFieldValues>;
    /** Caption rendered above the select. */
    readonly label: string;
    readonly url: string;
    readonly placeholder: string;
    /** Label passed to AsyncSelect (defaults to `label`). */
    readonly selectLabel?: string;
    readonly labelFn?: (item: TItem) => string;
    readonly valueFn?: (item: TItem) => string;
}

/**
 * AsyncSelect bound to a react-hook-form field, with a plain text caption above it.
 * Used by inventory forms that render a bare Controller instead of FormField.
 */
export function ControlledAsyncSelectField<
    TFieldValues extends FieldValues,
    TItem extends object = Record<string, unknown>,
>({
    control,
    name,
    label,
    url,
    placeholder,
    selectLabel,
    labelFn,
    valueFn,
}: Readonly<ControlledAsyncSelectFieldProps<TFieldValues, TItem>>) {
    return (
        <Controller
            control={control}
            name={name}
            render={({ field }) => (
                <div className="space-y-2">
                    <div className="text-sm leading-none font-medium">
                        {label}
                    </div>
                    <AsyncSelect<TItem>
                        value={field.value ? String(field.value) : undefined}
                        onValueChange={field.onChange}
                        url={url}
                        placeholder={placeholder}
                        label={selectLabel ?? label}
                        labelFn={labelFn}
                        valueFn={valueFn}
                    />
                </div>
            )}
        />
    );
}
