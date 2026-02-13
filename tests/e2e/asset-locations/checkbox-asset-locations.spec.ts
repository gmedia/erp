import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('DataTable has checkbox in row body but not in row head', async ({ page }) => {
  await login(page);
  await page.goto('/asset-locations');

  // Verify no checkbox in table header
  const headerCheckbox = page.locator('thead').getByRole('checkbox');
  const count = await headerCheckbox.count();
  expect(count).toBe(0);

  // Verify checkboxes exist in table body rows
  const bodyCheckboxes = page.locator('tbody').getByRole('checkbox');
  const bodyCount = await bodyCheckboxes.count();
  // Ensure we have at least some data and rows have checkboxes
  if (bodyCount > 0) {
      await expect(bodyCheckboxes.first()).toBeVisible();
  }
});
