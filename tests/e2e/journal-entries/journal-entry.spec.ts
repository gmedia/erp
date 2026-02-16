import { generateModuleTests } from '../shared-test-factories';
import { createJournalEntry, searchJournalEntry, editJournalEntry, viewJournalEntry, deleteJournalEntry } from './helpers';

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
  
  sortableColumns: ['Entry Number', 'Date', 'Description', 'Reference', 'Total Amount', 'Status'],
  
  viewType: 'dialog',
  viewDialogTitle: 'Journal Entry Details',
  
  expectedExportColumns: ['ID', 'Entry Number', 'Date', 'Reference', 'Description', 'Total Amount', 'Fiscal Year', 'Status', 'Created By', 'Created At'],
  
  // Custom actions pattern for Journal Entries (icon buttons instead of dropdown)
  actionsPattern: 'icon-buttons',
  customViewAction: async (page) => {
      // Logic is handled inside viewJournalEntry per entity, but factory expects a page-level action or per-row
      // The factory's `customViewAction` is called after creating and searching.
      // So we just need to find the row and click view.
      // Since we don't have the identifier here easily without passing it, 
      // we can use the `viewJournalEntry` helper if we knew the identifier.
      // However, the factory structure for custom actions is:
      // if (config.actionsPattern === 'icon-buttons' && config.customViewAction) { await config.customViewAction(page); }
      // The factory doesn't pass the identifier to customViewAction.
      // But the test context has `identifier` variable.
      
      // WAIT. The factory definition:
      // customViewAction?: (page: Page) => Promise<void>;
      // It DOES NOT accept identifier.
      // But the test scafolding in `generateModuleTests` does:
      // const identifier = await config.createEntity(page);
      // await config.searchEntity(page, identifier);
      // 
      // If we use the helper `viewJournalEntry`, we need the identifier.
      // We can try to assume the row is visible and just click the first one?
      // "View" test in factory:
      // await config.searchEntity(page, identifier);
      // ...
      // await config.customViewAction(page);
      
      // So the row is already filtered to showing the specific entity.
      // We can just click the first row's view button.
      
      const firstRow = page.locator('tbody tr').first();
      await firstRow.getByRole('button').first().click(); // Eye is usually first
  },
  
  customEditAction: async (page) => {
      // Similar to view, row is isolated.
      const firstRow = page.locator('tbody tr').first();
      // Edit is usually second button (Pencil)
      await firstRow.getByRole('button').nth(1).click();
  },
  
  customDeleteAction: async (page) => {
      // Similar to view, row is isolated.
      const firstRow = page.locator('tbody tr').first();
      // Delete is usually third button (Trash)
      await firstRow.getByRole('button').last().click();
  },

  filterTests: [
      {
          filterName: 'Status',
          filterType: 'select',
          filterValue: 'draft',
          expectedText: 'DRAFT',
      }
  ]
});
