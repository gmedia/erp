import { test, expect } from '@playwright/test';
import { createEmployee, searchEmployee, editEmployee } from './helpers';

test('edit employee end‑to‑end', async ({ page }) => {
  // Create a new employee using shared helper (includes login & navigation)
  const email = await createEmployee(page);

  // Optionally search for the employee before editing (helper also searches internally)
  await searchEmployee(page, email);

  // Edit the employee using shared helper
  await editEmployee(page, email, { name: 'John Doe Updated', salary: '6000' });

  // Verify the updated name appears in the table
  await expect(page.locator('text=John Doe Updated')).toBeVisible();
});