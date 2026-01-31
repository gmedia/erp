import { test, expect } from '@playwright/test';
import { createFiscalYear, searchFiscalYear } from '../helpers';

test('add new fiscal year end‑to‑end', async ({ page }) => {
  // Create fiscal year using shared helper
  const name = await createFiscalYear(page);

  // Search for the newly created fiscal year
  await searchFiscalYear(page, name);

  // Verify it appears in the table
  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();
});
