import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const monthOptions = [
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

export function createTrialBalanceFilterFields(): FieldDescriptor[] {
    const currentYear = new Date().getFullYear();
    const yearOptions = Array.from({ length: 10 }, (_, i) => ({
        value: String(currentYear - i),
        label: String(currentYear - i),
    }));

    return [
        {
            name: 'fiscal_year_id',
            label: 'Fiscal Year (Required)',
            component: (
                <AsyncSelect
                    url="/api/fiscal-years"
                    placeholder="Select Fiscal Year"
                />
            ),
        },
        createSelectFilterField(
            'period_month',
            'Period Month',
            monthOptions,
            'Select Month',
        ),
        createSelectFilterField(
            'period_year',
            'Period Year',
            yearOptions,
            'Select Year',
        ),
    ];
}
