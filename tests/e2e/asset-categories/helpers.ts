import { Page, expect } from '@playwright/test';
import { createEntity, EntityConfig } from '../helpers';

/**
 * Create a new asset category via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override default values.
 * @returns The unique code used for the created asset category.
 */
export async function createAssetCategory(
  page: Page,
  overrides: Partial<{
    code: string;
    name: string;
    useful_life_months_default: string;
  }> = {}
): Promise<{ code: string; name: string }> {
  const timestamp = Date.now();
  const defaultCode = `AC${timestamp.toString().slice(-5)}`;
  const defaultName = `Category ${timestamp}`;

  const config: EntityConfig = {
    route: '/asset-categories',
    returnField: 'code',
    fields: [
      { name: 'code', type: 'text', defaultValue: defaultCode },
      { name: 'name', type: 'text', defaultValue: defaultName },
      { name: 'useful_life_months_default', type: 'text', defaultValue: '48' },
    ],
  };

  const code = await createEntity(page, config, (overrides as Record<string, string>));
  return { code, name: overrides.name || defaultName };
}

/**
 * Search for an asset category by code or name.
 *
 * @param page - Playwright Page object.
 * @param query - Query string to search for.
 */
export async function searchAssetCategory(page: Page, query: string): Promise<void> {
  await page.fill('input[placeholder="Search asset categories..."]', query);
  await page.press('input[placeholder="Search asset categories..."]', 'Enter');
  
  // Wait for the row containing the query to appear
  const row = page.locator('tr').filter({ hasText: query }).first();
  await row.waitFor({ state: 'visible', timeout: 10000 });
}

/**
 * Edit an existing asset category via the UI.
 *
 * @param page - Playwright Page object.
 * @param code - Code of the asset category to edit.
 * @param updates - Fields to update.
 */
export async function editAssetCategory(
  page: Page,
  code: string,
  updates: { name?: string; useful_life_months_default?: string }
): Promise<void> {
  // Locate the asset category first
  await searchAssetCategory(page, code);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: code }).first();
  await expect(row).toBeVisible();
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Edit menu item
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click({ force: true });

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // Update fields if provided
  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }
  if (updates.useful_life_months_default) {
    await dialog.locator('input[name="useful_life_months_default"]').fill(updates.useful_life_months_default);
  }

  // Submit the edit dialog
  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

/**
 * Delete an asset category via the UI.
 *
 * @param page - Playwright Page object.
 * @param code - Code of the asset category to delete.
 */
export async function deleteAssetCategory(page: Page, code: string): Promise<void> {
  // Locate the asset category first
  await searchAssetCategory(page, code);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: code }).first();
  await expect(row).toBeVisible();
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Delete menu item
  const deleteBtn = page.getByRole('menuitem', { name: /Delete/i });
  await expect(deleteBtn).toBeVisible();
  await deleteBtn.click({ force: true });

  // Confirm delete in dialog
  const deleteBtnConfirm = page.getByRole('button', { name: /Delete/i }).last();
  await deleteBtnConfirm.click();

  // Verify deletion
  await page.waitForSelector(`text=${code}`, { state: 'detached' });
}
