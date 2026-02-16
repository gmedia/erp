import { generateModuleTests } from '../shared-test-factories';
import { createPosition, searchPosition, editPosition } from './helpers';

generateModuleTests({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    route: '/positions',
    apiPath: '/api/positions',

    // Callbacks
    createEntity: (page) => createPosition(page),
    searchEntity: (page, identifier) => searchPosition(page, identifier),
    editEntity: (page, identifier, updates) => editPosition(page, identifier, updates),
    editUpdates: { name: `Pos-Updated-${Date.now()}` },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Position Details',

    // Export
    exportApiPath: '/api/positions/export',
    expectedExportColumns: ['ID', 'Name', 'Created At', 'Updated At'],
});
