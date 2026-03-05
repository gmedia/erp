import { generateModuleTests } from '../shared-test-factories';
import {
    createStockAdjustment,
    editStockAdjustment,
    searchStockAdjustment,
} from './helpers';

generateModuleTests({
    entityName: 'Stock Adjustment',
    entityNamePlural: 'Stock Adjustments',
    route: '/stock-adjustments',
    apiPath: '/api/stock-adjustments',

    createEntity: (page) => createStockAdjustment(page),
    searchEntity: (page, identifier) => searchStockAdjustment(page, identifier),
    editEntity: (page, identifier, updates) =>
        editStockAdjustment(page, identifier, updates),
    editUpdates: { adjustment_number: `SA-UPDATED-${Date.now()}` },

    sortableColumns: [
        'Adjustment Number',
        'Warehouse',
        'Adjustment Date',
        'Adjustment Type',
        'Status',
    ],

    viewType: 'dialog',

    exportApiPath: '/api/stock-adjustments/export',
    expectedExportColumns: [
        'ID',
        'Adjustment Number',
        'Warehouse',
        'Adjustment Date',
        'Adjustment Type',
        'Status',
        'Stocktake Number',
        'Created At',
    ],
});
