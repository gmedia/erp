import { generateModuleTests } from '../shared-test-factories';
import { createWarehouse, searchWarehouse, editWarehouse } from './helpers';

generateModuleTests({
    entityName: 'Warehouse',
    entityNamePlural: 'Warehouses',
    route: '/warehouses',
    apiPath: '/api/warehouses',

    createEntity: (page) => createWarehouse(page),
    searchEntity: (page, identifier) => searchWarehouse(page, identifier),
    editEntity: (page, identifier, updates) => editWarehouse(page, identifier, updates),
    editUpdates: { name: `Warehouse-Updated-${Date.now()}` },

    sortableColumns: ['Name', 'Created At', 'Updated At'],

    viewType: 'dialog',
    viewDialogTitle: 'Warehouse Details',

    exportApiPath: '/api/warehouses/export',
    expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
});
