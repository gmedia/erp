import { test, expect } from '@playwright/test';
import { createCoaVersion, editCoaVersion, searchCoaVersion } from '../helpers';

test('edit coa version end‑to‑end', async ({ page }) => {
  // 1️⃣ Create a temporary coa version to edit
  const name = await createCoaVersion(page);

  // 2️⃣ Update the coa version
  const updatedName = `${name} Updated`;
  await editCoaVersion(page, name, { 
    name: updatedName,
    status: 'Active'
  });

  // 3️⃣ Search for the updated coa version
  await searchCoaVersion(page, updatedName);

  // 4️⃣ Verify the changes appear in the table
  const row = page.locator('tr', { hasText: updatedName }).first();
  await expect(row).toBeVisible();
  await expect(row).toContainText('active');
});
