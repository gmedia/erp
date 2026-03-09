'use client';

import { DataTable } from '@/components/common/DataTableCore';
import {
    maintenanceCostColumns,
    MaintenanceCostReportItem,
} from '@/components/reports/maintenance-cost/Columns';
import { createMaintenanceCostReportFilterFields } from '@/components/reports/maintenance-cost/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import { Helmet } from 'react-helmet-async';

export default function MaintenanceCostReport() {
    const filterFields = createMaintenanceCostReportFilterFields();

    const {
        filters,
        pagination,
        handleFilterChange,
        handleSearchChange,
        handlePageChange,
        handlePageSizeChange,
        resetFilters,
    } = useCrudFilters({
        initialFilters: {
            search: '',
            asset_category_id: '',
            branch_id: '',
            supplier_id: '',
            maintenance_type: '',
            status: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<MaintenanceCostReportItem>({
        endpoint: '/reports/maintenance-cost',
        queryKey: ['maintenance-cost-report'],
        entityName: 'Maintenance Cost Report',
        pagination,
        filters,
    });

    return (
        <>
            <Helmet>
                <title>Maintenance Cost Report</title>
            </Helmet>
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    {
                        title: 'Maintenance Cost',
                        href: '/reports/maintenance-cost',
                    },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={maintenanceCostColumns}
                            data={data}
                            pagination={{
                                page: meta.current_page,
                                per_page: meta.per_page,
                                total: meta.total,
                                last_page: meta.last_page,
                                from: meta.from ?? 0,
                                to: meta.to ?? 0,
                            }}
                            onPageChange={handlePageChange}
                            onPageSizeChange={(per_page) =>
                                handlePageSizeChange(per_page)
                            }
                            onSearchChange={handleSearchChange}
                            isLoading={isLoading}
                            filterValue={filters.search}
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            onResetFilters={resetFilters}
                            filterFields={filterFields}
                            exportEndpoint="/reports/maintenance-cost/export"
                            entityName="Maintenance Cost"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
