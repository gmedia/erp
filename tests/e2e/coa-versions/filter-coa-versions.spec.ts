import { test, expect } from '@playwright/test';
import { login, createCoaVersion } from '../helpers';

test('filter coa versions end‑to‑end', async ({ page }) => {
  // 1️⃣ Ensure at least one COA version exists with a specific status
  const name = await createCoaVersion(page, { status: 'Draft' });

  // 2️⃣ Open the filter modal
  const filterButton = page.getByRole('button', { name: /Filters/i });
  await filterButton.click();

  // 3️⃣ Select the 'Draft' status filter
  const statusTrigger = page.locator('button[role="combobox"]').filter({ hasText: /All Statuses|Status/i }).first();
  await statusTrigger.click();
  await page.getByRole('option', { name: 'Draft', exact: true }).click();

  // 4️⃣ Apply the filter and wait for response
  const applyButton = page.getByRole('button', { name: /Apply Filters/i });
  const responsePromise = page.waitForResponse(resp => resp.url().includes('/api/coa-versions') && resp.url().includes('status=draft'));
  await applyButton.click();
  await responsePromise;

  // 5️⃣ Verify the COA version appears in the filtered list
  await expect(page.locator('tr', { hasText: name }).first()).toBeVisible();

  // 6️⃣ Reset filters
  await filterButton.click();
  const resetResponsePromise = page.waitForResponse(resp => resp.url().includes('/api/coa-versions'));
  const clearAllButton = page.getByRole('button', { name: /Clear All/i });
  await clearAllButton.click();
  await resetResponsePromise;

  // 7️⃣ Verify still visible
  await expect(page.locator('tr', { hasText: name }).first()).toBeVisible();
});
