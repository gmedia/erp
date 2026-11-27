'use client';

import {
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCreditNoteFilterFields(): FieldDescriptor[] {
    const creditNoteStatusOptions = [
        { value: 'draft', label: { key: 'status.draft', fallback: 'Draft' } },
        {
            value: 'confirmed',
            label: { key: 'status.confirmed', fallback: 'Confirmed' },
        },
        {
            value: 'applied',
            label: { key: 'status.applied', fallback: 'Applied' },
        },
        {
            value: 'cancelled',
            label: { key: 'status.cancelled', fallback: 'Cancelled' },
        },
        { value: 'void', label: { key: 'status.void', fallback: 'Void' } },
    ];

    const creditNoteReasonOptions = [
        {
            value: 'return',
            label: {
                key: 'form_fields.credit_notes.reason_return',
                fallback: 'Return',
            },
        },
        {
            value: 'discount',
            label: {
                key: 'form_fields.credit_notes.reason_discount',
                fallback: 'Discount',
            },
        },
        {
            value: 'correction',
            label: {
                key: 'form_fields.credit_notes.reason_correction',
                fallback: 'Correction',
            },
        },
        {
            value: 'bad_debt',
            label: {
                key: 'form_fields.credit_notes.reason_bad_debt',
                fallback: 'Bad Debt',
            },
        },
        {
            value: 'other',
            label: {
                key: 'form_fields.credit_notes.reason_other',
                fallback: 'Other',
            },
        },
    ];

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
