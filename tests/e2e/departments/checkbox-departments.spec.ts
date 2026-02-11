import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('DataTable has checkbox in row body but not in row head', async ({ page }) => {
  await login(page);
  await page.goto('/departments');

  const headerCheckbox = page.locator('thead th').locator('button[role="checkbox"], input[type="checkbox"]');
  await expect(headerCheckbox).toHaveCount(0);

  const rowCount = await page.locator('tbody tr').count();
  if (rowCount > 0) {
    const bodyCheckbox = page.locator('tbody tr').first().locator('button[role="checkbox"], input[type="checkbox"]');
    await expect(bodyCheckbox).toBeVisible();
  }
});
