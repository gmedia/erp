import { test, expect } from '@playwright/test';
import { createAccount, searchAccount } from '../helpers';

test('add new account end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);
  
  // Create account using shared helper
  const code = await createAccount(page, {
    coa_version: 'COA 2026 Enhanced',
    type: 'Asset',
    normal_balance: 'Debit',
    is_active: true,
  });

  // Search for the newly created account
  await searchAccount(page, code);

  // Verify the account appears in the tree/list
  const item = page.locator('div', { hasText: code }).first();
  await expect(item).toBeVisible({ timeout: 15000 });
});
