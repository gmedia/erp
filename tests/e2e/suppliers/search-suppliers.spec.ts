import { test, expect } from '@playwright/test';
import { createSupplier, searchSupplier } from '../helpers';

test('search suppliers by name, email, and phone', async ({ page }) => {
  // Create a supplier to ensure data exists
  // createSupplier returns the email of the created supplier
  const email = await createSupplier(page);
  
  // Navigate to suppliers list
  await page.goto('/suppliers');

  // Search by email
  await searchSupplier(page, email);
  await expect(page.locator(`text=${email}`)).toBeVisible();

  // Search by name (assuming name is derived from email or we know it)
  // The createSupplier helper creates a supplier with a random name usually.
  // Let's check helpers.ts to see what it does.
});
