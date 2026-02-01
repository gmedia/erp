import { test, expect } from '@playwright/test';
import { createAccount, editAccount, searchAccount } from '../helpers';

test('edit existing account end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);

  // Create account first
  const code = await createAccount(page, {
    coa_version: 'COA 2026 Enhanced',
    name: 'Original Account',
  });

  // Edit the account
  const updatedName = `Updated Account ${Date.now()}`;
  await editAccount(page, code, {
    name: updatedName,
    type: 'Liability'
  });

  // Search and verify
  await searchAccount(page, code);
  const item = page.locator('div', { hasText: updatedName }).first();
  await expect(item).toBeVisible({ timeout: 15000 });
});
