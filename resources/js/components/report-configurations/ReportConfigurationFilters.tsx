import {
    createSelectFilterField,
    createTextFilterField,
    translatedOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

const reportTypeOptions = translatedOptions(
    'page.report_configurations.view_report_type_',
    [
        ['balance_sheet', 'Balance Sheet'],
        ['income_statement', 'Income Statement'],
        ['cash_flow', 'Cash Flow'],
        ['trial_balance', 'Trial Balance'],
        ['custom', 'Custom'],
    ],
);

const activeStatusOptions = translatedOptions(
    'page.report_configurations.view_',
    [
        ['1', 'Active', 'page.report_configurations.view_active'],
        ['0', 'Inactive', 'page.report_configurations.view_inactive'],
    ],
);

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
