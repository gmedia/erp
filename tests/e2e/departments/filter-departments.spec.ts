import { test, expect } from '@playwright/test';
import { login, createDepartment } from '../helpers';

test('filter departments end‑to‑end', async ({ page }) => {
  // Log in
  await login(page);

  // Create departments with distinct department/position combos
  await createDepartment(page);
  await createDepartment(page);
  const pos3 = await createDepartment(page);

  // Open filter dialog
  const filtersBtn = page.getByRole('button', { name: /Filters/i });
  await expect(filtersBtn).toBeVisible();
  await filtersBtn.click();

  // Test search filter
  const partial = pos3;
  await page.fill('input[placeholder="Search departments..."]', partial);
  await page.press('input[placeholder="Search departments..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  // Verify search results
  await expect(page.locator(`text=${pos3}`)).toBeVisible();
});
