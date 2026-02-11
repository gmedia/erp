import { test, expect } from '@playwright/test';
import { createJournalEntry, login, searchJournalEntry } from '../helpers';

test('view journal entry works correctly', async ({ page }) => {
  await login(page);
  const reference = `REF-VIEW-${Date.now()}`;
  await createJournalEntry(page, { reference });
  
  await searchJournalEntry(page, reference);
  
  const row = page.locator('tr', { hasText: reference }).first();
  const actionsCell = row.locator('td').last();
  // View is the first button (index 0)
  await actionsCell.locator('button').nth(0).click();
  
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog).toContainText('Journal Entry Details');
  await expect(dialog).toContainText(reference);
});
