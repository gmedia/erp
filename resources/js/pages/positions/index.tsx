'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createPositionFilterFields } from '@/components/positions/PositionFilters';
import { Position, PositionFormData, SimpleEntityFilters } from '@/types/entity';
import { type BreadcrumbItem } from '@/types';
import { positionColumns } from '@/components/positions/PositionColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: '/positions',
    },
];

export default function PositionIndex() {
    return (
        <CrudPage<Position, PositionFormData, SimpleEntityFilters>
            config={{
                entityName: 'Position',
                entityNamePlural: 'Positions',
                apiEndpoint: '/api/positions',
                queryKey: ['positions'],
                breadcrumbs,

                DataTableComponent: GenericDataTable,
                FormComponent: SimpleEntityForm,

                initialFilters: {
                    search: '',
                },

                mapDataTableProps: (props) => ({
                    ...props,
                    columns: positionColumns,
                    exportEndpoint: '/api/positions/export',
                    filterFields: createPositionFilterFields(),
                }),

                mapFormProps: (props) => ({
                    ...props,
                    entity: props.item,
                    entityName: 'Position',
                }),

                getDeleteMessage: (position) =>
                    `This action cannot be undone. This will permanently delete ${position.name}'s position record.`,
            }}
        />
    );
}
