import { generateModuleTests } from '../shared-test-factories';
import { createProductCategory, searchProductCategory, editProductCategory } from './helpers';

generateModuleTests({
    entityName: 'Product Category',
    entityNamePlural: 'Product Categories',
    route: '/product-categories',
    apiPath: '/api/product-categories',

    // Callbacks
    createEntity: (page) => createProductCategory(page),
    searchEntity: (page, identifier) => searchProductCategory(page, identifier),
    editEntity: (page, identifier, updates) => editProductCategory(page, identifier, updates),
    editUpdates: { name: `ProdCat-Updated-${Date.now()}`, description: 'Updated Description' },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Product Category Details',

    // Export
    exportApiPath: '/api/product-categories/export',
    expectedExportColumns: ['ID', 'Name', 'Description', 'Created At', 'Updated At'],
});
