import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

const reportTypeOptions: SelectOption[] = [
    {
        value: 'balance_sheet',
        label: {
            key: 'page.report_configurations.view_report_type_balance_sheet',
            fallback: 'Balance Sheet',
        },
    },
    {
        value: 'income_statement',
        label: {
            key: 'page.report_configurations.view_report_type_income_statement',
            fallback: 'Income Statement',
        },
    },
    {
        value: 'cash_flow',
        label: {
            key: 'page.report_configurations.view_report_type_cash_flow',
            fallback: 'Cash Flow',
        },
    },
    {
        value: 'trial_balance',
        label: {
            key: 'page.report_configurations.view_report_type_trial_balance',
            fallback: 'Trial Balance',
        },
    },
    {
        value: 'custom',
        label: {
            key: 'page.report_configurations.view_report_type_custom',
            fallback: 'Custom',
        },
    },
];

const activeStatusOptions: SelectOption[] = [
    {
        value: '1',
        label: {
            key: 'page.report_configurations.view_active',
            fallback: 'Active',
        },
    },
    {
        value: '0',
        label: {
            key: 'page.report_configurations.view_inactive',
            fallback: 'Inactive',
        },
    },
];

export function createReportConfigurationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.report_configurations.search_placeholder',
                fallback: 'Search code, name, description...',
            },
        ),
        createSelectFilterField(
            'report_type',
            {
                key: 'page.report_configurations.view_report_type',
                fallback: 'Report Type',
            },
            reportTypeOptions,
            {
                key: 'filter_field.select_report_type',
                fallback: 'Select Report Type',
            },
        ),
        createSelectFilterField(
            'is_active',
            { key: 'filter_field.status', fallback: 'Status' },
            activeStatusOptions,
            { key: 'filter_field.select_status', fallback: 'Select Status' },
        ),
    ];
}
