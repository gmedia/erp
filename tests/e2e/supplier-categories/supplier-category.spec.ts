import { generateModuleTests } from '../shared-test-factories';
import { createSupplierCategory, searchSupplierCategory, editSupplierCategory } from './helpers';

generateModuleTests({
    entityName: 'Supplier Category',
    entityNamePlural: 'Supplier Categories',
    route: '/supplier-categories',
    apiPath: '/api/supplier-categories',

    // Callbacks
    createEntity: (page) => createSupplierCategory(page),
    searchEntity: (page, identifier) => searchSupplierCategory(page, identifier),
    editEntity: (page, identifier, updates) => editSupplierCategory(page, identifier, updates),
    editUpdates: { name: `SupCat-Updated-${Date.now()}` },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Supplier Category Details',

    // Export
    exportApiPath: '/api/supplier-categories/export',
    expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
});
