'use client';

import { assetColumns } from '@/components/assets/AssetColumns';
import { createAssetFilterFields } from '@/components/assets/AssetFilters';
import {
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import { Asset } from '@/types/asset';

export default function AssetRegisterReport() {
    const reportColumns = assetColumns.filter(
        (col) => col.id !== 'select' && col.id !== 'actions',
    );

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
        />
    );
}
