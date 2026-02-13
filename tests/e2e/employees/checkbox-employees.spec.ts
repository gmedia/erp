import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('checkbox visibility in DataTable', async ({ page }) => {
  await login(page);
  await page.goto('/employees');
  
  // Verify no checkbox in header
  const headerCheckbox = page.locator('thead').getByRole('checkbox');
  await expect(headerCheckbox).toHaveCount(0);
  
  // Verify checkbox in body rows
  // Wait for loading to finish (skeletons to disappear)
  await expect(page.locator('[data-slot="skeleton"]')).toHaveCount(0);

  // Wait for data to load if any
  const dataRows = page.locator('tbody tr').filter({ hasNotText: 'No results.' });
  const rowCount = await dataRows.count();
  const bodyCheckboxes = page.locator('tbody').getByRole('checkbox');
  
  if (rowCount > 0) {
    // There should be one checkbox per row if the first column is select
    await expect(bodyCheckboxes).toHaveCount(rowCount);
  }
});
