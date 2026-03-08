'use client';

import { Helmet } from 'react-helmet-async';
import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/common/DataTableCore';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { createApprovalAuditTrailColumns, ApprovalAuditTrailItem } from '@/components/approval-audit-trail/Columns';
import { createApprovalAuditTrailFilterFields } from '@/components/approval-audit-trail/Filters';
import { DetailModal } from '@/components/approval-audit-trail/DetailModal';
import { useState } from 'react';

export default function ApprovalAuditTrail() {
    const filterFields = createApprovalAuditTrailFilterFields();
    
    const [selectedItem, setSelectedItem] = useState<ApprovalAuditTrailItem | null>(null);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);

    const handleViewDetail = (item: ApprovalAuditTrailItem) => {
        setSelectedItem(item);
        setIsDetailModalOpen(true);
    };

    const columns = createApprovalAuditTrailColumns({ onViewDetail: handleViewDetail });

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
            approvable_type: '',
            event: '',
            actor_user_id: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<ApprovalAuditTrailItem>({
        endpoint: '/api/approval-audit-trail', // Fetch JSON from API
        queryKey: ['approval-audit-trail'],
        entityName: 'Approval Audit Trail',
        pagination,
        filters,
    });

    return (
        <>
            <Helmet><title>Approval Audit Trail</title></Helmet>
            <AppLayout breadcrumbs={[{ title: 'Admin', href: '#' }, { title: 'Approval Audit Trail', href: '/approval-audit-trail' }]}>
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
                            exportEndpoint="/api/approval-audit-trail/export"
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
