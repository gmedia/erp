import { test, expect } from '@playwright/test';
import { createCustomerCategory, searchCustomerCategory, login } from '../helpers';

test.describe('Customer Category Management - Search', () => {
  test('should search for a customer category by name', async ({ page }) => {
    const name1 = await createCustomerCategory(page);
    const name2 = await createCustomerCategory(page);

    await searchCustomerCategory(page, name1);
    await expect(page.locator(`tr:has-text("${name1}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name2}")`)).not.toBeVisible();

    await searchCustomerCategory(page, name2);
    await expect(page.locator(`tr:has-text("${name2}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${name1}")`)).not.toBeVisible();
  });
});
