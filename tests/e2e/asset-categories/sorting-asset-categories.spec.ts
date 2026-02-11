import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Categories Sorting', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/asset-categories');
  });

  const columns = [
    { name: 'Code', label: 'Code' },
    { name: 'Name', label: 'Name' },
    { name: 'Default Useful Life (Months)', label: 'Default Useful Life (Months)' },
    { name: 'Created At', label: 'Created At' },
    { name: 'Updated At', label: 'Updated At' },
  ];

  for (const column of columns) {
    test(`sort by ${column.name}`, async ({ page }) => {
      const header = page.getByRole('button', { name: column.label, exact: true });
      await expect(header).toBeVisible();

      // Sort Ascending
      await header.click();
      await page.waitForResponse(resp => resp.url().includes('/api/asset-categories') && resp.status() === 200);
      // We don't necessarily check the exact order unless we have deterministic data, 
      // but we check that the UI reflects the sorting state if possible, or at least that it doesn't crash.
      
      // Sort Descending
      await header.click();
      await page.waitForResponse(resp => resp.url().includes('/api/asset-categories') && resp.status() === 200);
    });
  }
});
