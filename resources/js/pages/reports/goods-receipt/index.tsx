'use client';

import { ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import {
    goodsReceiptReportColumns,
    type GoodsReceiptReportItem,
} from '@/components/reports/goods-receipt/Columns';
import { createGoodsReceiptReportFilterFields } from '@/components/reports/goods-receipt/Filters';

export default function GoodsReceiptReportPage() {
    return (
        <ReportDataTablePage<GoodsReceiptReportItem>
            title="Goods Receipt Report"
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                { title: 'Goods Receipt', href: '/reports/goods-receipt' },
            ]}
            columns={goodsReceiptReportColumns}
            filterFields={createGoodsReceiptReportFilterFields()}
            initialFilters={{
                search: '',
                supplier_id: '',
                warehouse_id: '',
                product_id: '',
                status: '',
                start_date: '',
                end_date: '',
            }}
            endpoint="/api/reports/goods-receipt"
            queryKey={['goods-receipt-report']}
            entityName="Goods Receipt Report"
            exportEndpoint="/api/reports/goods-receipt/export"
        />
    );
}
