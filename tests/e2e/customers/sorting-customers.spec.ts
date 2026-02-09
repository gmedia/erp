import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('sorting customers by various columns', async ({ page }) => {
  await login(page);
  await page.goto('/customers');

  const columns = ['Name', 'Email', 'Phone', 'Branch', 'Category', 'Status'];

  for (const column of columns) {
    const header = page.getByRole('button', { name: column, exact: true });
    await expect(header).toBeVisible();

    // Click to sort ASC
    await header.click();
    await page.waitForResponse(resp => resp.url().includes('/api/customers') && resp.url().includes('sort_direction=asc'));
    
    // Click to sort DESC
    await header.click();
    await page.waitForResponse(resp => resp.url().includes('/api/customers') && resp.url().includes('sort_direction=desc'));
  }
});
