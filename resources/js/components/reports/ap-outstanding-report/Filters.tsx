import {
    apBillStatusOptions,
    createApReportCommonFilterFields,
    createFilterFieldDateFields,
    createFilterFieldStatusSelect,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createApOutstandingReportFilterFields(): FieldDescriptor[] {
    return [
        ...createApReportCommonFilterFields(
            'page.ap_outstanding.search_placeholder',
            'Search bill number, supplier invoice, supplier, branch, PO number, GR number, or notes...',
        ),
        createFilterFieldStatusSelect(apBillStatusOptions),
        ...createFilterFieldDateFields([
            ['due_date_from', 'Due Date From', 'filter.from', 'From'],
            ['due_date_to', 'Due Date To', 'filter.to', 'To'],
        ]),
    ];
}
