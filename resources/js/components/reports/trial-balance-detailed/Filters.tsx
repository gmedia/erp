import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createTrialBalanceDetailedFilterFields(): FieldDescriptor[] {
    const months = [
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

    return [
        {
            name: 'fiscal_year_id',
            label: 'Fiscal Year',
            component: (
                <AsyncSelect
                    url="/api/fiscal-years"
                    placeholder="Select fiscal year"
                    preferredMetaKey="preferred_fiscal_year_id"
                />
            ),
        },
        createSelectFilterField('period_month', 'Month', months, 'All months'),
    ];
}
