import { test, expect } from '@playwright/test';
import { createProductCategory, login } from '../helpers';

test.describe('Product Category Management - Checkboxes', () => {
  test('should show checkboxes on rows but NOT on header', async ({ page }) => {
    await createProductCategory(page);
    await page.goto('/product-categories');

    // Wait for data
    await page.waitForSelector('tbody tr');

    const rowCheckbox = page.locator('tbody tr [role="checkbox"]').first();
    await expect(rowCheckbox).toBeVisible();

    // Header checkbox should NOT be visible
    const headerCheckbox = page.locator('thead tr th [role="checkbox"]');
    await expect(headerCheckbox).not.toBeVisible();
  });
});
