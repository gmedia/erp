'use client';

import { createSimpleEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { Position, PositionFormData, SimpleEntityFilters } from '@/types/entity';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: '/positions',
    },
];

export default createSimpleEntityCrudPage<Position, PositionFormData, SimpleEntityFilters>({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    apiEndpoint: '/api/positions',
    queryKey: ['positions'],
    breadcrumbs,
    exportEndpoint: '/api/positions/export',
    filterPlaceholder: 'Search positions...',
    getDeleteMessage: (position) =>
        `This action cannot be undone. This will permanently delete ${position.name}'s position record.`,
});
