import { test, expect } from '@playwright/test';
import { createAccountMapping, findAccountMappingRow, searchAccountMappings } from './helpers';

test('edit account mapping end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);

  const { sourceCode, targetCode } = await createAccountMapping(page);

  await searchAccountMappings(page, sourceCode);
  const row = findAccountMappingRow(page, sourceCode, targetCode);
  await expect(row).toBeVisible({ timeout: 15000 });

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible({ timeout: 15000 });

  const typeTrigger = dialog.locator('button').filter({ hasText: /rename|merge|split|Select type/i }).last();
  await typeTrigger.click();
  await page.getByRole('option', { name: 'Merge', exact: true }).click();

  const updateBtn = dialog.getByRole('button', { name: /Update|Submit/i });
  await updateBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  await searchAccountMappings(page, sourceCode);
  await expect(findAccountMappingRow(page, sourceCode, targetCode).locator('text=merge')).toBeVisible();
});

