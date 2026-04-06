'use client';

import { ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import {
    AssetStocktakeVarianceItem,
    varianceColumns,
} from '@/components/reports/asset-stocktake-variances/Columns';
import { createVarianceFilterFields } from '@/components/reports/asset-stocktake-variances/Filters';

export default function StocktakeVarianceReport() {
    return (
        <ReportDataTablePage<AssetStocktakeVarianceItem>
            title="Stocktake Variance Report"
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                {
                    title: 'Stocktake Variance',
                    href: '/asset-stocktake-variances',
                },
            ]}
            columns={varianceColumns}
            filterFields={createVarianceFilterFields()}
            initialFilters={{
                search: '',
                asset_stocktake_id: '',
                branch_id: '',
                result: '',
            }}
            endpoint="/api/asset-stocktake-variances"
            queryKey={['asset-stocktake-variances']}
            entityName="Variance"
            exportEndpoint="/api/asset-stocktake-variances/export"
        />
    );
}
