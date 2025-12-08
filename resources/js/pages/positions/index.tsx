'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { PositionForm } from '@/components/positions/PositionForm';
import positions from '@/routes/positions';
import { Position, PositionFormData } from '@/types/position';
import { type BreadcrumbItem } from '@/types';
import { positionColumns } from '@/components/positions/PositionColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: positions.index().url,
    },
];

export default function PositionIndex() {
    return (
        <CrudPage<Position, PositionFormData>
            config={{
                entityName: 'Position',
                entityNamePlural: 'Positions',
                apiEndpoint: '/api/positions',
                queryKey: ['positions'],
                breadcrumbs,
                
                DataTableComponent: GenericDataTable,
                FormComponent: PositionForm,
                
                // Simplified prop mapping using spread operator
                mapDataTableProps: (props) => ({
                    ...props,
                    columns: positionColumns,
                    exportEndpoint: '/api/positions/export',
                    entityType: 'position',
                }),
                
                mapFormProps: (props) => ({
                    ...props,
                    position: props.item,
                }),
            }}
        />
    );
}