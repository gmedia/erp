import { Page, expect } from '@playwright/test';
import { login } from '../helpers';

// ---------------------------------------------------
// Asset Movement helpers
// ---------------------------------------------------

/**
 * Create a new asset movement via the UI.
 */
export async function createAssetMovement(
  page: Page,
  overrides: Partial<{
    asset_id: string; // This can be the name or code
    movement_type: string; // Transfer, Assign, Return, Dispose, Adjustment
    to_branch_id: string;
    to_location_id: string;
    to_department_id: string;
    to_employee_id: string;
    reference: string;
    notes: string;
  }> = {}
): Promise<string> {
  await login(page);
  await page.goto('/asset-movements');

  // Open the "Add Movement" dialog
  await page.getByRole('button', { name: /Add/i }).click();
  const dialog = page.getByRole('dialog', { name: /movement/i });
  await expect(dialog).toBeVisible();

  // Select Asset (AsyncSelect)
  const assetTrigger = dialog.locator('button').filter({ hasText: /Select asset/i });
  await assetTrigger.click();
  if (overrides.asset_id) {
    const assetSearchInput = page.getByPlaceholder('Search...').last();
    await assetSearchInput.fill(overrides.asset_id);
    await page.waitForTimeout(1000); // Wait for results
  }
  const assetOption = page.getByRole('option').first();
  await expect(assetOption).toBeVisible();
  await assetOption.click();

  // Select Movement Type (Select)
  // Default is usually 'Transfer'
  const targetType = overrides.movement_type ?? 'Transfer';
  
  // Only change type if necessary or if override provided
  // Note: The dialog might default to one type. We should ensure we select the right one.
  const typeTrigger = dialog.locator('button').filter({ hasText: /Transfer|Assign|Return|Dispose|Adjustment/i }).first();
  await typeTrigger.click();
  
  // Use regex to match the label starting with the type name (e.g., "Transfer (Location Change)")
  // Using explicit known types to be safe
  const typeRegex = new RegExp(`${targetType}`, 'i');
  await page.getByRole('option', { name: typeRegex }).first().click();
  

  // Handle specific fields based on movement type
  if (targetType.toLowerCase() === 'transfer') {
    // Branch
    const branchTrigger = dialog.locator('button').filter({ hasText: /Select destination branch/i });
    await branchTrigger.click();
    if (overrides.to_branch_id) {
      const branchSearchInput = page.getByPlaceholder('Search...').last();
      await branchSearchInput.fill(overrides.to_branch_id);
      await page.waitForTimeout(500);
    }
    const branchOption = page.getByRole('option').first();
    await expect(branchOption).toBeVisible();
    await branchOption.click();

    // Location
    const locationTrigger = dialog.locator('button').filter({ hasText: /Select destination location/i });
    await locationTrigger.click();
    if (overrides.to_location_id) {
      const locationSearchInput = page.getByPlaceholder('Search...').last();
      await locationSearchInput.fill(overrides.to_location_id);
      await page.waitForTimeout(500);
    }
    const locationOption = page.getByRole('option').first();
    await expect(locationOption).toBeVisible();
    await locationOption.click();
  } else if (targetType.toLowerCase() === 'assign') {
    // Department
    const deptTrigger = dialog.locator('button').filter({ hasText: /Select department/i });
    await deptTrigger.click();
    if (overrides.to_department_id) {
      const deptSearchInput = page.getByPlaceholder('Search...').last();
      await deptSearchInput.fill(overrides.to_department_id);
      await page.waitForTimeout(500);
    }
    const deptOption = page.getByRole('option').first();
    await expect(deptOption).toBeVisible();
    await deptOption.click();

    // Employee
    const empTrigger = dialog.locator('button').filter({ hasText: /Select employee/i });
    await empTrigger.click();
    if (overrides.to_employee_id) {
      const empSearchInput = page.getByPlaceholder('Search...').last();
      await empSearchInput.fill(overrides.to_employee_id);
      await page.waitForTimeout(500);
    }
    const empOption = page.getByRole('option').first();
    await expect(empOption).toBeVisible();
    await empOption.click();
  }

  const reference = overrides.reference ?? `MOV-${Date.now()}`;
  await dialog.locator('input[name="reference"]').fill(reference);

  if (overrides.notes) {
    await dialog.locator('textarea[name="notes"]').fill(overrides.notes);
  }

  // Submit
  const submitBtn = dialog.getByRole('button', { name: /Record Movement/i }).last();
  await submitBtn.click();

  // Wait for dialog to disappear
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return reference; // Return reference as identifier
}

/**
 * Search for an asset movement.
 * Uses passive wait pattern.
 */
export async function searchAssetMovement(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search/i).first();
  await expect(searchInput).toBeVisible();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');
  // Passive wait: don't assert visibility of results here to allow "delete" tests to verify absence
}

/**
 * Delete an asset movement.
 */
export async function deleteAssetMovement(page: Page, reference: string): Promise<void> {
  await searchAssetMovement(page, reference);
  
  // Find row by reference
  // Note: Reference column might be combined in one cell or separate. 
  // Based on columns, ref/notes is one column.
  const row = page.locator('tbody tr').filter({ hasText: reference }).first();
  await expect(row).toBeVisible();

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await deleteItem.click();

  const confirmBtn = page.getByRole('button', { name: /Delete/i }).last();
  await confirmBtn.click();

  await expect(row).not.toBeVisible();
}
