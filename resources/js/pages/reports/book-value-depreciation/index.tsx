'use client';

import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/common/DataTableCore';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { useState } from 'react';
import { bookValueDepreciationColumns, BookValueDepreciationReportItem } from '@/components/reports/book-value-depreciation/Columns';
import { createBookValueReportFilterFields } from '@/components/reports/book-value-depreciation/Filters';

export default function BookValueDepreciationReport() {
    // Generate filters
    const filterFields = createBookValueReportFilterFields();

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
        },
    });

    const { data, isLoading, meta } = useCrudQuery<BookValueDepreciationReportItem>({
        endpoint: '/reports/book-value-depreciation', // Matches our backend route
        queryKey: ['book-value-report'],
        entityName: 'Book Value & Depreciation Report',
        pagination,
        filters,
    });

    return (
        <>
            <Head title="Book Value & Depreciation Report" />
            <AppLayout breadcrumbs={[{ title: 'Reports' }, { title: 'Book Value & Depreciation', href: '/reports/book-value-depreciation' }]}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={bookValueDepreciationColumns}
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
                            onPageSizeChange={(per_page) => handlePageSizeChange(per_page)}
                            onSearchChange={handleSearchChange}
                            isLoading={isLoading}
                            filterValue={filters.search}
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            onResetFilters={resetFilters}
                            filterFields={filterFields}
                            exportEndpoint="/reports/book-value-depreciation/export"
                            entityName="Asset (Book Value)"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
