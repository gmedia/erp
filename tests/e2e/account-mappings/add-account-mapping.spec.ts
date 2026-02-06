import { test, expect } from '@playwright/test';
import { createAccountMapping, findAccountMappingRow, searchAccountMappings } from './helpers';

test('add new account mapping end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);

  const { sourceCode, targetCode } = await createAccountMapping(page);

  await searchAccountMappings(page, sourceCode);
  const row = findAccountMappingRow(page, sourceCode, targetCode);
  await expect(row).toBeVisible({ timeout: 15000 });
});

