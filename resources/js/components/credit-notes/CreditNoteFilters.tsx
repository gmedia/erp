'use client';

import {
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCreditNoteFilterFields(): FieldDescriptor[] {
    const creditNoteStatusOptions = [
        { value: 'draft', label: 'Draft' },
        { value: 'confirmed', label: 'Confirmed' },
        { value: 'applied', label: 'Applied' },
        { value: 'cancelled', label: 'Cancelled' },
        { value: 'void', label: 'Void' },
    ];

    const creditNoteReasonOptions = [
        { value: 'return', label: 'Return' },
        { value: 'discount', label: 'Discount' },
        { value: 'correction', label: 'Correction' },
        { value: 'bad_debt', label: 'Bad Debt' },
        { value: 'other', label: 'Other' },
    ];

    return [
        ...createCustomerFinancialFilterFields(
            'Search credit note number, customer, or notes...',
        ),
        ...createSelectFilterFields([
            {
                name: 'reason',
                label: { key: 'column.reason', fallback: 'Reason' },
                options: creditNoteReasonOptions,
                placeholder: 'Select Reason',
            },
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: creditNoteStatusOptions,
                placeholder: 'Select Status',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'credit_note_date_from',
                label: {
                    key: 'column.credit_note_date_from',
                    fallback: 'Credit Note Date From',
                },
                placeholder: 'Credit Note Date From',
            },
            {
                name: 'credit_note_date_to',
                label: {
                    key: 'column.credit_note_date_to',
                    fallback: 'Credit Note Date To',
                },
                placeholder: 'Credit Note Date To',
            },
        ]),
    ];
}
