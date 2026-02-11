import { test, expect } from '@playwright/test';
import { createProductCategory, searchProductCategory, login } from '../helpers';

test.describe('Product Category Management - Search', () => {
  test('should search for a product category by name', async ({ page }) => {
    const name1 = await createProductCategory(page);
    const name2 = await createProductCategory(page);

    await searchProductCategory(page, name1);
    await expect(page.locator(`tr:has-text("${name1}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name2}")`)).not.toBeVisible();

    await searchProductCategory(page, name2);
    await expect(page.locator(`tr:has-text("${name2}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name1}")`)).not.toBeVisible();
  });
});
