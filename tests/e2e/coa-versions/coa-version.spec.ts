import { generateModuleTests } from '../shared-test-factories';
import { createCoaVersion, searchCoaVersion, editCoaVersion } from './helpers';

generateModuleTests({
  entityName: 'COA Version',
  entityNamePlural: 'COA Versions',
  route: '/coa-versions',
  apiPath: '/api/coa-versions',

  // Callbacks
  createEntity: (page) => createCoaVersion(page),
  searchEntity: (page, identifier) => searchCoaVersion(page, identifier),
  editEntity: (page, identifier, updates) => editCoaVersion(page, identifier, updates),
  editUpdates: { name: `COAV-Updated-${Date.now()}` },

  // DataTable
  sortableColumns: ['Name', 'Fiscal Year', 'Status', 'Created At'],

  // View
  viewType: 'dialog',
  viewDialogTitle: 'View COA Version',

  // Export
  exportApiPath: '/api/coa-versions/export',
  expectedExportColumns: ['ID', 'Name', 'Fiscal Year', 'Status', 'Created At', 'Updated At'],
});
