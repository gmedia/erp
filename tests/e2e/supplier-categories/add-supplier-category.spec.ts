import { test, expect } from '@playwright/test';
import { createSupplierCategory, searchSupplierCategory } from '../helpers';

test('add new supplier category end‑to‑end', async ({ page }) => {
  // Create a supplier category using the shared helper (includes login & navigation)
  const name = await createSupplierCategory(page);

  // Search for the newly created supplier category
  await searchSupplierCategory(page, name);

  // Verify the supplier category appears in the table
  const row = page.locator(`text=${name}`);
  await expect(row).toBeVisible();
});
