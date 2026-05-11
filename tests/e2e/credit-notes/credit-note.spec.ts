import { test } from '@playwright/test';

import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import { createCreditNote, editCreditNote, searchCreditNote } from './helpers';

const config: ModuleTestConfig = {
    entityName: 'Credit Note',
    entityNamePlural: 'Credit Notes',
    route: '/credit-notes',
    apiPath: '/api/credit-notes',
    exportApiPath: '/api/credit-notes/export',
    createEntity: createCreditNote,
    searchEntity: searchCreditNote,
    editEntity: editCreditNote,
    editUpdates: { credit_note_number: `CN-E2E-UPDATED-${Date.now()}` },
    expectedExportColumns: [
        'ID', 'Credit Note Number', 'Customer', 'Branch', 'Credit Note Date',
        'Reason', 'Status', 'Subtotal', 'Tax Amount', 'Grand Total',
        'Notes', 'Created At',
    ],
    sortableColumns: ['Credit Note Number', 'Customer', 'Branch', 'Credit Note Date', 'Reason', 'Status', 'Grand Total'],
    viewType: 'dialog',
    viewDialogTitle: 'Credit Note Details',
};

test.describe('Credit Notes Module', () => {
    generateModuleTests(config);
});
