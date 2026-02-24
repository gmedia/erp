import { generateModuleTests } from '../shared-test-factories';
import { createPipeline, searchPipeline, editPipeline } from './helpers';

generateModuleTests({
    entityName: 'Pipeline',
    entityNamePlural: 'Pipelines',
    route: '/pipelines',
    apiPath: '/api/pipelines',

    createEntity: createPipeline,
    searchEntity: searchPipeline,
    editEntity: editPipeline,
    editUpdates: {
        name: 'Updated Pipeline Name',
        is_active: false,
    },

    // View config
    viewType: 'dialog',
    viewDialogTitle: 'View Pipeline',

    // DataTable columns expected to be sortable
    sortableColumns: ['Name', 'Code', 'Entity', 'Version', 'Creator', 'Status'],

    // Export validation
    exportApiPath: '/api/pipelines/export',
    expectedExportColumns: [
        'ID',
        'Name',
        'Code',
        'Entity Type',
        'Version',
        'Active',
        'Created By',
        'Created At',
    ],
    
    // We can omit testing filters in this basic transition if there are no complex comboboxes
});
