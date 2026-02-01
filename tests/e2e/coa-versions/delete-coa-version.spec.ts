import { test, expect } from '@playwright/test';
import { createCoaVersion, searchCoaVersion } from '../helpers';

test('delete coa version end‑to‑end', async ({ page }) => {
  // 1️⃣ Create a temporary coa version to delete
  const name = await createCoaVersion(page);

  // 2️⃣ Locate the coa version in the list
  await searchCoaVersion(page, name);
  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();

  // 3️⃣ Open the Actions menu and click Delete
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await deleteItem.click();

  // 4️⃣ Confirm the deletion in the alert/dialog
  const confirmBtn = page.getByRole('button', { name: /Delete/i }).last();
  await confirmBtn.click();

  // 5️⃣ Verify the coa version is no longer in the list
  await page.fill('input[placeholder="Search COA versions..."]', name);
  await page.press('input[placeholder="Search COA versions..."]', 'Enter');
  
  // The row should eventually disappear
  await expect(page.locator(`text=${name}`)).not.toBeVisible();
});
