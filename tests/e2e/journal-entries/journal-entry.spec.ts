import { generateModuleTests } from '../shared-test-factories';
import {
    createJournalEntry,
    editJournalEntry,
    searchJournalEntry,
} from './helpers';

generateModuleTests({
    entityName: 'Journal Entry',
    entityNamePlural: 'Journal Entries',
    route: '/journal-entries',
    apiPath: '/api/journal-entries',
    exportApiPath: '/api/journal-entries/export',

    createEntity: createJournalEntry,
    searchEntity: searchJournalEntry,
    editEntity: editJournalEntry,

    editUpdates: {
        description: 'Updated Journal Entry Description',
    },

    sortableColumns: [
        'Entry Number',
        'Date',
        'Description',
        'Reference',
        'Total Amount',
        'Status',
    ],

    viewType: 'dialog',
    viewDialogTitle: 'Journal Entry Details',

    expectedExportColumns: [
        'ID',
        'Entry Number',
        'Date',
        'Reference',
        'Description',
        'Total Amount',
        'Fiscal Year',
        'Status',
        'Created By',
        'Created At',
    ],

    filterTests: [
        {
            filterName: 'Status',
            filterType: 'select',
            filterValue: 'draft',
            expectedText: 'DRAFT',
        },
    ],
});
