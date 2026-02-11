import { test, expect } from '@playwright/test';
import { createProductCategory, login } from '../helpers';

test.describe('Product Category Management - Add', () => {
  test('should add a new product category successfully', async ({ page }) => {
    const name = await createProductCategory(page);
    
    await login(page);
    await page.goto('/product-categories');
    
    const searchInput = page.getByPlaceholder(/Search product categories.../i);
    await searchInput.fill(name);
    await searchInput.press('Enter');

    await expect(page.locator(`tr:has-text("${name}")`)).toBeVisible();
  });
});
