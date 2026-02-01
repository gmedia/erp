import { test, expect } from '@playwright/test';
import { createAccount, deleteAccount, searchAccount } from '../helpers';

test('delete account end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);

  // Create account first
  const code = await createAccount(page, {
    coa_version: 'COA 2026 Enhanced',
    name: 'Delete Me',
  });

  // Delete the account
  await deleteAccount(page, code);

  // Search and verify it's gone
  await searchAccount(page, code);
  const item = page.locator('div', { hasText: code }).first();
  await expect(item).toBeHidden({ timeout: 15000 });
});
