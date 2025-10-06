import { test, expect } from '@playwright/test';
import { createPosition, searchPosition } from './helpers';

test('delete position end‑to‑end', async ({ page }) => {
  // 1. Create a new position (includes login and navigation)
  const name = await createPosition(page);

  // 2. Ensure the position appears in the list
  await searchPosition(page, name);
  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();

  // 3. Open the Actions menu for that row
  const actionsButton = row.getByRole('button', { name: /Actions/i });
  await actionsButton.click();

  // 4. Click the Delete menu item
  const deleteMenuItem = page.getByRole('menuitemcheckbox', { name: /Delete/i });
  await deleteMenuItem.click();

  // 5. Confirm deletion in the dialog
  const confirmButton = page.getByRole('button', { name: /Delete|Confirm/i });
  await confirmButton.click();

  // 6. Verify the position is no longer present
  await expect(page.locator(`text=${name}`)).toBeHidden();
});
