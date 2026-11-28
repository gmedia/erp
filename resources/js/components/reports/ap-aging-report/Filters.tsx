import {
    apBillStatusOptions,
    createApReportCommonFilterFields,
    createFilterFieldDateFields,
    createFilterFieldStatusSelect,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createApAgingReportFilterFields(): FieldDescriptor[] {
    return [
        ...createApReportCommonFilterFields(
            'page.ap_aging.search_placeholder',
            'Search bill number, supplier invoice, supplier, branch, PO number, GR number, or notes...',
        ),
        createFilterFieldStatusSelect(apBillStatusOptions),
        ...createFilterFieldDateFields([
            [
                'as_of_date',
                'As of Date',
                'filter.select_aging_date',
                'Select aging calculation date',
            ],
        ]),
    ];
}
