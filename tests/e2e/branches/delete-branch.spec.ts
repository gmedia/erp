import { test, expect } from '@playwright/test';
import { createBranch, searchBranch, login } from '../helpers';

test.describe('Branch Management - Delete', () => {
  test('should delete a branch successfully', async ({ page }) => {
    const branchName = await createBranch(page);
    await searchBranch(page, branchName);

    const row = page.locator(`tr:has-text("${branchName}")`);
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
