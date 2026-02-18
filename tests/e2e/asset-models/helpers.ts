import { Page, expect } from '@playwright/test';
import { createAssetCategory } from '../asset-categories/helpers';

/**
 * Create a new asset model via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override default values.
 * @returns The unique model name used for the created asset model.
 */
export async function createAssetModel(
  page: Page,
  overrides: Partial<{
    model_name: string;
    manufacturer: string;
    asset_category_id: string;
    specs: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultModelName = `Model ${timestamp}`;

  // First create an asset category if not provided
  let categoryName = overrides.asset_category_id;
  if (!categoryName) {
    categoryName = `Category ${timestamp}`;
    await createAssetCategory(page, { name: categoryName });
  }

  // Navigate back to asset-models (createAssetCategory navigates away)
  await page.goto('/asset-models', { waitUntil: 'domcontentloaded', timeout: 60000 });

  const addButton = page.getByRole('button', { name: /Add/i, exact: true });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const dialog = page.getByRole('dialog', { name: 'Add New Asset Model' });
  await expect(dialog).toBeVisible();

  // Fill the form
  const modelName = overrides.model_name ?? defaultModelName;
  await dialog.locator('input[name="model_name"]').fill(modelName);

  if (overrides.manufacturer) {
    await dialog.locator('input[name="manufacturer"]').fill(overrides.manufacturer);
  } else {
    await dialog.locator('input[name="manufacturer"]').fill('Test Manufacturer');
  }

  // Select category
  const categoryTrigger = dialog.locator('button').filter({ hasText: /Select a category/i });
  await categoryTrigger.click();
  const searchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  if (await searchInput.isVisible()) {
    await searchInput.fill(categoryName);
  }
  await page.waitForTimeout(500);
  const categoryOption = page.getByRole('option', { name: categoryName, exact: true }).first();
  await categoryOption.click();

  // Specs (JSON)
  if (overrides.specs) {
    await dialog.locator('textarea[name="specs"]').fill(overrides.specs);
  } else {
    await dialog.locator('textarea[name="specs"]').fill('{"test": "data"}');
  }

  // Submit
  const submitButton = dialog.getByRole('button', { name: /Add/i, exact: true });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return modelName;
}

/**
 * Search for an asset model by name.
 *
 * @param page - Playwright Page object.
 * @param query - Query string to search for.
 */
export async function searchAssetModel(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search by model name or manufacturer...');
  await expect(searchInput).toBeVisible();

  // Start listening for response before triggering search
  const responsePromise = page.waitForResponse(resp => 
    resp.url().includes('/api/asset-models') && resp.status() < 400
  ).catch(() => null);

  await searchInput.clear();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  
  // Wait for the table to refresh
  await responsePromise;
}

/**
 * Edit an existing asset model via the UI.
 *
 * @param page - Playwright Page object.
 * @param modelName - Current model name to locate.
 * @param updates - Fields to update.
 */
export async function editAssetModel(
  page: Page,
  modelName: string,
  updates: { model_name?: string; manufacturer?: string; specs?: string }
): Promise<void> {
  // Locate the asset model first
  await searchAssetModel(page, modelName);

  // Locate the row and open the Actions menu
  const row = page.locator('tbody tr', { hasText: modelName }).first();
  await expect(row).toBeVisible();
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Edit menu item
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click({ force: true });

  const dialog = page.getByRole('dialog', { name: 'Edit Asset Model' });
  await expect(dialog).toBeVisible();

  // Update fields if provided
  if (updates.model_name) {
    await dialog.locator('input[name="model_name"]').fill(updates.model_name);
  }
  if (updates.manufacturer) {
    await dialog.locator('input[name="manufacturer"]').fill(updates.manufacturer);
  }
  if (updates.specs) {
    await dialog.locator('textarea[name="specs"]').fill(updates.specs);
  }

  // Submit the edit dialog
  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await expect(updateBtn).toBeVisible();
  
  const responsePromise = page.waitForResponse(resp => 
    resp.url().includes('/api/asset-models') && resp.status() < 400
  ).catch(() => null);

  await updateBtn.click();
  await responsePromise;

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  // Clear search for next step (optional but safer)
  await searchInputSafeClear(page);
}

async function searchInputSafeClear(page: Page) {
  const searchInput = page.getByPlaceholder('Search by model name or manufacturer...');
  if (await searchInput.isVisible()) {
    const responsePromise = page.waitForResponse(resp => 
      resp.url().includes('/api/asset-models') && resp.status() < 400
    ).catch(() => null);
    await searchInput.clear();
    await searchInput.press('Enter');
    await responsePromise;
  }
}

/**
 * Delete an asset model via the UI.
 *
 * @param page - Playwright Page object.
 * @param modelName - Model name of the asset model to delete.
 */
export async function deleteAssetModel(page: Page, modelName: string): Promise<void> {
  // Locate the asset model first
  await searchAssetModel(page, modelName);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: modelName }).first();
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
  
  const responsePromise = page.waitForResponse(resp => 
    resp.url().includes('/api/asset-models') && resp.status() < 400
  ).catch(() => null);
  
  await deleteBtnConfirm.click();
  await responsePromise;

  // Wait for deletion
  await page.waitForTimeout(500); 
}
