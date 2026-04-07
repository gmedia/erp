import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';

export function ApprovalDelegationFilters() {
    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            <InputField
                name="search"
                label="Search..."
                placeholder="Search by reason..."
            />
            <AsyncSelectField
                name="delegator_user_id"
                label="Delegator"
                url="/api/users"
                placeholder="Select delegator..."
            />
            <AsyncSelectField
                name="delegate_user_id"
                label="Delegate"
                url="/api/users"
                placeholder="Select delegate..."
            />
            <SelectField
                name="is_active"
                label="Status"
                options={[
                    { label: 'Active', value: 'true' },
                    { label: 'Inactive', value: 'false' },
                ]}
            />
            <DatePickerField name="start_date_from" label="Start Date From" />
            <DatePickerField name="start_date_to" label="Start Date To" />
        </div>
    );
}

import {
    createApprovalDelegationUserFilterFields,
    createBinaryStatusFilterField,
    createDateFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createApprovalDelegationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search reason...'),
        ...createApprovalDelegationUserFilterFields(),
        createBinaryStatusFilterField(
            'is_active',
            'Status',
            'true',
            'false',
            'Select status',
        ),
        ...createDateFilterFields([
            {
                name: 'start_date_from',
                label: 'Start Date From',
                placeholder: 'Start Date From',
            },
            {
                name: 'start_date_to',
                label: 'Start Date To',
                placeholder: 'Start Date To',
            },
        ]),
    ];
}
