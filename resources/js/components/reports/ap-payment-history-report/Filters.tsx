import {
    createApReportCommonFilterFields,
    createColumnSelectConfig,
    createFilterFieldDateFields,
    createFilterFieldStatusSelect,
    createSelectFilterFields,
    translatedOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createApPaymentHistoryReportFilterFields(): FieldDescriptor[] {
    const paymentMethodOptions = translatedOptions('filter.', [
        ['bank_transfer', 'Bank Transfer'],
        ['cash', 'Cash'],
        ['check', 'Check'],
        ['giro', 'Giro'],
        ['other', 'Other'],
    ]);

    const statusOptions = translatedOptions('status.', [
        ['confirmed', 'Confirmed'],
        ['reconciled', 'Reconciled'],
    ]);

    return [
        ...createApReportCommonFilterFields(
            'page.ap_payment_history.search_placeholder',
            'Search payment number, supplier, branch, bank account, reference, or notes...',
        ),
        ...createSelectFilterFields([
            createColumnSelectConfig(
                'payment_method',
                'Payment Method',
                paymentMethodOptions,
                'filter.select_method',
                'Select method',
            ),
        ]),
        createFilterFieldStatusSelect(statusOptions),
        ...createFilterFieldDateFields([
            ['payment_date_from', 'Payment Date From', 'filter.from', 'From'],
            ['payment_date_to', 'Payment Date To', 'filter.to', 'To'],
        ]),
    ];
}
