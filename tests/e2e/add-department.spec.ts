import { test, expect } from '@playwright/test';
import { createDepartment, searchDepartment } from './helpers';

test('add new department end‑to‑end', async ({ page }) => {
  // Create a department using the shared helper (includes login & navigation)
  const name = await createDepartment(page);

  // Search for the newly created department
  await searchDepartment(page, name);

  // Verify the department appears in the table
  const row = page.locator(`text=${name}`);
  await expect(row).toBeVisible();
});
