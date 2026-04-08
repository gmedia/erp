'use client';

import { assetColumns } from '@/components/assets/AssetColumns';
import { createAssetFilterFields } from '@/components/assets/AssetFilters';
import { AssetViewModal } from '@/components/assets/AssetViewModal';
import {
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import { Asset } from '@/types/asset';
import { useState } from 'react';

export default function AssetRegisterReport() {
    const [isViewModalOpen, setIsViewModalOpen] = useState(false);
    const [viewItem, setViewItem] = useState<Asset | null>(null);

    const handleView = (item: Asset) => {
        setViewItem(item);
        setIsViewModalOpen(true);
    };

    // Remove the select column and action column for the report if needed, or keep them.
    // In this case, we keep actions so they can view details, but we remove the select checkboxes.
    const reportColumns = assetColumns.filter((col) => col.id !== 'select');

    return (
        <ReportDataTablePage<Asset>
            title="Asset Register Report"
            breadcrumbs={createReportBreadcrumbs(
                'Asset Register',
                '/reports/assets/register',
            )}
            columns={reportColumns}
            filterFields={createAssetFilterFields()}
            initialFilters={{
                search: '',
                asset_category_id: '',
                branch_id: '',
                status: '',
                condition: '',
            }}
            endpoint="/api/assets"
            queryKey={['assets-report']}
            entityName="Asset"
            exportEndpoint="/reports/assets/register/export"
            onView={handleView}
        >
            <AssetViewModal
                open={isViewModalOpen}
                onClose={() => {
                    setIsViewModalOpen(false);
                    setViewItem(null);
                }}
                item={viewItem}
            />
        </ReportDataTablePage>
    );
}
