import { test, expect } from '@playwright/test';
import { createCustomerCategory, login } from '../helpers';

test.describe('Customer Category Management - Filter', () => {
  test('should filter customer categories', async ({ page }) => {
    const name = await createCustomerCategory(page);
    
    await login(page);
    await page.goto('/customer-categories');

    const searchInput = page.getByPlaceholder(/Search customer categories.../i);
    await expect(searchInput).toBeVisible();

    await searchInput.fill(name);
    await searchInput.press('Enter');

    await expect(page.locator(`tr:has-text("${name}")`)).toBeVisible();
  });
});
