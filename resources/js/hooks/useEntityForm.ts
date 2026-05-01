import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo } from 'react';
import { type DefaultValues, type FieldValues, useForm } from 'react-hook-form';

/**
 * useEntityForm — shared hook that eliminates boilerplate form setup
 * for CRUD entity forms.
 *
 * Handles: schema validation, default values from entity, form reset on entity change.
 *
 * Usage:
 * ```tsx
 * const form = useEntityForm({
 *     schema: warehouseFormSchema,
 *     getDefaults: getWarehouseFormDefaults,
 *     entity,
 * });
 * ```
 */
export function useEntityForm<
    TFormData extends FieldValues,
    TEntity = unknown,
>({
    schema,
    getDefaults,
    entity,
}: {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    schema: any;
    getDefaults: (entity?: TEntity | null) => TFormData;
    entity?: TEntity | null;
}) {
    const defaultValues = useMemo(
        () => getDefaults(entity) as DefaultValues<TFormData>,
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [entity],
    );

    const form = useForm<TFormData>({
        resolver: zodResolver(schema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return form;
}
