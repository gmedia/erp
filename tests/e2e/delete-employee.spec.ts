import { test, expect } from '@playwright/test';
import { createEmployee, searchEmployee } from './helpers';

test('delete employee end‑to‑end', async ({ page }) => {
  // 1. Create a new employee (includes login and navigation)
  const email = await createEmployee(page);

  // 2. Ensure the employee appears in the list
  await searchEmployee(page, email);
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();

  // 3. Open the Actions menu for that row
  const actionsButton = row.getByRole('button', { name: /Actions/i });
  await actionsButton.click();

  // 4. Click the Delete menu item
  const deleteMenuItem = page.getByRole('menuitem', { name: /Delete/i });
  await deleteMenuItem.click();

  // 5. Confirm deletion in the dialog
  const confirmButton = page.getByRole('button', { name: /Delete|Confirm/i });
  await confirmButton.click();

  // 6. Verify the employee is no longer present
  await expect(page.locator(`text=${email}`)).toBeHidden();
});
