
import { test, expect } from '@playwright/test';
import { createProduct, login } from '../helpers';

test.describe('Product Management - Checkboxes', () => {
  test('should show checkboxes on rows but NOT on header', async ({ page }) => {
    await createProduct(page, { name: 'Checkbox Test Product', category_id: 'Electronics', unit_id: 'Piece', cost: '10', selling_price: '20' });
    await page.goto('/products');

    // Wait for data
    await page.waitForSelector('tbody tr');

    const rowCheckbox = page.locator('tbody tr [role="checkbox"]').first();
    await expect(rowCheckbox).toBeVisible();

    // Header checkbox should NOT be visible
    const headerCheckbox = page.locator('thead tr th [role="checkbox"]');
    await expect(headerCheckbox).not.toBeVisible();
  });
});
