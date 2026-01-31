import { test, expect } from '@playwright/test';
import { createFiscalYear, searchFiscalYear } from '../helpers';

test('delete fiscal year end‑to‑end', async ({ page }) => {
  const name = await createFiscalYear(page);
  
  await searchFiscalYear(page, name);

  const row = page.locator('tr', { hasText: name }).first();
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const deleteBtn = page.getByRole('menuitem', { name: /Delete/i });
  await deleteBtn.click();

  const confirmBtn = page.getByRole('alertdialog').getByRole('button', { name: /Delete/i });
  await confirmBtn.click();

  await expect(page.locator('tr', { hasText: name })).not.toBeVisible();
});
