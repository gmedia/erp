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
      await page.click(`button:has-text("${field.selector}")`);
      
      // Type in the search box to filter results (handles pagination)
      const searchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
      if (await searchInput.isVisible()) {
        await searchInput.fill(value);
      }
      
      await page.getByRole('option', { name: value }).click();
    } else if (field.type === 'textarea') {
      await page.fill(`textarea[name="${field.name}"]`, value);
    } else {
      await page.fill(`input[name="${field.name}"]`, value);
    }
  }

  // Ensure any modal overlay/backdrop is removed before submitting
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const submitButton = dialog.getByRole('button', { name: /Add/ });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  await expect(dialog).not.toBeVisible({ timeout: 30000 });

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
 * Create a new employee via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique email used for the created employee.
 */
export async function createEmployee(
  page: Page,
  overrides: Partial<{
    name: string;
    email: string;
    phone: string;
    salary: string;
    department_id: string;
    position_id: string;
    branch_id: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultEmail = `${Math.random().toString(36).substring(2,7)}${timestamp}@example.com`;

  const config: EntityConfig = {
    route: '/employees',
    returnField: 'email',
    fields: [
      { name: 'name', type: 'text', defaultValue: `Employee ${timestamp}` },
      { name: 'email', type: 'email', defaultValue: defaultEmail },
      { name: 'phone', type: 'text', defaultValue: '08123456789' },
      { name: 'department_id', type: 'select', selector: 'Select a department', defaultValue: 'Engineering' },
      { name: 'position_id', type: 'select', selector: 'Select a position', defaultValue: 'Senior Developer' },
      { name: 'branch_id', type: 'select', selector: 'Select a branch', defaultValue: 'Head Office' },
      { name: 'salary', type: 'text', defaultValue: '5000000' },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for an employee by email.
 *
 * @param page - Playwright Page object.
 * @param email - Email address to search for.
 */
export async function searchEmployee(page: Page, email: string): Promise<void> {
  await page.fill('input[placeholder="Search employees..."]', email);
  await page.press('input[placeholder="Search employees..."]', 'Enter');
  // Wait for the row containing the email to appear
  await page.waitForSelector(`text=${email}`);
}

/**
 * Edit an existing employee.
 *
 * @param page - Playwright Page object.
 * @param email - Email of the employee to edit.
 * @param updates - Fields to update (name and/or salary).
 */
export async function editEmployee(
  page: Page,
  email: string,
  updates: { name?: string; salary?: string }
): Promise<void> {
  // Locate the employee first
  await searchEmployee(page, email);

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

  // Update fields if provided
  if (updates.name) {
    await page.fill('input[name="name"]', updates.name);
  }
  if (updates.salary) {
    await page.fill('input[name="salary"]', updates.salary);
  }

  // Submit the edit dialog
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const editDialog = page.getByRole('dialog');
  const updateBtn = editDialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
}



// ---------------------------------------------------
// Customer helpers
// ---------------------------------------------------

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
  const timestamp = Date.now();
  const defaultEmail = `customer${Math.random().toString(36).substring(2,7)}${timestamp}@example.com`;

  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to customers page
  await page.goto('/customers');

  // 3️⃣ Open the "Add Customer" dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();
  
  // Wait for dialog to be visible
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // 4️⃣ Fill the form fields
  const email = overrides.email ?? defaultEmail;
  
  await page.fill('input[name="name"]', overrides.name ?? 'Test Customer');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', overrides.phone ?? '+628123456789');
  await page.fill('textarea[name="address"]', overrides.address ?? '123 Test Street, City, Country');
  
  // Select branch (AsyncSelectField)
  const branchTrigger = dialog.locator('button').filter({ hasText: /Select a branch/i });
  await branchTrigger.click();
  const branchSearchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  const branchName = overrides.branch_id ?? 'Head Office';
  if (await branchSearchInput.isVisible()) {
    await branchSearchInput.fill(branchName);
  }
  await page.getByRole('option', { name: branchName }).click();
  
  // Select category (AsyncSelectField)
  const categoryTrigger = dialog.locator('button').filter({ hasText: /Select a category/i });
  await categoryTrigger.click();
  const categorySearchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  const categoryName = overrides.category_id ?? 'Retail';
  if (await categorySearchInput.isVisible()) {
    await categorySearchInput.fill(categoryName);
    // Wait for the option to be stable and visible before clicking
    await expect(page.getByRole('option', { name: categoryName })).toBeVisible();
  }
  await page.getByRole('option', { name: categoryName }).click();
  
  // Select status (SelectField)
  const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Active|Inactive/i });
  await statusTrigger.click();
  const status = overrides.status ?? 'Active';
  await page.getByRole('option', { name: status, exact: true }).click();
  
  // Notes (optional)
  if (overrides.notes) {
    await page.fill('textarea[name="notes"]', overrides.notes);
  }

  // 5️⃣ Submit the form - use JS click to bypass viewport issue
  const submitButton = dialog.getByRole('button', { name: /Add/i }).last();
  await expect(submitButton).toBeVisible();
  await submitButton.evaluate((el: HTMLElement) => el.click());
  
  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 10000 });

  return email;
}

