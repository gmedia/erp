import { test, expect } from '@playwright/test';
import { createSupplierCategory, login } from '../helpers';

test.describe('Supplier Category Management - Add', () => {
  test('should add a new supplier category successfully', async ({ page }) => {
    const name = await createSupplierCategory(page);
    
    await login(page);
    await page.goto('/supplier-categories');
    
    const searchInput = page.getByPlaceholder(/Search supplier categories.../i);
    await searchInput.fill(name);
    await searchInput.press('Enter');

    await expect(page.locator(`tr:has-text("${name}")`)).toBeVisible();
  });
});
