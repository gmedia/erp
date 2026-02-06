import { test, expect } from '@playwright/test';
import { createAccountMapping, findAccountMappingRow, searchAccountMappings } from './helpers';

test('view account mapping end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);

  const { sourceCode, targetCode, notes } = await createAccountMapping(page);

  await searchAccountMappings(page, sourceCode);
  const row = findAccountMappingRow(page, sourceCode, targetCode);
  await expect(row).toBeVisible({ timeout: 15000 });

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const viewItem = page.getByRole('menuitem', { name: /View/i });
  await expect(viewItem).toBeVisible();
  await viewItem.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible({ timeout: 15000 });
  await expect(dialog.getByText('View Account Mapping')).toBeVisible();
  await expect(dialog.getByText(notes)).toBeVisible();

  await dialog.locator('button:has-text("Close")').first().click();
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
});
