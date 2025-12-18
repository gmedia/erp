'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { createPositionFilterFields } from '@/components/positions/PositionFilters';
import { PositionForm } from '@/components/positions/PositionForm';
import positions from '@/routes/positions';
import { type BreadcrumbItem } from '@/types';
import { positionColumns } from '@/components/positions/PositionColumns';

interface Position {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface PositionFormData {
    name: string;
}

interface PositionFilters {
    search: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: positions.index().url,
    },
];

export default function PositionIndex() {
    return (
        <CrudPage<Position, PositionFormData, PositionFilters>
            config={{
                entityName: 'Position',
                entityNamePlural: 'Positions',
                apiEndpoint: '/api/positions',
                queryKey: ['positions'],
                breadcrumbs,
                DataTableComponent: GenericDataTable,
                FormComponent: PositionForm,

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
                    position: props.item,
                }),

                getDeleteMessage: (position) =>
                    `This action cannot be undone. This will permanently delete ${position.name}'s position record.`,
            }}
        />
    );
}
