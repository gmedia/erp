import { generateModuleTests } from '../shared-test-factories';
import { createUnit, searchUnit, editUnit } from './helpers';

generateModuleTests({
    entityName: 'Unit',
    entityNamePlural: 'Units',
    route: '/units',
    apiPath: '/api/units',

    // Callbacks
    createEntity: (page) => createUnit(page),
    searchEntity: (page, identifier) => searchUnit(page, identifier),
    editEntity: (page, identifier, updates) => editUnit(page, identifier, updates),
    editUpdates: { name: `Unit-Updated-${Date.now()}`, symbol: 'pcs' },

    // DataTable
    sortableColumns: ['Name', 'Created At', 'Updated At'],

    // View
    viewType: 'dialog',
    viewDialogTitle: 'Unit Details',

    // Export
    exportApiPath: '/api/units/export',
    expectedExportColumns: ['ID', 'Name', 'Symbol', 'Created At', 'Updated At'],
});
