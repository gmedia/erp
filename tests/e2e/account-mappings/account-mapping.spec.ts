import { test } from '@playwright/test';
import { generateModuleTests } from '../shared-test-factories';
import { createAccountMapping, searchAccountMappings, editAccountMapping } from './helpers';

// Account mapping creation involves 5 combobox selections with async loading,
// which can exceed the default 60s timeout.
test.setTimeout(120_000);

generateModuleTests({
  entityName: 'Account Mapping',
  entityNamePlural: 'Account Mappings',
  route: '/account-mappings',
  apiPath: '/api/account-mappings',

  // Callbacks
  createEntity: async (page) => {
    const { notes } = await createAccountMapping(page);
    return notes;
  },
  searchEntity: (page, identifier) => searchAccountMappings(page, identifier),
  editEntity: (page, identifier, updates) => editAccountMapping(page, identifier, updates),
  editUpdates: { notes: 'UPDATED-NOTES' }, // Changing notes to test edit

  // DataTable
  sortableColumns: ['Source Account', 'Target Account', 'Type', 'Notes', 'Created At'],

  // View
  viewType: 'dialog',
  viewDialogTitle: 'View Account Mapping',

  // Export
  exportApiPath: '/api/account-mappings/export',
  expectedExportColumns: [
    'ID',
    'Type',
    'Source COA Version',
    'Source Account Code',
    'Source Account Name',
    'Target COA Version',
    'Target Account Code',
    'Target Account Name',
    'Notes',
    'Created At',
    'Updated At',
  ],
});
