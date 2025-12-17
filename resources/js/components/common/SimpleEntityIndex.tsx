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
    routes: any; // Routes object with index() method
    apiEndpoint: string;
    breadcrumbs: BreadcrumbItem[];
}

export function SimpleEntityIndex<Entity extends SimpleEntity>({
    entityName,
    entityNamePlural,
    routes,
    apiEndpoint,
    breadcrumbs,
}: SimpleEntityIndexProps<Entity>) {
    const columns: ColumnDef<Entity>[] = createSimpleEntityColumns<Entity>();

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
                    columns,
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
