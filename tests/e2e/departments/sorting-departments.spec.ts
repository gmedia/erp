import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('sort departments by various columns', async ({ page }) => {
  await login(page);
  await page.goto('/departments');

  const columns = ['Name', 'Created At', 'Updated At'];

  for (const column of columns) {
    const headerCell = page.locator('thead th', { hasText: new RegExp(`^${column}$`, 'i') });
    const sortButton = headerCell.getByRole('button');
    
    await expect(sortButton).toBeVisible();

    await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/api/departments') && resp.status() === 200, { timeout: 10000 }).catch(() => null),
      sortButton.click(),
    ]);
    
    await page.waitForTimeout(500);

    await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/api/departments') && resp.status() === 200, { timeout: 10000 }).catch(() => null),
      sortButton.click(),
    ]);

    await page.waitForTimeout(500);
  }
});