/**
 * Search for a customer by email.
 *
 * @param page - Playwright Page object.
 * @param email - Email address to search for.
 */
export async function searchCustomer(page: Page, email: string): Promise<void> {
  await page.fill('input[placeholder="Search customers..."]', email);
  await page.press('input[placeholder="Search customers..."]', 'Enter');
  // Wait for the row containing the email to appear
  await page.waitForSelector(`text=${email}`);
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

// ---------------------------------------------------
// Supplier helpers
// ---------------------------------------------------

/**
 * Create a new supplier via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique email used for the created supplier.
 */
export async function createSupplier(
  page: Page,
  overrides: Partial<{
    name: string;
    email: string;
    phone: string;
    address: string;
    branch: string;
    category: string;
    status: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultEmail = `supplier${Math.random().toString(36).substring(2,7)}${timestamp}@example.com`;

  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to suppliers page
  await page.goto('/suppliers');

  // 3️⃣ Open the "Add Supplier" dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();
  
  // Wait for dialog to be visible
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // 4️⃣ Fill the form fields
  const email = overrides.email ?? defaultEmail;
  
  await page.fill('input[name="name"]', overrides.name ?? 'Test Supplier');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', overrides.phone ?? '+628123456789');
  await page.fill('textarea[name="address"]', overrides.address ?? '456 Supplier Ave, City, Country');
  
  // Select branch (AsyncSelectField)
  const branchTrigger = dialog.locator('button').filter({ hasText: /Select a branch/i });
  await branchTrigger.click();
  const branchSearchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  const branchName = overrides.branch ?? 'Head Office';
  if (await branchSearchInput.isVisible()) {
    await branchSearchInput.fill(branchName);
  }
  await page.getByRole('option', { name: branchName }).click();
  
  // Select category (AsyncSelectField)
  const categoryTrigger = dialog.locator('button').filter({ hasText: /Select a category/i });
  await categoryTrigger.click();
  const categorySearchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  const categoryName = overrides.category ?? 'Electronics';
  if (await categorySearchInput.isVisible()) {
    await categorySearchInput.fill(categoryName);
    // Wait for the option to be stable and visible before clicking
    await expect(page.getByRole('option', { name: categoryName })).toBeVisible();
  }
  await page.getByRole('option', { name: categoryName }).click();
  
  // Select status (SelectField)
  const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Active|Inactive/i });
  await statusTrigger.click();
  const status = overrides.status ?? 'Active';
  await page.getByRole('option', { name: status, exact: true }).click();
  
  // 5️⃣ Submit the form - use JS click to bypass viewport issue
  const submitButton = dialog.getByRole('button', { name: /Add/i }).last();
  await expect(submitButton).toBeVisible();
  await submitButton.evaluate((el: HTMLElement) => el.click());
  
  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 10000 });

  return email;
}

