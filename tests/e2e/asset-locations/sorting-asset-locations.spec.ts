import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Location Sorting', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/asset-locations');
  });

  const columns = ['Code', 'Name', 'Branch'];

  for (const column of columns) {
    test(`sort by ${column}`, async ({ page }) => {
      const header = page.getByRole('button', { name: column });
      await expect(header).toBeVisible();

      // Click to sort ascending
      await header.click();
      await page.waitForLoadState('networkidle');
      
      // Click to sort descending
      await header.click();
      await page.waitForLoadState('networkidle');

      // Verify table still has content
      const rows = page.locator('tbody tr');
      const count = await rows.count();
      if (count > 0) {
          await expect(rows.first()).toBeVisible();
      }
    });
  }

  test('sort by Parent Location', async ({ page }) => {
      // Parent Location header might not be a button if sorting is disabled for it
      // But according to IndexAssetLocationRequest, parent_id is allowed
      const header = page.getByText('Parent Location');
      await expect(header).toBeVisible();
      
      // If it's a button, it's sortable
      const button = page.getByRole('button', { name: 'Parent Location' });
      if (await button.isVisible()) {
          await button.click();
          await page.waitForLoadState('networkidle');
      }
  });
});
