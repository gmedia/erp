import {
    createGoodsReceiptStatusFilterField,
    createPurchasingReportScopeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createGoodsReceiptReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'filter.goods_receipt_search_placeholder',
                fallback:
                    'Search GR number, PO number, supplier, warehouse, or product...',
            },
        ),
        ...createPurchasingReportScopeFilterFields(),
        createGoodsReceiptStatusFilterField(),
    ];
}
