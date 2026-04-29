import { Page, expect } from '@playwright/test';
import { ensureAppOrigin, login, searchAndWaitForApi, waitForApiAfterAction } from '../helpers';

async function pickAsyncOption(page: Page, label: string): Promise<void> {
  const option = page
    .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
    .filter({ hasText: new RegExp(`^${label}`, 'i') })
    .first();
  await expect(option).toBeVisible({ timeout: 10000 });
  await option.click({ force: true });
}

async function ensureAuthenticated(page: Page): Promise<void> {
  await ensureAppOrigin(page);

  const hasApiToken = await page
    .evaluate(() => Boolean(localStorage.getItem('api_token')))
    .catch(() => false);

  if (!hasApiToken) {
    await login(page, undefined, undefined, { requireDashboard: false });
  }
}

/**
 * Create a new asset via the UI.
 */
export async function createAsset(
  page: Page,
  overrides: Partial<{
    asset_code: string;
    name: string;
    category: string;
    branch: string;
    purchase_cost: string;
    status: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  // Ensure we consistently use 'asset_code' or name as the identifier returned.
  // The generic factory often expects the name/identifier used for searching.
  // Search usually works on name or code. Let's return the name if that's what we search by default, or code.
  // AssetColumns checks 'asset_code' and 'name'.
  
  const assetCode = overrides.asset_code ?? `AST-${timestamp}`;
  const assetName = overrides.name ?? `Test Asset ${timestamp}`;

  await ensureAuthenticated(page);
  await waitForApiAfterAction(page, '/api/assets', () => page.goto('/assets'), 30000);

  // Open the "Add Asset" dialog
  await page.getByRole('button', { name: /Add/i }).click();
  const dialog = page.getByRole('dialog', { name: /Add New Asset/i });
  await expect(dialog).toBeVisible();

  // Fill required fields
  await dialog.locator('input[name="asset_code"]').fill(assetCode);
  await dialog.locator('input[name="name"]').fill(assetName);

  // Select Category (AsyncSelect)
  const categoryTrigger = dialog.locator('button').filter({ hasText: /Select a category/i });
  await categoryTrigger.click();
  const categorySearchInput = page.getByPlaceholder('Search...').last();
  await expect(categorySearchInput).toBeVisible();
  const categoryName = overrides.category ?? 'IT Equipment'; // Ensure this category exists in seed
  await categorySearchInput.fill(categoryName);
  
  // Strict check might fail if 'IT' matches multiple. distinct name is better.
  // Using generic first option if strict match fails is a pattern used elsewhere.
  try {
      await pickAsyncOption(page, categoryName);
  } catch {
      // Fallback
      await page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible').first().click({ force: true });
  }

  // Select Branch (AsyncSelect)
  const branchTrigger = dialog.locator('button').filter({ hasText: /Select a branch/i });
  await branchTrigger.click();
  const branchSearchInput = page.getByPlaceholder('Search...').last();
  await expect(branchSearchInput).toBeVisible();
  const branchName = overrides.branch ?? 'Head Office';
  await branchSearchInput.fill(branchName);
  
  try {
     await pickAsyncOption(page, branchName);
  } catch {
      await page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible').first().click({ force: true });
  }

  // Purchase Information
  const cost = overrides.purchase_cost ?? '1000000';
  await dialog.locator('input[name="purchase_cost"]').fill(cost);

  // Status (Select)
  if (overrides.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Draft|Active|Inactive/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: overrides.status }).click();
  }

  // Submit
  const submitBtn = dialog.getByRole('button', { name: /Add/i }).last();
  const createResponsePromise = page.waitForResponse(
    response =>
      response.url().includes('/api/assets') &&
      response.request().method() === 'POST' &&
      response.status() < 400,
    { timeout: 15000 },
  );
  const reloadResponsePromise = page.waitForResponse(
    response =>
      response.url().includes('/api/assets') &&
      response.request().method() === 'GET' &&
      response.status() < 400,
    { timeout: 15000 },
  );
  await submitBtn.click();
  await createResponsePromise;

  // Wait for dialog to disappear, using passive wait pattern
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
  await reloadResponsePromise;

  return assetName; // Returning name as it's often the primary column for verify
}

