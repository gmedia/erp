import { test, expect } from '@playwright/test';
import { createCustomerCategory, editCustomerCategory, searchCustomerCategory, login } from '../helpers';

test.describe('Customer Category Management - Edit', () => {
  test('should edit an existing customer category successfully', async ({ page }) => {
    const oldName = await createCustomerCategory(page);
    const newName = `${oldName} Updated`;

    await editCustomerCategory(page, oldName, { name: newName });

    await searchCustomerCategory(page, newName);
    await expect(page.locator(`tr:has-text("${newName}")`)).toBeVisible();
  });
});
