'use client';

import { createCustomerBranchFilterFields } from '@/components/common/customer-branch-filters';
import {
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
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
        createTextFilterField(
            'search',
            'Search',
            'Search credit note number, customer, or notes...',
        ),
        ...createCustomerBranchFilterFields(),
        ...createSelectFilterFields([
            {
                name: 'reason',
                label: 'Reason',
                options: creditNoteReasonOptions,
                placeholder: 'Select Reason',
            },
            {
                name: 'status',
                label: 'Status',
                options: creditNoteStatusOptions,
                placeholder: 'Select Status',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'credit_note_date_from',
                label: 'Credit Note Date From',
                placeholder: 'Credit Note Date From',
            },
            {
                name: 'credit_note_date_to',
                label: 'Credit Note Date To',
                placeholder: 'Credit Note Date To',
            },
        ]),
    ];
}
