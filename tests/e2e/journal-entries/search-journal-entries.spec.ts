import { test, expect } from '@playwright/test';
import { createJournalEntry, login, searchJournalEntry } from '../helpers';

test.describe('Journal Entry Search', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should search by entry number', async ({ page }) => {
    const timestamp = Date.now();
    const reference = `REF-SEARCH-NUM-${timestamp}`;
    await createJournalEntry(page, { reference });
    
    // Get the entry number from the list
    const row = page.locator('tr', { hasText: reference }).first();
    const entryNumber = await row.locator('td').nth(1).textContent(); 
    
    await page.reload();
    await searchJournalEntry(page, entryNumber!.trim());
    await expect(page.locator('tr').filter({ hasText: entryNumber!.trim() })).toBeVisible();
  });

  test('should search by reference', async ({ page }) => {
    const timestamp = Date.now();
    const reference = `REF-SEARCH-REF-${timestamp}`;
    await createJournalEntry(page, { reference });
    
    await page.reload();
    await searchJournalEntry(page, reference);
    await expect(page.locator('tr').filter({ hasText: reference })).toBeVisible();
  });

  test('should search by description', async ({ page }) => {
    const timestamp = Date.now();
    const description = `DESC-SEARCH-${timestamp}`;
    await createJournalEntry(page, { description });
    
    await page.reload();
    await searchJournalEntry(page, description);
    await expect(page.locator('tr').filter({ hasText: description })).toBeVisible();
  });
});
