import { test, expect } from '@playwright/test';
import { createJournalEntry, login } from '../helpers';

test('filter journal entries end-to-end', async ({ page }) => {
  test.setTimeout(120000);
  await login(page);

  // We need distinct entries
  // But creating takes time. We can just create one unique one and filter for it.
  const uniqueRef = `FILTER-${Date.now()}`;
  await createJournalEntry(page, { reference: uniqueRef, description: 'To Be Filtered' });

  // Clear search if any from create helper (it stays on page)
  // Actually createJournalEntry finishes on the page.
  
  // Open Filter
  const filterBtn = page.getByRole('button', { name: /Filter/i }).first();
  await filterBtn.click();
  
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // Filter by Status (Draft)
  const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Draft|Posted|Void/i });
  await statusTrigger.click();
  await page.getByRole('option', { name: 'Draft', exact: true }).click();
  
  const applyBtn = dialog.getByRole('button', { name: /Apply|Filter/i }).last();
  await applyBtn.click();
  await page.waitForLoadState('networkidle');

  // Verify result contains the unique reference and status is DRAFT
  const row = page.locator('tr', { hasText: uniqueRef }).first();
  await expect(row).toBeVisible();
  await expect(row).toContainText('DRAFT');
});
