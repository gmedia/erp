import { generateModuleTests } from '../shared-test-factories';
import { createBudget, searchBudget, editBudget } from './helpers';

generateModuleTests({
  entityName: 'Budget',
  entityNamePlural: 'Budgets',
  route: '/budgets',
  apiPath: '/api/budgets',

  createEntity: (page) => createBudget(page),
  searchEntity: (page, identifier) => searchBudget(page, identifier),
  editEntity: (page, identifier, updates) => editBudget(page, identifier, updates),
  editUpdates: { name: `Budget-Updated-${Date.now()}` },

  sortableColumns: ['Name', 'Budget Type', 'Status', 'Total Amount', 'Created At'],

  viewType: 'dialog',
  viewDialogTitle: 'Budget Details',

  exportApiPath: '/api/budgets/export',
  expectedExportColumns: [
    'ID',
    'Name',
    'Fiscal Year',
    'Budget Type',
    'Status',
    'Total Amount',
    'Created By',
    'Created At',
  ],

  filterTests: [
    {
      filterName: 'Status',
      filterType: 'select',
      filterValue: 'Draft',
      expectedText: 'Draft',
    },
    {
      filterName: 'Budget Type',
      filterType: 'select',
      filterValue: 'Operational',
      expectedText: 'Operational',
    },
  ],
});
