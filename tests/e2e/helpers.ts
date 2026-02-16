import { Page, expect } from '@playwright/test';

// Generic entity creation helper
export interface EntityField {
  name: string;
  type: 'text' | 'email' | 'select' | 'textarea';
  selector?: string;
  defaultValue: string;
  optionSelector?: string;
}

export interface EntityConfig {
  route: string;
  fields: EntityField[];
  returnField: string; // field name to return as identifier
}

/**
 * Generic function to create any entity via the UI.
 *
 * @param page - Playwright Page object.
 * @param config - Entity configuration.
 * @param overrides - Optional field overrides.
 * @returns The unique identifier field value used for the created entity.
 */
export async function createEntity(
  page: Page,
  config: EntityConfig,
  overrides: Record<string, string> = {}
): Promise<string> {
  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to entity list page
  await page.goto(config.route);

  // 3️⃣ Open the "Add Entity" dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // 4️⃣ Fill the form fields
  let returnValue = '';

  for (const field of config.fields) {
    const value = overrides[field.name] ?? field.defaultValue;

    if (field.name === config.returnField) {
      returnValue = value;
    }

    if (field.type === 'select') {
      // Find the combobox by role and name (using partial match for robustness)
      const combobox = page.getByRole('combobox', { name: new RegExp(field.selector || value, 'i') }).first();
      await expect(combobox).toBeVisible();
      await combobox.click();
      
      // Wait for listbox to be visible
      const listbox = page.getByRole('listbox');
      await expect(listbox).toBeVisible();

      // Search if input is available
      const searchInput = listbox.getByPlaceholder('Search...');
      if (await searchInput.isVisible()) {
        await searchInput.fill(value);
        // Wait for debounce and fetch
        await page.waitForTimeout(500);
      }

      // Click the option
      const option = listbox.getByRole('option', { name: new RegExp(value, 'i') }).first();
      await expect(option).toBeVisible();
      await option.click();

      // Wait for listbox to disappear
      await expect(listbox).not.toBeVisible();
    }
 else if (field.type === 'textarea') {
      await page.fill(`textarea[name="${field.name}"]`, value);
    } else {
      await page.fill(`input[name="${field.name}"]`, value);
    }
  }

  // Ensure the dialog is visible before interacting
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();
  const submitButton = dialog.getByRole('button', { name: /Add|Tambah|Create|Buat|Update|Perbarui|Submit|Kirim/i });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  // Wait for dialog to disappear with a descriptive error
  try {
      await expect(dialog).not.toBeVisible({ timeout: 15000 });
  } catch (error) {
      console.error(`Dialog did not close after 15s. Likely validation error or backend failure.`);
      // Check for visible error messages in the dialog
      const errorMessages = await dialog.locator('.text-destructive, .text-red-500, [role="alert"]').allTextContents();
      if (errorMessages.length > 0) {
          console.error(`Found error messages in dialog: ${errorMessages.join(', ')}`);
      }
      throw error;
  }

  return returnValue;
}

/**
 * Logs in a user using the provided credentials.
 *
 * @param page - Playwright Page object.
 * @param email - User email address. Defaults to 'admin@admin.com'.
 * @param password - User password. Defaults to 'password'.
 *
 * @returns A promise that resolves when navigation to the dashboard is complete.
 */
