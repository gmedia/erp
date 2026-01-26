import { test, expect } from '@playwright/test';
import { createCustomerCategory, searchCustomerCategory } from '../helpers';

test('delete customer category end-to-end', async ({ page }) => {
  const name = await createCustomerCategory(page);

  await searchCustomerCategory(page, name);

  const row = page.locator(`tr:has-text("${name}")`).first();
  await expect(row).toBeVisible();

  // Open actions menu
  await row.getByRole('button', { name: /actions/i }).click();

  // Click delete
  await page.getByRole('menuitem', { name: /delete/i }).click();

  // Confirm delete dialog
  const dialog = page.getByRole('alertdialog');
  await expect(dialog).toBeVisible();
  await dialog.getByRole('button', { name: /delete/i }).click();

  // Verify row is gone
  await expect(row).not.toBeVisible();
});
