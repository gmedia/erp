import { test, expect } from '@playwright/test';
import { createFiscalYear, editFiscalYear, searchFiscalYear } from '../helpers';

test('edit existing fiscal year end‑to‑end', async ({ page }) => {
  const name = await createFiscalYear(page);
  const newName = `${name} Updated`;

  await editFiscalYear(page, name, { name: newName, status: 'Closed' });

  await searchFiscalYear(page, newName);

  const row = page.locator('tr', { hasText: newName }).first();
  await expect(row).toBeVisible();
  await expect(row).toContainText('closed');
});