export async function login(
  page: Page,
  email = 'admin@admin.com',
  password = 'password'
): Promise<void> {
  if (page.url().includes('/dashboard')) return;

  const gotoWithRetry = async (url: string): Promise<void> => {
    let lastError: unknown;
    for (let attempt = 0; attempt < 3; attempt++) {
      try {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        return;
      } catch (err) {
        lastError = err;
        const message = err instanceof Error ? err.message : String(err);
        const retriable =
          message.includes('net::ERR_ABORTED') ||
          message.toLowerCase().includes('frame was detached') ||
          message.toLowerCase().includes('navigation');
        if (!retriable) throw err;
        await page.waitForTimeout(750);
      }
    }
    throw lastError instanceof Error ? lastError : new Error(String(lastError));
  };

  await gotoWithRetry('/dashboard');

  if (page.url().includes('/dashboard')) return;

  const emailInput = page.locator('input[name="email"]');
  if (page.url().includes('/login') || (await emailInput.isVisible({ timeout: 10000 }).catch(() => false))) {
    if (!page.url().includes('/login')) {
      await gotoWithRetry('/login');
    }
    await expect(emailInput).toBeVisible({ timeout: 15000 });
    await emailInput.fill(email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"], button[data-testid="login-button"]');
    await page.waitForURL('**/dashboard', { timeout: 60000 });
    return;
  }

  await page.waitForURL('**/dashboard', { timeout: 60000 });
}



/**
 * Generic function to create an account via the UI.
 */
export async function createAccount(
  page: Page,
  overrides: Partial<{
    coa_version: string;
    code: string;
    name: string;
    type: string;
    normal_balance: string;
    is_active: boolean;
    is_cash_flow: boolean;
    description: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `Account ${timestamp}`;
  const defaultCode = `CODE${timestamp.toString().slice(-5)}`;

  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to Accounts page
  await page.goto('/accounts');
  await page.waitForLoadState('networkidle');

  // 3️⃣ Select COA Version if provided
  if (overrides.coa_version) {
    // Try to find the selector trigger by role or text
    const versionTrigger = page.getByRole('combobox').or(page.locator('button')).filter({ hasText: /Select COA Version|COA \d{4}/i }).first();
    await expect(versionTrigger).toBeVisible({ timeout: 30000 });
    await versionTrigger.click();
    
    const option = page.getByRole('option', { name: overrides.coa_version });
    await expect(option).toBeVisible({ timeout: 15000 });
    await option.first().click();
    await page.waitForLoadState('networkidle');
  }

  // 4️⃣ Open the "Add Account" dialog
  const addButton = page.getByRole('button', { name: /New Root Account/i });
  await expect(addButton).toBeVisible({ timeout: 15000 });
  await addButton.click();

  // Wait for dialog to be visible
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // 5️⃣ Fill the form fields
  const name = overrides.name ?? defaultName;
  const code = overrides.code ?? defaultCode;
  
  await dialog.locator('input[name="code"]').fill(code);
  await dialog.locator('input[name="name"]').fill(name);

  // Select type
  if (overrides.type) {
    const typeTrigger = dialog.locator('button').filter({ hasText: /Asset|Liability|Equity|Revenue|Expense/i });
    await typeTrigger.click();
    await page.getByRole('option', { name: overrides.type, exact: true }).click();
  }

  // Select normal balance
  if (overrides.normal_balance) {
    const balanceTrigger = dialog.locator('button').filter({ hasText: /Debit|Credit/i }).last();
    await balanceTrigger.click();
    await page.getByRole('option', { name: overrides.normal_balance, exact: true }).click();
  }

  // Checkboxes
  if (overrides.is_active === false) {
    await dialog.locator('button[id="is_active"]').click();
  }
  if (overrides.is_cash_flow === true) {
    await dialog.locator('button[id="is_cash_flow"]').click();
  }

  if (overrides.description) {
    await dialog.locator('textarea[id="description"]').fill(overrides.description);
  }

  // 6️⃣ Submit the form
  const submitButton = dialog.getByRole('button', { name: /Create/i }).first();
  await submitButton.click();

  // Wait for dialog to be hidden
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return code;
}

/**
 * Search for an account by code or name.
 */
export async function searchAccount(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search code or name/i);
  await searchInput.waitFor({ state: 'visible' });
  await searchInput.fill(query);
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');
}

/**
 * Edit an existing account.
 */
export async function editAccount(
  page: Page,
  code: string,
  updates: { name?: string; type?: string }
): Promise<void> {
  await searchAccount(page, code);

  const item = page.locator('div', { hasText: code }).first();
  await expect(item).toBeVisible();
  
  // Hover to reveal buttons
  await item.hover();

  // The Edit button has .text-blue-500 class
  const editBtn = item.locator('button.text-blue-500');
  await editBtn.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }

  if (updates.type) {
    const typeTrigger = dialog.locator('button').filter({ hasText: /Asset|Liability|Equity|Revenue|Expense/i });
    await typeTrigger.click();
    await page.getByRole('option', { name: updates.type, exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update|Save|Submit/i }).first();
  await updateBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

/**
 * Delete an account.
 */
export async function deleteAccount(page: Page, code: string): Promise<void> {
  await searchAccount(page, code);

  // Find the row or tree item containing the code
  const item = page.locator('div', { hasText: code }).first();
  await expect(item).toBeVisible();
  
  // Hover to reveal buttons
  await item.hover();

  // The Delete button has .text-destructive class
  const deleteBtn = item.locator('button.text-destructive');
  await deleteBtn.click();

  // Confirm deletion in AlertDialog
  const confirmBtn = page.getByRole('button', { name: /Delete/i }).last();
  await confirmBtn.click();

  await page.waitForLoadState('networkidle');
}


// Asset Model helpers
// ---------------------------------------------------


// ---------------------------------------------------
// Asset helpers
// ---------------------------------------------------

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
  const categoryName = overrides.category ?? 'IT Equipment';
  await categorySearchInput.fill(categoryName);
  const categoryOption = page.getByRole('option', { name: new RegExp(`^${categoryName}$`, 'i') }).first();
  await expect(categoryOption).toBeVisible();
  await categoryOption.click();

  // Select Branch (AsyncSelect)
  const branchTrigger = dialog.locator('button').filter({ hasText: /Select a branch/i });
  await branchTrigger.click();
  const branchSearchInput = page.getByPlaceholder('Search...').last();
  await expect(branchSearchInput).toBeVisible();
  const branchName = overrides.branch ?? 'Head Office';
  await branchSearchInput.fill(branchName);
  const branchOption = page.getByRole('option', { name: new RegExp(`^${branchName}$`, 'i') }).first();
  await expect(branchOption).toBeVisible();
  await branchOption.click();

  // Purchase Information
  await dialog.locator('input[name="purchase_cost"]').fill(overrides.purchase_cost ?? '1000000');

  // Status (Select)
  if (overrides.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Draft|Active|Inactive/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: overrides.status }).click();
  }

  // Submit
  const submitBtn = dialog.getByRole('button', { name: /Add/i }).last();
  await submitBtn.click();

  // Wait for dialog to disappear
  await expect(dialog).not.toBeVisible({ timeout: 10000 });

  return assetCode;
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
    movement_type: string;
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
  if (overrides.movement_type) {
    const typeTrigger = dialog.locator('button').filter({ hasText: /Transfer|Assign|Return|Dispose|Adjustment/i }).first();
    await typeTrigger.click();
    // Use regex to match the label starting with the type name
    await page.getByRole('option', { name: new RegExp(`^${overrides.movement_type}`, 'i') }).click();
  }

  // Handle specific fields based on movement type
  const movementType = overrides.movement_type ?? 'Transfer';
  
  if (movementType.toLowerCase() === 'transfer') {
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
  } else if (movementType.toLowerCase() === 'assign') {
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

  if (overrides.reference) {
    await dialog.locator('input[name="reference"]').fill(overrides.reference);
  }

  if (overrides.notes) {
    await dialog.locator('textarea[name="notes"]').fill(overrides.notes);
  }

  // Submit
  const submitBtn = dialog.getByRole('button', { name: /Record Movement/i }).last();
  await submitBtn.click();

  // Wait for dialog to disappear
  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return overrides.reference ?? 'Movement';
}

/**
 * Search for an asset movement.
 */
export async function searchAssetMovement(page: Page, query: string): Promise<void> {
  const searchInput = page.locator('input[placeholder*="Search"], input[placeholder*="Filter"]').first();
  await expect(searchInput).toBeVisible();
  await searchInput.fill(query);
  await page.keyboard.press('Enter');
  await page.waitForLoadState('networkidle');
}

/**
 * Delete an asset movement.
 */
export async function deleteAssetMovement(page: Page, reference: string): Promise<void> {
  await searchAssetMovement(page, reference);
  const row = page.locator('tr', { hasText: reference }).first();
  await expect(row).toBeVisible();

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await deleteItem.click();

  const confirmBtn = page.getByRole('button', { name: /Delete/i }).last();
  await confirmBtn.click();

  await expect(row).not.toBeVisible();
}
