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
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.pipeline_audit_trail.search_placeholder',
                fallback: 'Search entity ID, performer, comment...',
            },
        ),
        // NOTE: We'll use a text field for entity type for simplicity, or it could be a select if we know the exact types
        createTextFilterField(
            'entity_type',
            { key: 'filter_field.entity_type', fallback: 'Entity Type' },
            'e.g. Asset, Employee',
        ),
        createPipelineFilterField(),
        createUserFilterField('performed_by', {
            key: 'filter_field.performed_by',
            fallback: 'Performed By',
        }),
        ...createDateRangeFilterFields(),
    ];
}
