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
                    {
                        label: { key: 'status.active', fallback: 'Active' },
                        value: 'true',
                    },
                    {
                        label: { key: 'status.inactive', fallback: 'Inactive' },
                        value: 'false',
                    },
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
        createTextFilterField(
            'search',
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.approval_delegations.search_placeholder',
                fallback: 'Search reason...',
            },
        ),
        ...createApprovalDelegationUserFilterFields(),
        createBinaryStatusFilterField(
            'is_active',
            { key: 'filter_field.status', fallback: 'Status' },
            'true',
            'false',
            { key: 'filter_field.select_status', fallback: 'Select status' },
        ),
        ...createDateFilterFields([
            {
                name: 'start_date_from',
                label: {
                    key: 'filter_field.start_date_from',
                    fallback: 'Start Date From',
                },
                placeholder: {
                    key: 'filter_field.start_date_from',
                    fallback: 'Start Date From',
                },
            },
            {
                name: 'start_date_to',
                label: {
                    key: 'filter_field.start_date_to',
                    fallback: 'Start Date To',
                },
                placeholder: {
                    key: 'filter_field.start_date_to',
                    fallback: 'Start Date To',
                },
            },
        ]),
    ];
}
