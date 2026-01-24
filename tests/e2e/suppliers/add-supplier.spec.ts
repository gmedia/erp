import { test, expect } from '@playwright/test';
import { createSupplier, searchSupplier } from '../helpers';

test('add new supplier end-to-end', async ({ page }) => {
  // Create supplier using shared helper (includes login & navigation)
  const email = await createSupplier(page);

  // Search for the newly created supplier
  await searchSupplier(page, email);

  // Verify the supplier appears in the table
  const row = page.locator(`text=${email}`);
  await expect(row).toBeVisible();
});
