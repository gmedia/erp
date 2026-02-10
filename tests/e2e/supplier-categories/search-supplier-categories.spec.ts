import { test, expect } from '@playwright/test';
import { createSupplierCategory, searchSupplierCategory, login } from '../helpers';

test.describe('Supplier Category Management - Search', () => {
  test('should search for a supplier category by name', async ({ page }) => {
    const name1 = await createSupplierCategory(page);
    const name2 = await createSupplierCategory(page);

    await searchSupplierCategory(page, name1);
    await expect(page.locator(`tr:has-text("${name1}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name2}")`)).not.toBeVisible();

    await searchSupplierCategory(page, name2);
    await expect(page.locator(`tr:has-text("${name2}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name1}")`)).not.toBeVisible();
  });
});
