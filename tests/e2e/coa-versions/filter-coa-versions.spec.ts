import { test, expect } from '@playwright/test';
import { login, createCoaVersion } from '../helpers';

test('filter coa versions end‑to‑end', async ({ page }) => {
  // 1️⃣ Ensure at least one COA version exists with a specific status
  const name = await createCoaVersion(page, { status: 'Draft' });

  // 2️⃣ Open the filter popover
  const filterButton = page.getByRole('button', { name: /Filter/i });
  await filterButton.click();

  // 3️⃣ Select the 'Draft' status filter
  const statusTrigger = page.locator('button').filter({ hasText: /All Statuses|Draft|Active|Archived/i });
  await statusTrigger.click();
  await page.getByRole('option', { name: 'Draft', exact: true }).click();

  // 4️⃣ Apply the filter
  const applyButton = page.getByRole('button', { name: /Apply/i });
  await applyButton.click();

  // 5️⃣ Verify the COA version appears in the filtered list
  await expect(page.locator('tr', { hasText: name }).first()).toBeVisible();

  // 6️⃣ Change filter to 'Active' (assuming we don't have many active ones, or at least the one we created should disappear)
  await filterButton.click();
  await statusTrigger.click();
  await page.getByRole('option', { name: 'Active', exact: true }).click();
  await applyButton.click();

  // 7️⃣ Verify the 'Draft' COA version is no longer visible
  await expect(page.locator('tr', { hasText: name }).first()).not.toBeVisible();

  // 8️⃣ Reset filters
  await filterButton.click();
  const clearAllButton = page.getByRole('button', { name: /Clear All/i });
  await clearAllButton.click();

  // 9️⃣ Verify the 'Draft' COA version is visible again
  await expect(page.locator('tr', { hasText: name }).first()).toBeVisible();
});
