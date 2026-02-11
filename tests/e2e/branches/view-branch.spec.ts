import { test, expect } from '@playwright/test';
import { createBranch, searchBranch, login } from '../helpers';

test.describe('Branch Management - View', () => {
  test('should view branch details successfully', async ({ page }) => {
    const branchName = await createBranch(page);
    await searchBranch(page, branchName);

    const row = page.locator(`tr:has-text("${branchName}")`);
    await expect(row).toBeVisible();

    // Open Actions menu
    const actionsButton = row.getByRole('button', { name: /Actions/i });
    await actionsButton.click();

    // Click View
    const viewButton = page.getByRole('menuitem', { name: /View/i });
    await viewButton.click();

    // Check if the Dialog is visible
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    
    // Check if the branch name is visible in the dialog
    // The specific selector depends on how ViewModal is implemented.
    // Based on SimpleEntityViewModal, it likely shows details in a grid or standard text.
    // We expect the name to be present.
    await expect(dialog.getByText(branchName)).toBeVisible();
  });
});
