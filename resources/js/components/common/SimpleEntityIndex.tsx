'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { Input } from '@/components/ui/input';
import { createSimpleEntityColumns } from '@/utils/columns';
import { SimpleEntity, SimpleEntityFormData, SimpleEntityFilters, BreadcrumbItem } from '@/types';

interface SimpleEntityIndexProps {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    breadcrumbs: BreadcrumbItem[];
}

export function SimpleEntityIndex({
    entityName,
    entityNamePlural,
    apiEndpoint,
    breadcrumbs,
}: SimpleEntityIndexProps) {
    return (
        <CrudPage<SimpleEntity, SimpleEntityFormData, SimpleEntityFilters>
            config={{
                entityName,
                entityNamePlural,
                apiEndpoint,
                queryKey: [entityNamePlural.toLowerCase()],
                breadcrumbs,

                DataTableComponent: GenericDataTable,
                FormComponent: SimpleEntityForm,

                initialFilters: {
                    search: '',
                },

                mapDataTableProps: (props) => ({
                    ...props,
                    columns: createSimpleEntityColumns<SimpleEntity>(),
                    exportEndpoint: `${apiEndpoint}/export`,
                    filterFields: [
                        {
                            name: 'search',
                            label: 'Search',
                            component: <Input placeholder={`Search ${entityNamePlural.toLowerCase()}...`} />,
                        },
                    ],
                }),

                mapFormProps: (props) => ({
                    ...props,
                    entity: props.item,
                    entityName,
                }),

                getDeleteMessage: (entity) =>
                    `This action cannot be undone. This will permanently delete ${entity.name}'s ${entityName.toLowerCase()} record.`,
            }}
        />
    );
}
