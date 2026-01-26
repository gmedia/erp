import { test, expect } from '@playwright/test';
import { createCustomerCategory, searchCustomerCategory, editCustomerCategory } from '../helpers';

test('edit customer category end‑to‑end', async ({ page }) => {
  // Create a new customer category (includes login & navigation)
  const name = await createCustomerCategory(page);

  // Optionally search for the customer category before editing (helper also searches internally)
  await searchCustomerCategory(page, name);

  // Edit the customer category using shared helper
  const updatedName = name + ' Updated';
  await editCustomerCategory(page, name, { name: updatedName });

  // Verify the updated name appears in the table
  await expect(page.locator('text=' + updatedName)).toBeVisible();
});
