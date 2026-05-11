import {
    createAsyncSelectFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCustomerBranchFilterFields(): FieldDescriptor[] {
    return [
        createAsyncSelectFilterField(
            'customer_id',
            'Customer',
            '/api/customers',
            'Select Customer',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select Branch',
        ),
    ];
}
