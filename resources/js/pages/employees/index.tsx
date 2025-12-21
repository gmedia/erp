'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { Employee, EmployeeFormData } from '@/types/entity';
import { employeeColumns } from '@/components/employees/EmployeeColumns';
import { employeeConfig, EmployeeFilters } from '@/utils/entityConfigs';

export default function EmployeeIndex() {
    return (
        <CrudPage<Employee, EmployeeFormData, EmployeeFilters>
            config={{
                entityName: employeeConfig.entityName,
                entityNamePlural: employeeConfig.entityNamePlural,
                apiEndpoint: employeeConfig.apiEndpoint,
                queryKey: employeeConfig.queryKey,
                breadcrumbs: employeeConfig.breadcrumbs,
                DataTableComponent: GenericDataTable,
                FormComponent: EmployeeForm,

                initialFilters: employeeConfig.initialFilters,

                mapDataTableProps: (props) => ({
                    ...props,
                    columns: employeeColumns,
                    exportEndpoint: employeeConfig.exportEndpoint,
                    filterFields: employeeConfig.filterFields,
                }),

                mapFormProps: (props) => ({
                    ...props,
                    employee: props.item,
                }),

                getDeleteMessage: employeeConfig.getDeleteMessage,
            }}
        />
    );
}
