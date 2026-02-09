import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('checkbox visibility in DataTable', async ({ page }) => {
  await login(page);
  await page.goto('/employees');
  
  // Verify no checkbox in header
  const headerCheckbox = page.locator('thead').getByRole('checkbox');
  await expect(headerCheckbox).toHaveCount(0);
  
  // Verify checkbox in body rows
  // Wait for data to load
  await page.waitForSelector('tbody tr');
  const bodyCheckboxes = page.locator('tbody').getByRole('checkbox');
  const rowCount = await page.locator('tbody tr').count();
  
  if (rowCount > 0) {
    // There should be one checkbox per row if the first column is select
    await expect(bodyCheckboxes).toHaveCount(rowCount);
  }
});
