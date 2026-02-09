import { test, expect } from '@playwright/test';
import { login, createEmployee, searchEmployee } from '../helpers';

test('search employee by name and email', async ({ page }) => {
  await login(page);
  
  // Create a unique employee
  const timestamp = Date.now();
  const name = `SearchTest ${timestamp}`;
  const email = `search_${timestamp}@example.com`;
  
  await createEmployee(page, { name, email });
  
  // Search by name
  await searchEmployee(page, name);
  await expect(page.locator('tr', { hasText: name })).toBeVisible();
  
  // Search by email
  await searchEmployee(page, email);
  await expect(page.locator('tr', { hasText: email })).toBeVisible();
  
  // Search non-existent
  const searchInput = page.getByPlaceholder(/Search employees...|Search.../i);
  await searchInput.clear();
  await searchInput.fill('NonExistentEmployeeXYZ');
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');
  await expect(page.getByText(/No results/i)).toBeVisible();
});
