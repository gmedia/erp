import {
    createDateRangeFilterFields,
    createPipelineFilterField,
    createTextFilterField,
    createUserFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPipelineAuditTrailFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search entity ID, performer, comment...',
        ),
        // NOTE: We'll use a text field for entity type for simplicity, or it could be a select if we know the exact types
        createTextFilterField(
            'entity_type',
            'Entity Type',
            'e.g. Asset, Employee',
        ),
        createPipelineFilterField(),
        createUserFilterField('performed_by', 'Performed By'),
        ...createDateRangeFilterFields(),
    ];
}
