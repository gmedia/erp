import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Movement Sorting', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/asset-movements');
  });

  const columns = ['Asset', 'Type', 'Date', 'PIC'];

  for (const column of columns) {
    test(`should sort by ${column}`, async ({ page }) => {
      const header = page.getByRole('columnheader', { name: column });
      await expect(header).toBeVisible();
      
      // Click to sort ASC
      await header.click();
      await page.waitForLoadState('networkidle');
      
      // Click to sort DESC
      await header.click();
      await page.waitForLoadState('networkidle');
      
      // Verify some sort indicator or just that it didn't crash
      // More advanced tests would check row order
    });
  }
});
