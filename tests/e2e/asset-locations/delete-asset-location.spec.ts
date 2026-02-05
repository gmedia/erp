import { test, expect } from '@playwright/test';
import { createAssetLocation, login, searchAssetLocation } from '../helpers';

test('delete asset location end-to-end', async ({ page }) => {
  const name = await createAssetLocation(page);

  await login(page);
  await page.goto('/asset-locations');

  await searchAssetLocation(page, name);

  const row = page.locator('tr').filter({ hasText: name }).first();
  await expect(row).toBeVisible();

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await deleteItem.click();

  const deleteBtnConfirm = page.getByRole('button', { name: /Delete/i }).last();
  await deleteBtnConfirm.click();

  await page.waitForTimeout(1000);

  await expect(row).not.toBeVisible({ timeout: 10000 });
});
