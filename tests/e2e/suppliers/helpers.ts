import { Page, expect } from '@playwright/test';
import { createEntity, EntityConfig } from '../helpers';

/**
 * Create a new supplier via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique email used for the created supplier.
 */
export async function createSupplier(
  page: Page,
  overrides: Partial<{
    name: string;
    email: string;
    phone: string;
    address: string;
    branch_id: string;
    category_id: string;
    status: string;
    notes: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now().toString().slice(-6);
  const defaultEmail = `s${timestamp}@test.com`;

  const config: EntityConfig = {
    route: '/suppliers',
    returnField: 'email',
    fields: [
      { name: 'name', type: 'text', defaultValue: `Test Supplier ${timestamp}` },
      { name: 'email', type: 'email', defaultValue: defaultEmail },
      { name: 'phone', type: 'text', defaultValue: '08123456789' },
      { name: 'address', type: 'textarea', defaultValue: '456 Supplier Road' },
      { name: 'branch_id', type: 'select', selector: 'Branch', defaultValue: 'Head Office' },
      { name: 'category_id', type: 'select', selector: 'Category', defaultValue: 'Office Supplies' },
      { name: 'status', type: 'select', selector: 'Status', defaultValue: 'Active' },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a supplier by email.
 *
 * @param page - Playwright Page object.
 * @param email - Email address to search for.
 */
export async function searchSupplier(page: Page, email: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search suppliers...');
  await expect(searchInput).toBeVisible();
  await searchInput.clear();
  await searchInput.type(email);
  await page.keyboard.press('Enter');
  
  // Wait for the API response to ensure the table has updated
  await page.waitForResponse(r => r.url().includes('/api/suppliers') && r.status() === 200).catch(() => null);
  
  // Wait a small amount for the table context to re-render
  // This is the "passive" pattern to avoid intermittent timeouts
  await page.waitForTimeout(500);
}

/**
 * Edit an existing supplier via the UI.
 *
 * @param page - Playwright Page object.
 * @param email - Current supplier email to locate.
 * @param updates - Fields to update.
 */
export async function editSupplier(
  page: Page,
  email: string,
  updates: { name?: string; status?: string }
): Promise<void> {
  // Locate the supplier first
  await searchSupplier(page, email);

  // Locate the row and open the Actions menu
  const row = page.locator('tr').filter({ hasText: email }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click();

  // Click the Edit menu item
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click();
  
  // Wait for dialog
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // Update fields if provided
  if (updates.name) {
    const nameInput = dialog.locator('input[name="name"]');
    await nameInput.clear();
    await nameInput.fill(updates.name);
  }
  if (updates.status) {
    // Select status (SelectField) - use accessible name from previous fix
    const statusTrigger = dialog.getByLabel('Status');
    await statusTrigger.click();
    await page.getByRole('option', { name: updates.status, exact: true }).click();
  }

  // Submit the edit dialog
  const updateBtn = dialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
  
  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
