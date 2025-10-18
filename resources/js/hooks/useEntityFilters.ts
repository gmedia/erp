'use client';

import { FilterField } from '@/components/common/FilterField';
import { Employee } from '@/types/employee';
import { Department } from '@/types/department';
import { Position } from '@/types/position';

/**
 * Returns filter field definitions for the three main entities.
 * The concrete implementation (options, validation, etc.) will be added later.
 *
 * @param entity - The entity type for which filters are required.
 */
export function useEntityFilters(
  entity: 'employee' | 'position' | 'department'
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
      return [
        ...commonFields,
        {
          label: 'Department',
          name: 'department',
          type: 'select',
        },
      ] as const;

    case 'department':
      return [
        ...commonFields,
        {
          label: 'Location',
          name: 'location',
          type: 'text',
        },
      ] as const;

    default:
      return [] as const;
  }
}
