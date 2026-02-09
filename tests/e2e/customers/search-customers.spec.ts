import { test, expect } from '@playwright/test';
import { createCustomer, login } from '../helpers';

test('search customers by name, email, and phone', async ({ page }) => {
  // Create a customer with specific details
  const timestamp = Date.now();
  const searchName = `SearchTarget ${timestamp}`;
  const searchEmail = `search${timestamp}@example.com`;
  const searchPhone = `0855${timestamp.toString().slice(-8)}`;

  await createCustomer(page, {
    name: searchName,
    email: searchEmail,
    phone: searchPhone,
  });

  // Navigate to customers list
  await page.goto('/customers');

  const searchInput = page.getByPlaceholder('Search customers...');

  // Search by name
  await searchInput.clear();
  await searchInput.fill(searchName);
  await page.keyboard.press('Enter');
  await expect(page.locator('tr').filter({ hasText: searchName }).first()).toBeVisible();

  // Search by email
  await searchInput.clear();
  await searchInput.fill(searchEmail);
  await page.keyboard.press('Enter');
  await expect(page.locator('tr').filter({ hasText: searchEmail }).first()).toBeVisible();

  // Search by phone
  await searchInput.clear();
  await searchInput.fill(searchPhone);
  await page.keyboard.press('Enter');
  await expect(page.locator('tr').filter({ hasText: searchPhone }).first()).toBeVisible();
});
