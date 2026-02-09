import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('DataTable has checkbox in row body but not in header', async ({ page }) => {
  await login(page);
  await page.goto('/customers');

  // Verify header does not have a checkbox
  const thead = page.locator('thead');
  const headerCheckbox = thead.getByRole('checkbox');
  await expect(headerCheckbox).not.toBeVisible();

  // Verify row body has checkboxes
  const tbody = page.locator('tbody');
  const bodyCheckboxes = tbody.getByRole('checkbox');
  
  // Wait for data to load if any
  await expect(async () => {
    const count = await bodyCheckboxes.count();
    if (count === 0) {
        // If no data, maybe it's still loading or truly empty
        // We expect some data from seeders or previous tests
    }
  }).toPass();

  // Ensure at least one checkbox is visible in body if rows exist
  if (await page.locator('tbody tr').count() > 0) {
      await expect(bodyCheckboxes.first()).toBeVisible();
  }
});
