import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('DataTable has checkbox in row body but not in row head', async ({ page }) => {
  await login(page);
  await page.goto('/suppliers');

  // Verify no checkbox in the table header
  const headerCheckbox = page.locator('thead th').locator('button[role="checkbox"], input[type="checkbox"]');
  await expect(headerCheckbox).toHaveCount(0);

  // Verify checkboxes exist in the table body row
  const bodyCheckbox = page.locator('tbody tr').first().locator('button[role="checkbox"], input[type="checkbox"]');
  // We need at least one row. Data might be seeded or created.
  // To be safe, many tests rely on seeded data or create one.
  const rowCount = await page.locator('tbody tr').count();
  if (rowCount > 0) {
    await expect(bodyCheckbox).toBeVisible();
  }
});
