import { test, expect } from '@playwright/test';
import { createDepartment, searchDepartment, editDepartment } from './helpers';

test('edit department end‑to‑end', async ({ page }) => {
  // Create a new department (includes login & navigation)
  const name = await createDepartment(page);

  // Optionally search for the department before editing (helper also searches internally)
  await searchDepartment(page, name);

  // Edit the department using shared helper
  const updatedName = name + ' Updated';
  await editDepartment(page, name, { name: updatedName });

  // Verify the updated name appears in the table
  await expect(page.locator('text=' + updatedName)).toBeVisible();
});
