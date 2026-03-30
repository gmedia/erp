import { Page, expect } from '@playwright/test';

async function pickFirstAsyncOption(page: Page, searchText?: string): Promise<void> {
  const list = page.locator('[role="listbox"]:visible, ul[aria-busy]:visible').last();
  await expect(list).toBeVisible({ timeout: 10000 });

  const searchInput = page.locator('input[placeholder="Search..."]:visible').last();
  if (searchText && (await searchInput.isVisible().catch(() => false))) {
    await searchInput.fill(searchText);
    await page.waitForTimeout(500);
  }

  const option = list.locator('[role="option"], button').first();
  await expect(option).toBeVisible({ timeout: 10000 });
  await option.click({ force: true });
  await expect(list).toBeHidden({ timeout: 5000 }).catch(() => null);
}

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
  await page.goto('/asset-movements');

  // Open the "Add Movement" dialog
  await page.getByRole('button', { name: /Add/i }).click();
  const dialog = page.getByRole('dialog', { name: /movement/i });
  await expect(dialog).toBeVisible();

  // Select Asset (AsyncSelect)
  const assetTrigger = dialog.locator('button').filter({ hasText: /Select asset/i });
  await assetTrigger.click();
  await pickFirstAsyncOption(page, overrides.asset_id);

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
    await pickFirstAsyncOption(page, overrides.to_branch_id);

    // Location
    const locationTrigger = dialog.locator('button').filter({ hasText: /Select destination location/i });
    await locationTrigger.click();
    await pickFirstAsyncOption(page, overrides.to_location_id);
    await expect(dialog.getByRole('combobox', { name: /To Location/i })).not.toContainText(/Select destination location/i);
  } else if (targetType.toLowerCase() === 'assign') {
    // Department
    const deptTrigger = dialog.locator('button').filter({ hasText: /Select department/i });
    await deptTrigger.click();
    await pickFirstAsyncOption(page, overrides.to_department_id);

    // Employee
    const empTrigger = dialog.locator('button').filter({ hasText: /Select employee/i });
    await empTrigger.click();
    await pickFirstAsyncOption(page, overrides.to_employee_id);
  }

  const reference = overrides.reference ?? `MOV-${Date.now()}`;
  await dialog.locator('input[name="reference"]').fill(reference);

  if (overrides.notes) {
    await dialog.locator('textarea[name="notes"]').fill(overrides.notes);
  }

  // Submit
  const createResponsePromise = page.waitForResponse(
    (response) =>
      response.url().includes('/api/asset-movements') &&
      response.request().method() === 'POST' &&
      response.status() < 400,
  );
  const submitBtn = dialog.getByRole('button', { name: /Record Movement/i }).last();
  await submitBtn.click();
  await createResponsePromise;

  // Wait for dialog to disappear
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  await searchAssetMovement(page, reference);
  await expect(page.getByText(reference).first()).toBeVisible({ timeout: 10000 });

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
  const responsePromise = page.waitForResponse(
    (response) =>
      response.url().includes('/api/asset-movements') &&
      response.request().method() === 'GET' &&
      response.status() < 400,
  ).catch(() => null);
  await searchInput.press('Enter');
  await responsePromise;
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
