import { test, expect } from '@playwright/test';
import { createAccountMapping, findAccountMappingRow, searchAccountMappings } from './helpers';

test('delete account mapping end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);

  const { sourceCode, targetCode, notes } = await createAccountMapping(page);

  await searchAccountMappings(page, notes);
  const row = findAccountMappingRow(page, sourceCode, targetCode);
  await expect(row).toBeVisible({ timeout: 15000 });

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await expect(deleteItem).toBeVisible();
  await deleteItem.click();

  const confirmBtn = page.getByRole('button', { name: /Delete/i }).last();
  await confirmBtn.click();

  await searchAccountMappings(page, notes);
  await expect(findAccountMappingRow(page, sourceCode, targetCode)).not.toBeVisible();
});
