import { test, expect } from '@playwright/test';
import { createCustomerCategory, login } from '../helpers';

test.describe('Customer Category Management - Add', () => {
  test('should add a new customer category successfully', async ({ page }) => {
    const name = await createCustomerCategory(page);
    
    await login(page);
    await page.goto('/customer-categories');
    
    const searchInput = page.getByPlaceholder(/Search customer categories.../i);
    await searchInput.fill(name);
    await searchInput.press('Enter');

    await expect(page.locator(`tr:has-text("${name}")`)).toBeVisible();
  });
});
