import {
    createDateFilterFields,
    createProductWarehouseFilterFields,
    createSelectFilterField,
    createTextFilterField,
    stockMovementTypeOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockMovementsFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.stock_movements.search_placeholder',
                fallback: 'Search reference, notes, product, warehouse...',
            },
        ),
        ...createProductWarehouseFilterFields(),
        createSelectFilterField(
            'movement_type',
            { key: 'column.movement_type', fallback: 'Movement Type' },
            stockMovementTypeOptions,
            {
                key: 'filter.select_movement_type',
                fallback: 'Select movement type',
            },
        ),
        ...createDateFilterFields([
            {
                name: 'start_date',
                label: { key: 'column.start_date', fallback: 'Start Date' },
                placeholder: {
                    key: 'column.start_date',
                    fallback: 'Start Date',
                },
            },
            {
                name: 'end_date',
                label: { key: 'column.end_date', fallback: 'End Date' },
                placeholder: { key: 'column.end_date', fallback: 'End Date' },
            },
        ]),
    ];
}
