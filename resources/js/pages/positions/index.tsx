'use client';

import { SimpleEntityIndex } from '@/components/common/SimpleEntityIndex';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: '/positions',
    },
];

export default function PositionIndex() {
    return (
        <SimpleEntityIndex
            entityName="Position"
            entityNamePlural="Positions"
            apiEndpoint="/api/positions"
            breadcrumbs={breadcrumbs}
        />
    );
}
