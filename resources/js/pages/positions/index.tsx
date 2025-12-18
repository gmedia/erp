'use client';

import { SimpleEntityIndex } from '@/components/common/SimpleEntityIndex';
import { positionColumns } from '@/components/positions/PositionColumns';
import positions from '@/routes/positions';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: positions.index().url,
    },
];

export default function PositionIndex() {
    return (
        <SimpleEntityIndex
            entityName="Position"
            entityNamePlural="Positions"
            routes={positions}
            apiEndpoint="/api/positions"
            breadcrumbs={breadcrumbs}
            columns={positionColumns}
        />
    );
}
