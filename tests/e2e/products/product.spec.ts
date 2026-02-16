import { generateModuleTests } from '../shared-test-factories';
import { createProduct, searchProduct, editProduct } from './helpers';

generateModuleTests({
    entityName: 'Product',
    entityNamePlural: 'Products',
    route: '/products',
    apiPath: '/api/products',

    // Callbacks
    createEntity: (page) => createProduct(page),
    searchEntity: (page, identifier) => searchProduct(page, identifier),
    editEntity: (page, identifier, updates) => editProduct(page, identifier, updates),
    editUpdates: { name: `Product-Updated-${Date.now()}`, selling_price: '2000' },

    // DataTable
    sortableColumns: ['Code', 'Name', 'Type', 'Category', 'Cost', 'Price', 'Status'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Product Details',

    // Export
    exportApiPath: '/api/products/export',
    expectedExportColumns: ['ID', 'Code', 'Name', 'Type', 'Category', 'Unit', 'Cost', 'Selling Price', 'Status', 'Created At'],
});
