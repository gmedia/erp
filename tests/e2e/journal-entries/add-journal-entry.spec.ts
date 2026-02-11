import { test, expect } from '@playwright/test';
import { createJournalEntry, searchJournalEntry } from '../helpers';

test('add new journal entry end-to-end', async ({ page }) => {
  test.setTimeout(120000);
  // Create journal entry using helper
  const reference = await createJournalEntry(page, {
     description: 'E2E Test Entry',
     lines: [
         { account: 'Cash in Banks', debit: '5000', credit: '0', memo: 'Debit Cash' },
         { account: 'Sales Revenue', debit: '0', credit: '5000', memo: 'Credit Sales' },
     ]
  });

  // Search for the newly created entry
  await searchJournalEntry(page, reference);

  // Verify the entry appears in the table
  const row = page.locator('tr', { hasText: reference }).first();
  await expect(row).toBeVisible();
  await expect(row).toContainText('E2E Test Entry');
  // Check total amount formatting (Rp 5.000,00)
  await expect(row).toContainText(/Rp\s*5\.000,00/);
});
