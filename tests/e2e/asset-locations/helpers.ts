import { Page, expect } from '@playwright/test';

/**
 * Create a new asset location via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override default values.
 * @returns The identifier used for the created asset location (name).
 */
export async function createAssetLocation(
  page: Page,
  overrides: Partial<{
    code: string;
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `Location ${timestamp}`;
  const defaultCode = `LOC-${timestamp}`;

  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const dialog = page.getByRole('dialog', { name: /Add/i });
  await expect(dialog).toBeVisible();

  // Fill the form
  const code = overrides.code ?? defaultCode;
  const name = overrides.name ?? defaultName;
  await dialog.locator('input[name="code"]').fill(code);
  await dialog.locator('input[name="name"]').fill(name);

  // Select branch (Mandatory)
  await dialog.locator('button:has-text("Select a branch")').click();
  // Wait for the popover/dropdown to be visible
  await page.waitForSelector('[role="option"]', { state: 'visible' });
  const branchOption = page.getByRole('option').first();
  await branchOption.click();

  // Submit
  const submitButton = dialog.getByRole('button', { name: /Add/i });
  await expect(submitButton).toBeVisible();
  
  const responsePromise = page.waitForResponse(resp => 
    resp.url().includes('/api/asset-locations') && resp.status() < 400
  ).catch(() => null);

  await submitButton.click();
  await responsePromise;

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
  const searchInput = page.getByPlaceholder('Search asset locations...');
  await expect(searchInput).toBeVisible();

  // Start listening for response before triggering search
  const responsePromise = page.waitForResponse(resp => 
    resp.url().includes('/api/asset-locations') && resp.status() < 400
  ).catch(() => null);

  await searchInput.clear();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  
  // Wait for the table to refresh
  await responsePromise;
}

/**
 * Edit an existing asset location via the UI.
 *
 * @param page - Playwright Page object.
 * @param identifier - Current locator (name/code) to locate.
 * @param updates - Fields to update.
 */
export async function editAssetLocation(
  page: Page,
  identifier: string,
  updates: Record<string, string>
): Promise<void> {
  // Locate the row and open the Actions menu
  const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button').last();
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Edit menu item
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click({ force: true });

  const dialog = page.getByRole('dialog', { name: /Edit/i });
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
  
  const responsePromise = page.waitForResponse(resp => 
    resp.url().includes('/api/asset-locations') && resp.status() < 400
  ).catch(() => null);

  await updateBtn.click();
  await responsePromise;

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

/**
 * Delete an asset location via the UI.
 *
 * @param page - Playwright Page object.
 * @param identifier - Identifier of the asset location to delete.
 */
export async function deleteAssetLocation(page: Page, identifier: string): Promise<void> {
  // Locate the row and open the Actions menu
  const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button').last();
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Delete menu item
  const deleteBtn = page.getByRole('menuitem', { name: /Delete/i });
  await expect(deleteBtn).toBeVisible();
  await deleteBtn.click({ force: true });

  // Confirm delete in dialog
  const deleteBtnConfirm = page.getByRole('button', { name: /Delete|Confirm|Continue/i }).last();
  
  const responsePromise = page.waitForResponse(resp => 
    resp.url().includes('/api/asset-locations') && resp.status() < 400
  ).catch(() => null);
  
  await deleteBtnConfirm.click();
  await responsePromise;

  // Wait for row to disappear
  await expect(row).not.toBeVisible({ timeout: 10000 });
}
