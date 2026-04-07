import {
    createDateRangeFilterFields,
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
            'Search',
            'Search reference, notes, product, warehouse...',
        ),
        ...createProductWarehouseFilterFields(),
        createSelectFilterField(
            'movement_type',
            'Movement Type',
            stockMovementTypeOptions,
            'Select movement type',
        ),
        ...createDateRangeFilterFields(),
    ];
}
