import { test, expect } from '@playwright/test';
import { createDepartment, searchDepartment } from './helpers';

test('add multiple departments end‑to‑end', async ({ page }) => {
  // Create three departments, collecting their names
  const names: string[] = [];

  for (let i = 0; i < 3; i++) {
    const name = await createDepartment(page);
    names.push(name);
  }

  // Verify each department appears in the list
  for (const name of names) {
    // Optionally ensure the department can be found via the search helper
    await searchDepartment(page, name);
    await expect(page.locator(`text=${name}`)).toBeVisible();
  }
});