import { test, expect } from '@playwright/test';
import { createDepartment, searchDepartment } from '../helpers';

test('view department details', async ({ page }) => {
  const name = `View Dept ${Date.now()}`;
  await createDepartment(page, { name });

  await searchDepartment(page, name);

  const row = page.locator('tr', { hasText: name }).first();
  await row.getByRole('button', { name: /Actions/i }).click();
  await page.getByRole('menuitem', { name: /View/i }).click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();
  // Be more flexible with the title mapping
  await expect(dialog.getByText(/details/i)).toBeVisible();
  await expect(dialog.getByText(name)).toBeVisible();
});
