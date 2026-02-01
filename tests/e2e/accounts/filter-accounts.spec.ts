import { test, expect } from '@playwright/test';
import { login, createAccount } from '../helpers';

test('filter accounts by search end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);
  await login(page);

  // Create accounts with distinct properties
  const acc1Code = await createAccount(page, {
    coa_version: 'COA 2026 Enhanced',
    name: 'Cash Engineering',
  });
  const acc2Code = await createAccount(page, {
    coa_version: 'COA 2026 Enhanced',
    name: 'Debt Marketing',
  });

  await page.goto('/accounts');
  
  // Test search filter
  const searchInput = page.getByPlaceholder(/Search code or name/i);
  await searchInput.fill('Debt');
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');

  // Verify only matching account is visible
  await expect(page.locator(`text=${acc2Code}`)).toBeVisible();
  await expect(page.locator(`text=${acc1Code}`)).toBeHidden();

  // Test search filter with code
  await searchInput.fill(acc1Code);
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');

  await expect(page.locator(`text=${acc1Code}`)).toBeVisible();
  await expect(page.locator(`text=${acc2Code}`)).toBeHidden();
});
