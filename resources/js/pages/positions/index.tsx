'use client';

import { SimpleEntityIndex } from '@/components/common/SimpleEntityIndex';
import positions from '@/routes/positions';
import { Position } from '@/types/position';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: positions.index().url,
    },
];

export default function PositionIndex() {
    return (
        <SimpleEntityIndex<Position>
            entityName="Position"
            entityNamePlural="Positions"
            routes={positions}
            apiEndpoint="/api/positions"
            breadcrumbs={breadcrumbs}
        />
    );
}
