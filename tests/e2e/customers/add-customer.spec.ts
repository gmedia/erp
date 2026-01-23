import { test, expect } from '@playwright/test';
import { createCustomer, searchCustomer } from '../helpers';

test('add new customer end-to-end', async ({ page }) => {
  // Create customer using shared helper (includes login & navigation)
  const email = await createCustomer(page);

  // Search for the newly created customer
  await searchCustomer(page, email);

  // Verify the customer appears in the table
  const row = page.locator(`text=${email}`);
  await expect(row).toBeVisible();
});
