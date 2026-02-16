import { generateModuleTests } from '../shared-test-factories';
import { createDepartment, searchDepartment, editDepartment } from './helpers';

generateModuleTests({
    entityName: 'Department',
    entityNamePlural: 'Departments',
    route: '/departments',
    apiPath: '/api/departments',

    // Callbacks
    createEntity: (page) => createDepartment(page),
    searchEntity: (page, identifier) => searchDepartment(page, identifier),
    editEntity: (page, identifier, updates) => editDepartment(page, identifier, updates),
    editUpdates: { name: `Dept-Updated-${Date.now()}` },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Department Details',

    // Export
    exportApiPath: '/api/departments/export',
    expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
});
