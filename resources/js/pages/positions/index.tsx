'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { PositionForm } from '@/components/positions/PositionForm';
import positions from '@/routes/positions';
import { Position, PositionFormData } from '@/types/position';
import { type BreadcrumbItem } from '@/types';
import { createSimpleEntityColumns } from '@/utils/columns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: positions.index().url,
    },
];

export default function PositionIndex() {
    const columns = createSimpleEntityColumns<Position>();

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

                mapDataTableProps: (props) => ({
                    ...props,
                    columns,
                    exportEndpoint: '/api/positions/export',
                    entityName: 'Position',
                }),

                mapFormProps: (props) => ({
                    ...props,
                    position: props.item,
                }),
            }}
        />
    );
}
