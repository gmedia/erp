import { Page, expect } from '@playwright/test';
import { login, createEntity, EntityConfig } from '../helpers';

/**
 * Create a new customer via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique email used for the created customer.
 */
export async function createCustomer(
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
  const defaultEmail = `c${timestamp}@test.com`;

  const config: EntityConfig = {
    route: '/customers',
    returnField: 'email',
    fields: [
      { name: 'name', type: 'text', defaultValue: `Test Customer ${timestamp}` },
      { name: 'email', type: 'email', defaultValue: defaultEmail },
      { name: 'phone', type: 'text', defaultValue: '08123456789' },
      { name: 'address', type: 'textarea', defaultValue: '123 Test Street' },
      { name: 'branch_id', type: 'select', selector: 'Branch', defaultValue: 'Head Office' },
      { name: 'category_id', type: 'select', selector: 'Category', defaultValue: 'Retail' },
      { name: 'status', type: 'select', selector: 'Status', defaultValue: 'Active' },
      { name: 'notes', type: 'textarea', defaultValue: 'Test notes' },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a customer by email.
 *
 * @param page - Playwright Page object.
 * @param email - Email address to search for.
 */
export async function searchCustomer(page: Page, email: string): Promise<void> {
  // Wait a bit for the UI to stabilize after any previous actions (like dialog close)
  await page.waitForTimeout(500);
  const searchInput = page.getByPlaceholder('Search customers...');
  await expect(searchInput).toBeVisible();
  await searchInput.click();
  await searchInput.clear();
  await searchInput.type(email, { delay: 50 });
  await page.keyboard.press('Enter');
  
  // Wait for the API response to ensure the table has updated
  await page.waitForResponse(r => r.url().includes('/api/customers') && r.status() === 200).catch(() => null);
  
  // Wait a small amount for the table context to re-render
  await page.waitForTimeout(500);
}

/**
 * Edit an existing customer via the UI.
 *
 * @param page - Playwright Page object.
 * @param email - Current customer email to locate.
 * @param updates - Fields to update.
 */
export async function editCustomer(
  page: Page,
  email: string,
  updates: { name?: string; status?: string }
): Promise<void> {
  // Locate the customer first
  await searchCustomer(page, email);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Edit menu item
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click({ force: true });
  
  // Wait for dialog
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // Update fields if provided
  if (updates.name) {
    await page.fill('input[name="name"]', updates.name);
  }
  if (updates.status) {
    // Select status (SelectField) - use more robust selector
    const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Active|Inactive/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: updates.status, exact: true }).click();
  }

  // Submit the edit dialog
  const updateBtn = dialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.evaluate((el: HTMLElement) => el.click());
  
  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
