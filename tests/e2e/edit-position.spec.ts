import { test, expect } from '@playwright/test';
import { createPosition, searchPosition, editPosition } from './helpers';

test('edit position end‑to‑end', async ({ page }) => {
  // Create a new position using shared helper (includes login & navigation)
  const name = await createPosition(page);

  // Optionally search for the position before editing (helper also searches internally)
  await searchPosition(page, name);

  // Edit the position using shared helper
  const updatedName = name + ' Updated';
  await editPosition(page, name, { name: updatedName });

  // Verify the updated name appears in the table
  await expect(page.locator('text=' + updatedName)).toBeVisible();
});
