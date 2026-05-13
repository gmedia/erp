import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const reportTypeOptions = [
    { value: 'balance_sheet', label: 'Balance Sheet' },
    { value: 'income_statement', label: 'Income Statement' },
    { value: 'cash_flow', label: 'Cash Flow' },
    { value: 'trial_balance', label: 'Trial Balance' },
    { value: 'custom', label: 'Custom' },
];

const activeStatusOptions = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

export function createReportConfigurationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search code, name, description...',
        ),
        createSelectFilterField(
            'report_type',
            'Report Type',
            reportTypeOptions,
            'Select Report Type',
        ),
        createSelectFilterField(
            'is_active',
            'Status',
            activeStatusOptions,
            'Select Status',
        ),
    ];
}
