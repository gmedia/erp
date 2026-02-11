import { test, expect } from '@playwright/test';
import { createProductCategory, editProductCategory, searchProductCategory, login } from '../helpers';

test.describe('Product Category Management - Edit', () => {
  test('should edit an existing product category successfully', async ({ page }) => {
    const oldName = await createProductCategory(page);
    const newName = `${oldName} Updated`;

    await editProductCategory(page, oldName, { name: newName });

    await searchProductCategory(page, newName);
    await expect(page.locator(`tr:has-text("${newName}")`)).toBeVisible();
  });
});
