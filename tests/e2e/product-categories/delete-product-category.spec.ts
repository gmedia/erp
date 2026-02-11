import { test, expect } from '@playwright/test';
import { createProductCategory, searchProductCategory, login } from '../helpers';

test.describe('Product Category Management - Delete', () => {
  test('should delete a product category successfully', async ({ page }) => {
    const name = await createProductCategory(page);
    await searchProductCategory(page, name);

    const row = page.locator(`tr:has-text("${name}")`);
    await expect(row).toBeVisible();

    // Open Actions menu
    const actionsButton = row.getByRole('button', { name: /Actions/i });
    await actionsButton.click();

    // Click Delete
    const deleteButton = page.getByRole('menuitem', { name: /Delete/i });
    await deleteButton.click();

    // Confirm Delete in Dialog
    const confirmButton = page.getByRole('button', { name: /Delete|Confirm/i });
    await expect(confirmButton).toBeVisible();
    await confirmButton.click();

    // Verify it's gone
    await expect(row).not.toBeVisible();
  });
});
