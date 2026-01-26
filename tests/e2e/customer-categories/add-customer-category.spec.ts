import { test, expect } from '@playwright/test';
import { createCustomerCategory, searchCustomerCategory } from '../helpers';

test('add new customer category end‑to‑end', async ({ page }) => {
  // Create a customer category using the shared helper (includes login & navigation)
  const name = await createCustomerCategory(page);

  // Search for the newly created customer category
  await searchCustomerCategory(page, name);

  // Verify the customer category appears in the table
  const row = page.locator(`text=${name}`);
  await expect(row).toBeVisible();
});