/**
 * Search for an asset.
 */
export async function searchAsset(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search assets.../i);
  await expect(searchInput).toBeVisible();
  const normalizedQuery = query.trim();
  if ((await searchInput.inputValue()).trim() === normalizedQuery) {
    return;
  }

  await searchAndWaitForApi(
    page,
    searchInput,
    normalizedQuery,
    url => url.includes('/api/assets') && url.includes('search='),
    15000,
  );

  // Passive wait: don't assert rows here to allow for "search after delete" tests
}

export async function editAsset(
  page: Page,
  identifier: string,
  updates: Record<string, string>,
): Promise<void> {
  await searchAsset(page, identifier);

  const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
  await expect(row).toBeVisible();

  await row.getByRole('button', { name: /Actions/i }).click();
  await page.getByRole('menuitem', { name: /Edit/i }).click();

  const dialog = page.getByRole('dialog', { name: /Edit Asset/i });
  await expect(dialog).toBeVisible();

  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }

  if (updates.asset_code) {
    await dialog.locator('input[name="asset_code"]').fill(updates.asset_code);
  }

  const updateResponsePromise = page.waitForResponse(
    (response) =>
      response.url().includes('/api/assets') &&
      ['PUT', 'PATCH'].includes(response.request().method()) &&
      response.status() < 400,
    { timeout: 15000 },
  );

  await dialog.getByRole('button', { name: /Update|Save/i }).last().click();
  await updateResponsePromise;
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

/**
 * Create a high-value asset (purchase_cost > 100M) to trigger approval flow.
 */
export async function createHighValueAsset(
  page: Page,
  overrides: Partial<{
    asset_code: string;
    name: string;
    category: string;
    branch: string;
    purchase_cost: string;
  }> = {}
): Promise<string> {
  return createAsset(page, {
    asset_code: overrides.asset_code ?? `HVA-${Date.now()}`,
    name: overrides.name ?? `High Value Asset ${Date.now()}`,
    category: overrides.category ?? 'IT Equipment',
    branch: overrides.branch ?? 'Head Office',
    purchase_cost: overrides.purchase_cost ?? '150000000', // 150M > 100M threshold
    ...overrides,
  });
}

export async function deleteAsset(page: Page, identifier: string): Promise<void> {
    await searchAsset(page, identifier);

    // Find the row
    const row = page.locator('tr', { hasText: identifier }).first();
    await expect(row).toBeVisible();

    // Click Actions dropdown
    row.locator('button').filter({ hasText: /Actions/i }); // Usually "Actions" or the ellipsis icon
    // If it's the standard createActionsColumn, it renders a button with sr-only "Open menu" or visible text?
    // Looking at other modules, it's often the ellipsis `...` which might have aria-label "Open menu" or similar.
    // Or it might be explicit text.
    // Let's try finding the button in the last cell.
    const actionsCell = row.locator('td').last();
    // Assuming standard ActionsDropdown from @/components/ui/data-table/data-table-row-actions (if used) or standard dropdown.
    // The shared test factories default delete action assumes:
    // row.getByRole('button', { name: 'Open menu' }).click() -> page.getByRole('menuitem', { name: 'Delete' }).click()
    
    // If createActionsColumn uses standard dropdown:
    const menuBtn = actionsCell.locator('button[aria-haspopup="menu"], button[role="button"]').first();
    await menuBtn.click();

    const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
    await expect(deleteItem).toBeVisible();
    await deleteItem.click();

    // Confirm dialog
    const confirmBtn = page.getByRole('button', { name: /Delete|Confirm/i }).last();
    await expect(confirmBtn).toBeVisible();
    await confirmBtn.click();

    await expect(row).not.toBeVisible();
}
