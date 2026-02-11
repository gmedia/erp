import { test, expect } from '@playwright/test';
import { createUnit, login } from '../helpers';

test.describe('Unit Management - Checkboxes', () => {
  test('should show checkboxes on rows but NOT on header', async ({ page }) => {
    await createUnit(page);
    await page.goto('/units');

    // Wait for data
    await page.waitForSelector('tbody tr');

    const rowCheckbox = page.locator('tbody tr [role="checkbox"]').first();
    await expect(rowCheckbox).toBeVisible();

    // Header checkbox should NOT be visible
    const headerCheckbox = page.locator('thead tr th [role="checkbox"]');
    await expect(headerCheckbox).not.toBeVisible();
  });
});
