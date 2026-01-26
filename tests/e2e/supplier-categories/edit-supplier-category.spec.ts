import { test, expect } from '@playwright/test';
import { createSupplierCategory, searchSupplierCategory, editSupplierCategory } from '../helpers';

test('edit supplier category end‑to‑end', async ({ page }) => {
  // Create a new supplier category (includes login & navigation)
  const name = await createSupplierCategory(page);

  // Optionally search for the supplier category before editing (helper also searches internally)
  await searchSupplierCategory(page, name);

  // Edit the supplier category using shared helper
  const updatedName = name + ' Updated';
  await editSupplierCategory(page, name, { name: updatedName });

  // Verify the updated name appears in the table
  await expect(page.locator('text=' + updatedName)).toBeVisible();
});
