import { test, expect } from '@playwright/test';
import { createAssetCategory, searchAssetCategory } from '../helpers';

test('view asset category detail', async ({ page }) => {
  const code = await createAssetCategory(page, {
    name: 'View Test Category',
    useful_life_months_default: '36',
  });

  await searchAssetCategory(page, code);

  const row = page.locator('tr', { hasText: code }).first();
  await row.getByRole('button', { name: /Actions/i }).click();
  await page.getByRole('menuitem', { name: /View/i }).click();

  const modal = page.getByRole('dialog');
  await expect(modal).toBeVisible();
  await expect(modal).toContainText('Asset Category Details');
  await expect(modal).toContainText(code);
  await expect(modal).toContainText('View Test Category');
  await expect(modal).toContainText('36');

  await modal.getByRole('button', { name: /Close/i }).click();
  await expect(modal).not.toBeVisible();
});