/**
 * Search for a supplier by email.
 *
 * @param page - Playwright Page object.
 * @param email - Email address to search for.
 */
export async function searchSupplier(page: Page, email: string): Promise<void> {
  await page.fill('input[placeholder="Search suppliers..."]', email);
  await page.press('input[placeholder="Search suppliers..."]', 'Enter');
  // Wait for the row containing the email to appear
  await page.waitForSelector(`text=${email}`);
}

/**
 * Edit an existing supplier via the UI.
 *
 * @param page - Playwright Page object.
 * @param email - Current supplier email to locate.
 * @param updates - Fields to update.
 */
export async function editSupplier(
  page: Page,
  email: string,
  updates: { name?: string; status?: string }
): Promise<void> {
  // Locate the supplier first
  await searchSupplier(page, email);

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

/**
 * Delete a supplier via the UI.
 *
 * @param page - Playwright Page object.
 * @param email - Email of the supplier to delete.
 */
export async function deleteSupplier(
  page: Page,
  email: string
): Promise<void> {
  // Locate the supplier first
  await searchSupplier(page, email);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: email }).first();
  await expect(row).toBeVisible();
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Delete menu item
  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await expect(deleteItem).toBeVisible();
  await deleteItem.click({ force: true });

  // Confirm delete in dialog
  const deleteDialog = page.getByRole('alertdialog');
  await expect(deleteDialog).toBeVisible();
  
  const confirmDeleteBtn = deleteDialog.getByRole('button', { name: /Delete/i });
  await expect(confirmDeleteBtn).toBeVisible();
  await confirmDeleteBtn.click();
  
  // Verify deletion
  await expect(deleteDialog).not.toBeVisible();
  await expect(row).not.toBeVisible();
}


// ---------------------------------------------------
// Journal Entry helpers
// ---------------------------------------------------

/**
 * Create a new journal entry via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique reference used for the created journal entry.
 */
export async function createJournalEntry(
  page: Page,
  overrides: Partial<{
    entry_date: string; // YYYY-MM-DD
    reference: string;
    description: string;
    lines: Array<{
      account: string;
      debit: string;
      credit: string;
      memo?: string;
    }>
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 10000);
  const defaultRef = `REF-${timestamp}-${random}`;
  const defaultDesc = `Journal Entry ${timestamp}`;

  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to Journal Entries page
  await page.goto('/journal-entries');

  // 3️⃣ Open the "Add Journal Entry" dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // 4️⃣ Fill Header
  const reference = overrides.reference ?? defaultRef;
  const description = overrides.description ?? defaultDesc;

  if (overrides.entry_date) {
    // Handling DatePicker might differ, often it's a button opening a calendar
    // But commonly input[name="entry_date"] might be hidden or read-only if using shadcn Calendar
    // Assuming DatePickerField uses a button to trigger calendar
    // For simplicity, if we can type, we type. If not, we pick via UI.
    // Based on existing helpers, let's try to find the button or input.
    const dateBtn = dialog.locator('button').filter({ hasText: /Pick a date/i }).first();
    if (await dateBtn.isVisible()) {
        await dateBtn.click();
        // Simple day pick for now, or just leave default (today) if not crucial
        // To be robust, maybe skip date picking unless needed, default is today.
    }
  }
  
  await dialog.locator('input[name="reference"]').fill(reference);
  await dialog.locator('input[name="description"]').fill(description);

  // 5️⃣ Fill Lines
  // Default lines are 2 empty lines
  const lines = overrides.lines ?? [
    { account: 'Cash in Banks', debit: '1000', credit: '0' },
    { account: 'Sales Revenue', debit: '0', credit: '1000' }, 
  ];

  // We need to ensure we have enough rows
  // The form starts with 2 rows.
  const addLineBtn = dialog.getByRole('button', { name: /Add Line/i });
  
  for (let i = 0; i < lines.length; i++) {
    // If row doesn't exist, add it
    const rows = dialog.locator('table tbody tr');
    const count = await rows.count();
    if (i >= count) {
        await addLineBtn.click();
    }
    
    const line = lines[i];
    const rowIndex = i;

    // Account (AsyncSelect)
    // The selector is `name="lines.${index}.account_id"` but it's hidden in AsyncSelect
    // trigger is the button inside the cell
    // Account (AsyncSelect)
    const row = rows.nth(rowIndex);
    const accountTrigger = row.locator('button[role="combobox"]');
    await accountTrigger.click();

    const searchInput = page.getByPlaceholder('Search...');
    if (await searchInput.isVisible()) {
        await searchInput.fill(line.account);
        // Wait for listbox to appear
        await page.waitForSelector('[role="listbox"]');
    }
    
    // Select the option by text to ensure we click the right one
    // Try exact match first, then partial
    const option = page.getByRole('option', { name: line.account, exact: true });
    try {
        await expect(option).toBeVisible({ timeout: 5000 });
        await option.click();
    } catch (e) {
        // Fallback to first option if exact match fails (e.g. if 'Cash' finds 'Cash in Banks')
        console.log(`Exact match not found for ${line.account}, selecting first option.`);
        const firstOption = page.getByRole('option').first();
        await expect(firstOption).toBeVisible();
        await firstOption.click();
    }

    // Debit
    await row.locator(`input[name="lines.${rowIndex}.debit"]`).fill(line.debit);

    // Credit
    await row.locator(`input[name="lines.${rowIndex}.credit"]`).fill(line.credit);

    // Memo
    if (line.memo) {
        await row.locator(`input[name="lines.${rowIndex}.memo"]`).fill(line.memo);
    }
  }

  // 6️⃣ Submit
  const saveBtn = dialog.locator('button[type="submit"]');
  await expect(saveBtn).toBeEnabled(); 
  await saveBtn.click();

  // Wait for dialog to close
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
  await page.waitForLoadState('networkidle');

  return reference;
}

