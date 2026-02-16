import { Page, expect } from '@playwright/test';
import { login } from '../helpers';

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

  await login(page);
  await page.goto('/assets');

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
      const categoryOption = page.getByRole('option', { name: new RegExp(`^${categoryName}`, 'i') }).first();
      await expect(categoryOption).toBeVisible({ timeout: 2000 });
      await categoryOption.click();
  } catch (e) {
      // Fallback
      await page.getByRole('option').first().click();
  }

  // Select Branch (AsyncSelect)
  const branchTrigger = dialog.locator('button').filter({ hasText: /Select a branch/i });
  await branchTrigger.click();
  const branchSearchInput = page.getByPlaceholder('Search...').last();
  await expect(branchSearchInput).toBeVisible();
  const branchName = overrides.branch ?? 'Head Office';
  await branchSearchInput.fill(branchName);
  
  try {
    const branchOption = page.getByRole('option', { name: new RegExp(`^${branchName}`, 'i') }).first();
    await expect(branchOption).toBeVisible({ timeout: 2000 });
    await branchOption.click();
  } catch(e) {
     await page.getByRole('option').first().click();
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
  await submitBtn.click();

  // Wait for dialog to disappear, using passive wait pattern
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
  await page.waitForResponse(r => r.url().includes('/api/assets') && r.status() === 200).catch(() => null);

  return assetName; // Returning name as it's often the primary column for verify
}

/**
 * Search for an asset.
 */
export async function searchAsset(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search assets.../i);
  await expect(searchInput).toBeVisible();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  
  // Wait for API response
  await page.waitForResponse(r => r.url().includes('/api/assets') && r.status() === 200).catch(() => null);
  // Passive wait: don't assert rows here to allow for "search after delete" tests
}

export async function deleteAsset(page: Page, identifier: string): Promise<void> {
    await searchAsset(page, identifier);

    // Find the row
    const row = page.locator('tr', { hasText: identifier }).first();
    await expect(row).toBeVisible();

    // Click Actions dropdown
    const actionsBtn = row.locator('button').filter({ hasText: /Actions/i }); // Usually "Actions" or the ellipsis icon
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
