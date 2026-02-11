import { test, expect } from '@playwright/test';
import { createCustomerCategory, searchCustomerCategory, login } from '../helpers';

test.describe('Customer Category Management - View', () => {
  test('should view customer category details', async ({ page }) => {
    const name = await createCustomerCategory(page);
    await searchCustomerCategory(page, name);

    const row = page.locator(`tr:has-text("${name}")`);
    await expect(row).toBeVisible();

    // Open Actions menu
    const actionsButton = row.getByRole('button', { name: /Actions/i });
    await actionsButton.click();

    // Click View
    const viewButton = page.getByRole('menuitem', { name: /View/i });
    await viewButton.click();

    // Verify Dialog
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText(name);
  });
});
