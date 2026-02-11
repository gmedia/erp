import { test, expect } from '@playwright/test';
import { createAccountMapping, findAccountMappingRow, searchAccountMappings } from './helpers';

test('filter account mappings by type end‑to‑end', async ({ page }) => {
  // 1. Create a "Rename" mapping
  // createAccountMapping default creates a 'Rename' mapping (see helpers line 69)
  const { sourceCode, targetCode } = await createAccountMapping(page);

  // 2. Open Filters
  const filterButton = page.getByRole('button', { name: /Filters/i });
  await filterButton.click();

  // 3. Filter by Type: Rename
  const typeTrigger = page.locator('button[role="combobox"]').filter({ hasText: /All Types|Type/i }).first();
  await typeTrigger.click();
  await page.getByRole('option', { name: 'Rename', exact: true }).click();

  // 4. Apply
  const applyButton = page.getByRole('button', { name: /Apply Filters/i });
  const responsePromise = page.waitForResponse(resp => resp.url().includes('/api/account-mappings') && resp.url().includes('type=rename'));
  await applyButton.click();
  await responsePromise;
  // Ensure dialog is closed before proceeding
  await expect(page.getByRole('dialog')).not.toBeVisible();

  // 5. Verify Row is visible
  const row = findAccountMappingRow(page, sourceCode, targetCode);
  await expect(row).toBeVisible();

  // 6. Change filter to "Merge" (should not find our Rename mapping)
  await filterButton.click();
  const filterDialog = page.getByRole('dialog');
  await expect(filterDialog).toBeVisible();
  await page.waitForTimeout(1000); // Give it time to settle the overlay

  // The Type filter is the first combobox in the filter panel
  const typeTriggerInFilter = filterDialog.locator('button[role="combobox"]').first();
  await expect(typeTriggerInFilter).toBeVisible({ timeout: 10000 });
  await typeTriggerInFilter.click();

  // Wait for options and click Merge
  const mergeOption = page.getByRole('option', { name: 'Merge' });
  await expect(mergeOption).toBeVisible({ timeout: 10000 });
  await mergeOption.click();
  await applyButton.click();

  // 7. Verify Row is NOT visible
  await expect(row).not.toBeVisible();

  // 8. Clear Filters
  await filterButton.click();
  const clearAllButton = page.getByRole('button', { name: /Clear All/i });
  await clearAllButton.click();

  // 9. Verify Row is visible again
  await expect(row).toBeVisible();
});

