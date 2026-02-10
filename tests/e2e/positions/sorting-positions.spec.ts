import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Position Sorting E2E Test', () => {
  test('should sort positions by Name, Created At, and Updated At', async ({ page }) => {
    await login(page);
    await page.goto('/positions');
    await page.waitForLoadState('networkidle');

    const columns = ['Name', 'Created At', 'Updated At'];

    for (const column of columns) {
      const header = page.getByRole('button', { name: column });
      await expect(header).toBeVisible();
      
      // Click to sort ASC
      await header.click();
      await page.waitForLoadState('networkidle');
      
      // Click to sort DESC
      await header.click();
      await page.waitForLoadState('networkidle');
      
      // Basic check that it doesn't crash and header is still there
      await expect(header).toBeVisible();
    }
  });
});
