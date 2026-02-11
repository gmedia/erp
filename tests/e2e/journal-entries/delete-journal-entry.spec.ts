import { test, expect } from '@playwright/test';
import { createJournalEntry, searchJournalEntry } from '../helpers';

test('delete journal entry end-to-end', async ({ page }) => {
  test.setTimeout(120000);
  const reference = await createJournalEntry(page);

  await searchJournalEntry(page, reference);
  const row = page.locator('tr', { hasText: reference }).first();
  await expect(row).toBeVisible();

  const actionsCell = row.locator('td').last();
  // Delete is the third button (index 2)
  await actionsCell.locator('button').nth(2).click();

  const confirmBtn = page.getByRole('button', { name: /Delete|Confirm/i });
  await confirmBtn.click();

  // Verify deletion
  await expect(page.locator(`text=${reference}`)).toBeHidden();
});
