import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('DataTable checkbox behavior', async ({ page }) => {
  await login(page);
  await page.goto('/journal-entries');
  
  // Wait for table to load
  await page.waitForSelector('table');
  
  // Header should NOT have checkbox
  const headerCheckbox = page.locator('thead tr th').getByRole('checkbox');
  const count = await headerCheckbox.count();
  expect(count).toBe(0);
  
  // Body should have checkboxes if data exists
  const rows = page.locator('tbody tr');
  if (await rows.count() > 0) {
    const bodyCheckbox = rows.first().locator('td').first().getByRole('checkbox');
    await expect(bodyCheckbox).toBeVisible();
  }
});
