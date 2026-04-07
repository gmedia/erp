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
            'Search',
            'Search GR number, PO number, supplier, warehouse, or product...',
        ),
        ...createPurchasingReportScopeFilterFields(),
        createGoodsReceiptStatusFilterField(),
    ];
}
