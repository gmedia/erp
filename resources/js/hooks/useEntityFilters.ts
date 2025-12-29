'use client';

/**
 * Returns filter field definitions for the three main entities.
 * The concrete implementation (options, validation, etc.) will be added later.
 *
 * @param entity - The entity type for which filters are required.
 */
export function useEntityFilters(
    entity: 'employee' | 'position' | 'department',
) {
    const commonFields = [
        {
            label: 'Name',
            name: 'name',
            type: 'text',
        },
        {
            label: 'Created At',
            name: 'created_at',
            type: 'date',
        },
    ] as const;

    switch (entity) {
        case 'employee':
            return [
                ...commonFields,
                {
                    label: 'Email',
                    name: 'email',
                    type: 'text',
                },
                {
                    label: 'Department',
                    name: 'department',
                    type: 'select',
                },
                {
                    label: 'Position',
                    name: 'position',
                    type: 'select',
                },
            ] as const;

        case 'position':
            return [...commonFields] as const;

        case 'department':
            return [...commonFields] as const;

        default:
            return [] as const;
    }
}
