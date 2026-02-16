import { generateModuleTests } from '../shared-test-factories';
import { createBranch, searchBranch, editBranch } from './helpers';

generateModuleTests({
    entityName: 'Branch',
    entityNamePlural: 'Branches',
    route: '/branches',
    apiPath: '/api/branches',

    // Callbacks
    createEntity: (page) => createBranch(page),
    searchEntity: (page, identifier) => searchBranch(page, identifier),
    editEntity: (page, identifier, updates) => editBranch(page, identifier, updates),
    editUpdates: { name: `Branch-Updated-${Date.now()}` },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Branch Details',

    // Export
    exportApiPath: '/api/branches/export',
    expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
});
