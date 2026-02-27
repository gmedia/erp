'use client';

import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/common/DataTableCore';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { createPipelineAuditTrailColumns, PipelineAuditTrailItem } from '@/components/pipeline-audit-trail/Columns';
import { createPipelineAuditTrailFilterFields } from '@/components/pipeline-audit-trail/Filters';
import { DetailModal } from '@/components/pipeline-audit-trail/DetailModal';
import { useState } from 'react';

export default function PipelineAuditTrail() {
    const filterFields = createPipelineAuditTrailFilterFields();
    
    const [selectedItem, setSelectedItem] = useState<PipelineAuditTrailItem | null>(null);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);

    const handleViewDetail = (item: PipelineAuditTrailItem) => {
        setSelectedItem(item);
        setIsDetailModalOpen(true);
    };

    const columns = createPipelineAuditTrailColumns({ onViewDetail: handleViewDetail });

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
            entity_type: '',
            pipeline_id: '',
            performed_by: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<PipelineAuditTrailItem>({
        endpoint: '/pipeline-audit-trail', // Using the web route, it accepts JSON via wantsJson()
        queryKey: ['pipeline-audit-trail'],
        entityName: 'Pipeline Audit Trail',
        pagination,
        filters,
    });

    return (
        <>
            <Head title="Pipeline Audit Trail" />
            <AppLayout breadcrumbs={[{ title: 'Admin', href: '#' }, { title: 'Pipeline Audit Trail', href: '/pipeline-audit-trail' }]}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={columns}
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
                            exportEndpoint="/api/pipeline-audit-trail/export"
                            entityName="Audit Trail"
                        />
                    </div>
                </div>

                <DetailModal 
                    item={selectedItem}
                    open={isDetailModalOpen}
                    onOpenChange={setIsDetailModalOpen}
                />
            </AppLayout>
        </>
    );
}
