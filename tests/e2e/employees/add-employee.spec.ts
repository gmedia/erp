import { test, expect } from '@playwright/test';
import { createEmployee, searchEmployee } from '../helpers';

test('add new employee end‑to‑end', async ({ page }) => {
  // Create employee using shared helper (includes login & navigation)
  const email = await createEmployee(page);

  // Search for the newly created employee
  await searchEmployee(page, email);

  // Verify the employee appears in the table
  const row = page.locator(`text=${email}`);
  await expect(row).toBeVisible();
});