'use client';

import {
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    translatedOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCreditNoteFilterFields(): FieldDescriptor[] {
    const creditNoteStatusOptions = translatedOptions('status.', [
        ['draft', 'Draft'],
        ['confirmed', 'Confirmed'],
        ['applied', 'Applied'],
        ['cancelled', 'Cancelled'],
        ['void', 'Void'],
    ]);

    const creditNoteReasonOptions = translatedOptions(
        'form_fields.credit_notes.reason_',
        [
            ['return', 'Return'],
            ['discount', 'Discount'],
            ['correction', 'Correction'],
            ['bad_debt', 'Bad Debt'],
            ['other', 'Other'],
        ],
    );

    return [
        ...createCustomerFinancialFilterFields({
            key: 'page.credit_notes.search_placeholder',
            fallback: 'Search credit note number, customer, or notes...',
        }),
        ...createSelectFilterFields([
            {
                name: 'reason',
                label: { key: 'column.reason', fallback: 'Reason' },
                options: creditNoteReasonOptions,
                placeholder: {
                    key: 'filter.select_reason',
                    fallback: 'Select Reason',
                },
            },
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: creditNoteStatusOptions,
                placeholder: {
                    key: 'filter.select_status',
                    fallback: 'Select Status',
                },
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'credit_note_date_from',
                label: {
                    key: 'column.credit_note_date_from',
                    fallback: 'Credit Note Date From',
                },
                placeholder: {
                    key: 'column.credit_note_date_from',
                    fallback: 'Credit Note Date From',
                },
            },
            {
                name: 'credit_note_date_to',
                label: {
                    key: 'column.credit_note_date_to',
                    fallback: 'Credit Note Date To',
                },
                placeholder: {
                    key: 'column.credit_note_date_to',
                    fallback: 'Credit Note Date To',
                },
            },
        ]),
    ];
}
