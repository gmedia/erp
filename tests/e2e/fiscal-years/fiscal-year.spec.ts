import { generateModuleTests } from '../shared-test-factories';
import { createFiscalYear, searchFiscalYear, editFiscalYear } from './helpers';

generateModuleTests({
  entityName: 'Fiscal Year',
  entityNamePlural: 'Fiscal Years',
  route: '/fiscal-years',
  apiPath: '/api/fiscal-years',

  // Callbacks
  createEntity: (page) => createFiscalYear(page),
  searchEntity: (page, identifier) => searchFiscalYear(page, identifier),
  editEntity: (page, identifier, updates) => editFiscalYear(page, identifier, updates),
  editUpdates: { name: `FY-Updated-${Date.now()}` },

  // DataTable
  sortableColumns: ['Name', 'Start Date', 'End Date', 'Status', 'Created At'],

  // View
  viewType: 'dialog',
  viewDialogTitle: 'View Fiscal Year',

  // Export
  exportApiPath: '/api/fiscal-years/export',
  expectedExportColumns: ['ID', 'Name', 'Start Date', 'End Date', 'Status', 'Created At', 'Updated At'],
});
