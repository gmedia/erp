import { generateModuleTests } from '../shared-test-factories';
import {
    createInventoryStocktake,
    editInventoryStocktake,
    searchInventoryStocktake,
} from './helpers';

generateModuleTests({
    entityName: 'Inventory Stocktake',
    entityNamePlural: 'Inventory Stocktakes',
    route: '/inventory-stocktakes',
    apiPath: '/api/inventory-stocktakes',

    createEntity: (page) => createInventoryStocktake(page),
    searchEntity: (page, identifier) => searchInventoryStocktake(page, identifier),
    editEntity: (page, identifier, updates) => editInventoryStocktake(page, identifier, updates),
    editUpdates: { stocktake_number: `SO-UPDATED-${Date.now()}` },

    sortableColumns: [
        'Stocktake Number',
        'Warehouse',
        'Product Category',
        'Stocktake Date',
        'Status',
    ],

    viewType: 'dialog',

    exportApiPath: '/api/inventory-stocktakes/export',
    expectedExportColumns: [
        'ID',
        'Stocktake Number',
        'Warehouse',
        'Stocktake Date',
        'Status',
        'Product Category',
        'Completed At',
        'Created At',
    ],
});

