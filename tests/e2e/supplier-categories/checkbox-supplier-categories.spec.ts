import { test, expect } from '@playwright/test';
import { createSupplierCategory, login } from '../helpers';

test.describe('Supplier Category Management - Checkboxes', () => {
  test('should show checkboxes on rows but NOT on header', async ({ page }) => {
    await createSupplierCategory(page);
    await page.goto('/supplier-categories');

    // Wait for data
    await page.waitForSelector('tbody tr');

    const rowCheckbox = page.locator('tbody tr [role="checkbox"]').first();
    await expect(rowCheckbox).toBeVisible();

    // Header checkbox should NOT be visible
    const headerCheckbox = page.locator('thead tr th [role="checkbox"]');
    await expect(headerCheckbox).not.toBeVisible();
  });
});
