import { test, expect } from '@playwright/test';
import { createCoaVersion, searchCoaVersion } from '../helpers';

test('add new coa version end‑to‑end', async ({ page }) => {
  // Create coa version using shared helper (includes login & navigation)
  const name = await createCoaVersion(page);

  // Search for the newly created coa version
  await searchCoaVersion(page, name);

  // Verify the coa version appears in the table
  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();
});
