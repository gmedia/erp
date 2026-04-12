'use client';

import type { FieldDescriptor } from '@/components/common/filters';
import { ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import type { FilterState } from '@/hooks/useCrudFilters';
import type { ColumnDef } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { useState } from 'react';

type BreadcrumbItem = {
    title: string;
    href: string;
};

type AuditTrailPageProps<TItem, TFilters extends FilterState = FilterState> = {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    filterFields: FieldDescriptor[];
    initialFilters: TFilters;
    endpoint: string;
    queryKey: string[];
    entityName: string;
    exportEndpoint: string;
    buildColumns: (options: {
        onViewDetail: (item: TItem) => void;
    }) => ColumnDef<TItem>[];
    renderDetailModal: (options: {
        item: TItem | null;
        open: boolean;
        onOpenChange: (open: boolean) => void;
    }) => ReactNode;
};

export function AuditTrailPage<
    TItem,
    TFilters extends FilterState = FilterState,
>({
    title,
    breadcrumbs,
    filterFields,
    initialFilters,
    endpoint,
    queryKey,
    entityName,
    exportEndpoint,
    buildColumns,
    renderDetailModal,
}: Readonly<AuditTrailPageProps<TItem, TFilters>>) {
    const [selectedItem, setSelectedItem] = useState<TItem | null>(null);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);

    const handleViewDetail = (item: TItem) => {
        setSelectedItem(item);
        setIsDetailModalOpen(true);
    };

    const columns = buildColumns({ onViewDetail: handleViewDetail });

    return (
        <ReportDataTablePage<TItem, TFilters>
            title={title}
            breadcrumbs={breadcrumbs}
            columns={columns}
            filterFields={filterFields}
            initialFilters={initialFilters}
            endpoint={endpoint}
            queryKey={queryKey}
            entityName={entityName}
            exportEndpoint={exportEndpoint}
        >
            {renderDetailModal({
                item: selectedItem,
                open: isDetailModalOpen,
                onOpenChange: setIsDetailModalOpen,
            })}
        </ReportDataTablePage>
    );
}
