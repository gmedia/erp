import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Movement DataTable Checkboxes', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/asset-movements');
  });

  test('should have checkboxes in row head and row body', async ({ page }) => {
    // Check for checkbox in table head
    const headerCheckbox = page.locator('thead tr th').locator('input[type="checkbox"], button[role="checkbox"]');
    await expect(headerCheckbox.first()).toBeVisible();

    // Check for checkbox in table body
    const bodyRows = page.locator('tbody tr');
    if (await bodyRows.count() > 1 && await bodyRows.first().textContent() !== 'No results.') {
      const firstRowCheckbox = bodyRows.first().locator('input[type="checkbox"], button[role="checkbox"]');
      await expect(firstRowCheckbox).toBeVisible();
    }
  });
});
