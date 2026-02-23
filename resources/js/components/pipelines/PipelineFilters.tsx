import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPipelineFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search name, code, or description...'),
        createSelectFilterField(
            'entity_type',
            'Entity Type',
            [
                { value: 'App\\Models\\Asset', label: 'Asset' },
                { value: 'App\\Models\\PurchaseOrder', label: 'Purchase Order' },
                { value: 'App\\Models\\PurchaseRequest', label: 'Purchase Request' },
                { value: 'App\\Models\\JournalEntry', label: 'Journal Entry' },
            ],
            'All Entities'
        ),
        createSelectFilterField(
            'is_active',
            'Status',
            [
                { value: 'true', label: 'Active' },
                { value: 'false', label: 'Inactive' },
            ],
            'All Statuses'
        ),
    ];
}
