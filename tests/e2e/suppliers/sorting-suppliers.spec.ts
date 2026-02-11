import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('sort suppliers by various columns', async ({ page }) => {
  await login(page);
  await page.goto('/suppliers');

  const columns = ['Name', 'Email', 'Phone', 'Branch', 'Category', 'Status'];

  for (const column of columns) {
    // Locate the sorting button in the header more precisely
    const headerCell = page.locator('thead th', { hasText: new RegExp(`^${column}$`, 'i') });
    const sortButton = headerCell.getByRole('button');
    
    await expect(sortButton).toBeVisible();

    // Click and wait for response
    await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/api/suppliers') && resp.status() === 200, { timeout: 10000 }).catch(() => null),
      sortButton.click(),
    ]);
    
    await page.waitForTimeout(500);

    // Click again for DESC
    await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/api/suppliers') && resp.status() === 200, { timeout: 10000 }).catch(() => null),
      sortButton.click(),
    ]);

    await page.waitForTimeout(500);
  }
});
