import { generateModuleTests } from '../shared-test-factories';
import { createCustomerCategory, searchCustomerCategory, editCustomerCategory } from './helpers';

generateModuleTests({
    entityName: 'Customer Category',
    entityNamePlural: 'Customer Categories',
    route: '/customer-categories',
    apiPath: '/api/customer-categories',

    // Callbacks
    createEntity: (page) => createCustomerCategory(page),
    searchEntity: (page, identifier) => searchCustomerCategory(page, identifier),
    editEntity: (page, identifier, updates) => editCustomerCategory(page, identifier, updates),
    editUpdates: { name: `CustCat-Updated-${Date.now()}` },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Customer Category Details',

    // Export
    exportApiPath: '/api/customer-categories/export',
    expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
});
