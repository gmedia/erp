import { test, expect } from '@playwright/test';
import { login, createPosition } from '../helpers';

test('filter positions end‑to‑end', async ({ page }) => {
  // Log in
  await login(page);

  // Create positions
  await createPosition(page);
  await createPosition(page);
  const pos3 = await createPosition(page);

  // Open filter dialog
  const filtersBtn = page.getByRole('button', { name: /Filters/i });
  await expect(filtersBtn).toBeVisible();
  await filtersBtn.click();

  // Test search filter
  const partial = pos3;
  await page.fill('input[placeholder="Search positions..."]', partial);
  await page.press('input[placeholder="Search positions..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  // Verify search results
  await expect(page.locator(`text=${pos3}`)).toBeVisible();
});