/**
 * Search for a journal entry by reference or description.
 */
export async function searchJournalEntry(page: Page, query: string): Promise<void> {
  const searchInput = page.locator('input[placeholder*="Search"]');
  await expect(searchInput).toBeVisible({ timeout: 10000 });
  await searchInput.fill(query);
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');
  // Wait for table to reload - check for spinner or wait for row
  await expect(page.locator(`text=${query}`)).toBeVisible({ timeout: 10000 });
}

/**
 * Edit a journal entry.
 */
export async function editJournalEntry(
    page: Page,
    query: string,
    updates: { description?: string }
): Promise<void> {
    await searchJournalEntry(page, query);

    const row = page.locator('tr', { hasText: query }).first();
    await expect(row).toBeVisible();

    const actionsCell = row.locator('td').last();
    // Edit is the second button (index 1)
    await actionsCell.locator('button').nth(1).click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    if (updates.description) {
        await dialog.locator('input[name="description"]').fill(updates.description);
    }

    const saveBtn = dialog.locator('button[type="submit"]');
    await expect(saveBtn).toBeVisible();
    
    // Check if disabled - if so, we can't save
    if (await saveBtn.isDisabled()) {
         // Check for difference error
         const diffText = await dialog.locator('text=Diff:').textContent();
         console.error(`Save button disabled. Difference detected: ${diffText}`);
         // Fail the test if we can't save due to imbalance (unless we are testing imbalance, but here we assume valid edit)
         throw new Error(`Save button is disabled in editJournalEntry. Diff: ${diffText}`);
    }

    await saveBtn.click();

    // Check for success or error
    try {
        await expect(dialog).not.toBeVisible({ timeout: 15000 });
    } catch (e) {
        // If dialog is still visible, check for error messages
        const errorMsg = await dialog.locator('.text-red-500').allTextContents();
        if (errorMsg.length > 0) {
            throw new Error(`Failed to save journal entry: ${errorMsg.join(', ')}`);
        }
        throw e;
    }
}


// ---------------------------------------------------
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
