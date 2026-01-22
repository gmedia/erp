import { test, expect } from '@playwright/test';
import { createBranch, searchBranch } from '../helpers';

test('delete branch end‑to‑end', async ({ page }) => {
  // 1. Create a new branch (includes login and navigation)
  const name = await createBranch(page);

  // 2. Ensure the branch appears in the list
  await searchBranch(page, name);
  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();

  // 3. Open the Actions menu for that row
  const actionsButton = row.getByRole('button', { name: /Actions/i });
  await actionsButton.click();

  // 4. Click the Delete menu item
  const deleteMenuItem = page.getByRole('menuitem', { name: /Delete/i });
  await expect(deleteMenuItem).toBeVisible();
  await deleteMenuItem.click();

  // 5. Confirm the deletion in the dialog
  const confirmButton = page.getByRole('button', { name: /Delete/i });
  await expect(confirmButton).toBeVisible();
  await confirmButton.click();

  // 6. Verify the branch no longer appears
  await page.waitForLoadState('networkidle');
  await expect(page.locator(`text=${name}`)).not.toBeVisible();
});
