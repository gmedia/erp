import { test, expect } from '@playwright/test';
import { createJournalEntry, editJournalEntry, searchJournalEntry } from '../helpers';

test('edit journal entry end-to-end', async ({ page }) => {
  test.setTimeout(120000);
  const reference = await createJournalEntry(page);

  await editJournalEntry(page, reference, { description: 'Updated E2E Description' });

  await searchJournalEntry(page, reference);
  await expect(page.locator(`text=Updated E2E Description`)).toBeVisible();
});
