import { test, expect } from '@playwright/test';
import { createSupplierCategory, editSupplierCategory, searchSupplierCategory, login } from '../helpers';

test.describe('Supplier Category Management - Edit', () => {
  test('should edit an existing supplier category successfully', async ({ page }) => {
    const oldName = await createSupplierCategory(page);
    const newName = `${oldName} Updated`;

    await editSupplierCategory(page, oldName, { name: newName });

    await searchSupplierCategory(page, newName);
    await expect(page.locator(`tr:has-text("${newName}")`)).toBeVisible();
  });
});
