'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { type BreadcrumbItem } from '@/types';
import { createSimpleEntityColumns } from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

interface SimpleEntity {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface SimpleEntityFormData {
    name: string;
}

interface SimpleEntityIndexProps<Entity extends SimpleEntity> {
    entityName: string;
    entityNamePlural: string;
    routes?: any; // Routes object with index() method (optional)
    apiEndpoint: string;
    breadcrumbs: BreadcrumbItem[];
    columns?: ColumnDef<Entity>[]; // Optional custom columns
}

export function SimpleEntityIndex<Entity extends SimpleEntity>({
    entityName,
    entityNamePlural,
    routes,
    apiEndpoint,
    breadcrumbs,
    columns,
}: SimpleEntityIndexProps<Entity>) {
    const finalColumns: ColumnDef<Entity>[] = columns || createSimpleEntityColumns<Entity>();

    return (
        <CrudPage<Entity, SimpleEntityFormData>
            config={{
                entityName,
                entityNamePlural,
                apiEndpoint,
                queryKey: [entityNamePlural.toLowerCase()],
                breadcrumbs,

                DataTableComponent: GenericDataTable,
                FormComponent: SimpleEntityForm,

                mapDataTableProps: (props) => ({
                    ...props,
                    columns: finalColumns,
                    exportEndpoint: `${apiEndpoint}/export`,
                    entityName,
                }),

                mapFormProps: (props) => ({
                    ...props,
                    entity: props.item,
                    entityName,
                }),
            }}
        />
    );
}
