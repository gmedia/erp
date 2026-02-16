import { Page, expect } from '@playwright/test';
import { login } from '../helpers';

/**
 * Create a new asset location via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override default values.
 * @returns The unique name used for the created asset location.
 */
export async function createAssetLocation(
  page: Page,
  overrides: Partial<{
    code: string;
    name: string;
    branch_id: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `Location ${timestamp}`;
  const defaultCode = `LOC-${timestamp}`;

  await login(page);
  await page.goto('/asset-locations');

  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const dialog = page.getByRole('dialog', { name: /Add New Asset Location/i });
  await expect(dialog).toBeVisible();

  // Fill the form
  const code = overrides.code ?? defaultCode;
  const name = overrides.name ?? defaultName;
  await dialog.locator('input[name="code"]').fill(code);
  await dialog.locator('input[name="name"]').fill(name);

  // Select branch
  await dialog.locator('button:has-text("Select a branch")').click();
  const searchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  if (await searchInput.isVisible()) {
    await searchInput.fill('');
  }
  await page.waitForTimeout(500);
  const branchOption = page.getByRole('option').first();
  await branchOption.click();

  // Submit
  const submitButton = dialog.getByRole('button', { name: /Add/i });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return name;
}

/**
 * Search for an asset location by name.
 *
 * @param page - Playwright Page object.
 * @param query - Query string to search for.
 */
export async function searchAssetLocation(page: Page, query: string): Promise<void> {
  await page.fill('input[placeholder="Search by code or name..."]', query);
  await page.press('input[placeholder="Search by code or name..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  // Wait for the row containing the query to appear
  const row = page.locator('tr').filter({ hasText: query }).first();
  await row.waitFor({ state: 'visible', timeout: 10000 });
}

/**
 * Edit an existing asset location via the UI.
 *
 * @param page - Playwright Page object.
 * @param locationName - Current location name to locate.
 * @param updates - Fields to update.
 */
export async function editAssetLocation(
  page: Page,
  locationName: string,
  updates: { code?: string; name?: string }
): Promise<void> {
  // Locate the asset location first
  await searchAssetLocation(page, locationName);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: locationName }).first();
  await expect(row).toBeVisible();
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Edit menu item
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click({ force: true });

  const dialog = page.getByRole('dialog', { name: /Edit Asset Location/i });
  await expect(dialog).toBeVisible();

  // Update fields if provided
  if (updates.code) {
    await dialog.locator('input[name="code"]').fill(updates.code);
  }
  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }

  // Submit the edit dialog
  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

/**
 * Delete an asset location via the UI.
 *
 * @param page - Playwright Page object.
 * @param locationName - Location name of the asset location to delete.
 */
export async function deleteAssetLocation(page: Page, locationName: string): Promise<void> {
  // Locate the asset location first
  await searchAssetLocation(page, locationName);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: locationName }).first();
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
  await page.waitForSelector(`text=${locationName}`, { state: 'detached' });
}
