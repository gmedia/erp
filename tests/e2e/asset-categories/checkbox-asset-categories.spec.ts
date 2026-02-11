import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('checkbox visibility in DataTable', async ({ page }) => {
  await login(page);
  await page.goto('/asset-categories');

  // Checkbox in row body should exist
  const rowCheckbox = page.locator('tr').nth(1).locator('button[role="checkbox"]');
  await expect(rowCheckbox).toBeVisible();

  // Checkbox in row head should NOT exist
  const headCheckbox = page.locator('thead').locator('button[role="checkbox"]');
  await expect(headCheckbox).not.toBeVisible();
});
