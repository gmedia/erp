import { test, expect } from '@playwright/test';
import { createSupplier, searchSupplier } from '../helpers';

test('edit supplier', async ({ page }) => {
  // Create a supplier first
  const email = await createSupplier(page, { name: 'Original Name' });

  // Search for the supplier
  await searchSupplier(page, email);

  // Open actions menu and click edit
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();
  
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await editItem.click();

  // Update the name
  await page.fill('input[name="name"]', 'Updated Name');

  // Submit
  const dialog = page.getByRole('dialog');
  const updateBtn = dialog.getByRole('button', { name: /Update/ });
  await updateBtn.evaluate((el: HTMLElement) => el.click());

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible();

  // Verify the updated name appears
  await expect(page.getByText('Updated Name')).toBeVisible();
});
