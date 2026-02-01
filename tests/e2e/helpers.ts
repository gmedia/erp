import { Page, expect } from '@playwright/test';

// Generic entity creation helper
interface EntityField {
  name: string;
  type: 'text' | 'email' | 'select' | 'textarea';
  selector?: string;
  defaultValue: string;
  optionSelector?: string;
}

interface EntityConfig {
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
async function createEntity(
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
  // If already on the dashboard, no need to log in again
  if (page.url().includes('/dashboard')) {
    await page.waitForLoadState('networkidle');
    return;
  }

  // Navigate to the login page
  await page.goto('/login', { timeout: 100000 });

  // Wait for either a redirect to the dashboard or the email input to become visible
  const dashboardWait = page.waitForURL('**/dashboard', { timeout: 100000 }).catch(() => null);
  const emailWait = page.waitForSelector('input[name="email"]', { state: 'visible', timeout: 100000 }).catch(() => null);
  await Promise.race([dashboardWait, emailWait]);

  // If we have been redirected to the dashboard, finish early
  if (page.url().includes('/dashboard')) {
    await page.waitForLoadState('networkidle');
    return;
  }

  // If the email input is not present, wait for a possible later redirect to the dashboard
  const emailElement = await page.$('input[name="email"]');
  if (!emailElement) {
    await page.waitForURL('**/dashboard', { timeout: 100000 }).catch(() => {});
    return;
  }

  // Perform login steps
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForURL('**/dashboard', { timeout: 1000000 }),
    (async () => {
      const testIdButton = page.locator('button[data-testid="login-button"]');
      if (await testIdButton.count() > 0) {
        await testIdButton.first().click();
      } else {
        await page.click('button[type="submit"]');
      }
    })()
  ]);
  await page.waitForLoadState('domcontentloaded');
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



/**
 * Create a new position via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created position.
 */
export async function createPosition(
  page: Page,
  overrides: Partial<{
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `${Math.random().toString(36).substring(2,7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/positions',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for an position by name.
 *
 * @param page - Playwright Page object.
 * @param name - Name address to search for.
 */
export async function searchPosition(page: Page, name: string): Promise<void> {
  await page.fill('input[placeholder="Search positions..."]', name);
  await page.press('input[placeholder="Search positions..."]', 'Enter');
  // Wait for the row containing the name to appear
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing position.
 *
 * @param page - Playwright Page object.
 * @param name - Name of the position to edit.
 * @param updates - Fields to update (name and/or salary).
 */
export async function editPosition(
  page: Page,
  name: string,
  updates: { name?: string }
): Promise<void> {
  // Locate the position first
  await searchPosition(page, name);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });

  // Click the Edit menu item (updated selector to 'menuitem' for positions)
  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click({ force: true });

  // Update fields if provided
  if (updates.name) {
    await page.fill('input[name="name"]', updates.name);
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
// Department helpers
// ---------------------------------------------------

/**
 * Create a new department via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created department.
 */
export async function createDepartment(
  page: Page,
  overrides: Partial<{
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `${Math.random().toString(36).substring(2, 7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/departments',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a department by name.
 *
 * @param page - Playwright Page object.
 * @param name - Department name to search for.
 */
export async function searchDepartment(
  page: Page,
  name: string
): Promise<void> {
  await page.fill('input[placeholder="Search departments..."]', name);
  await page.press('input[placeholder="Search departments..."]', 'Enter');
  // Wait for the row containing the name to appear
  await page.waitForSelector(`text=${name}`);
}
/**
 * Edit an existing department via the UI.
 *
 * @param page - Playwright Page object.
 * @param name - Current department name to locate.
 * @param updates - Fields to update (currently only name is supported).
 */
export async function editDepartment(
  page: Page,
  name: string,
  updates: { name?: string }
): Promise<void> {
  // Locate the department first
  await searchDepartment(page, name);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: name }).first();
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

  // Submit the edit dialog
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const updateBtn = dialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
}

// ---------------------------------------------------
// Branch helpers
// ---------------------------------------------------

/**
 * Create a new branch via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created branch.
 */
export async function createBranch(
  page: Page,
  overrides: Partial<{
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `${Math.random().toString(36).substring(2, 7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/branches',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a branch by name.
 *
 * @param page - Playwright Page object.
 * @param name - Branch name to search for.
 */
export async function searchBranch(
  page: Page,
  name: string
): Promise<void> {
  await page.fill('input[placeholder="Search branches..."]', name);
  await page.press('input[placeholder="Search branches..."]', 'Enter');
  // Wait for the row containing the name to appear
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing branch via the UI.
 *
 * @param page - Playwright Page object.
 * @param name - Current branch name to locate.
 * @param updates - Fields to update (currently only name is supported).
 */
export async function editBranch(
  page: Page,
  name: string,
  updates: { name?: string }
): Promise<void> {
  // Locate the branch first
  await searchBranch(page, name);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: name }).first();
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

  // Submit the edit dialog
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const updateBtn = dialog.getByRole('button', { name: /Update/ });
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
// Supplier Category helpers
// ---------------------------------------------------

/**
 * Create a new supplier category via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created supplier category.
 */
export async function createSupplierCategory(
  page: Page,
  overrides: Partial<{
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `${Math.random().toString(36).substring(2, 7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/supplier-categories',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a supplier category by name.
 *
 * @param page - Playwright Page object.
 * @param name - Supplier Category name to search for.
 */
export async function searchSupplierCategory(
  page: Page,
  name: string
): Promise<void> {
  await page.fill('input[placeholder="Search supplier categories..."]', name);
  await page.press('input[placeholder="Search supplier categories..."]', 'Enter');
  // Wait for the row containing the name to appear
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing supplier category via the UI.
 *
 * @param page - Playwright Page object.
 * @param name - Current supplier category name to locate.
 * @param updates - Fields to update (currently only name is supported).
 */
export async function editSupplierCategory(
  page: Page,
  name: string,
  updates: { name?: string }
): Promise<void> {
  // Locate the supplier category first
  await searchSupplierCategory(page, name);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: name }).first();
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

  // Submit the edit dialog
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const updateBtn = dialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
}

// ---------------------------------------------------
// Customer Category helpers
// ---------------------------------------------------

/**
 * Create a new customer category via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created customer category.
 */
export async function createCustomerCategory(
  page: Page,
  overrides: Partial<{
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `${Math.random().toString(36).substring(2, 7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/customer-categories',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a customer category by name.
 *
 * @param page - Playwright Page object.
 * @param name - Customer Category name to search for.
 */
export async function searchCustomerCategory(
  page: Page,
  name: string
): Promise<void> {
  await page.fill('input[placeholder="Search customer categories..."]', name);
  await page.press('input[placeholder="Search customer categories..."]', 'Enter');
  // Wait for the row containing the name to appear
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing customer category via the UI.
 *
 * @param page - Playwright Page object.
 * @param name - Current customer category name to locate.
 * @param updates - Fields to update (currently only name is supported).
 */
export async function editCustomerCategory(
  page: Page,
  name: string,
  updates: { name?: string }
): Promise<void> {
  // Locate the customer category first
  await searchCustomerCategory(page, name);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: name }).first();
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

  // Submit the edit dialog
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const updateBtn = dialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
}

// ---------------------------------------------------
// Product Category helpers
// ---------------------------------------------------

/**
 * Create a new product category via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created product category.
 */
export async function createProductCategory(
  page: Page,
  overrides: Partial<{
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `ProdCat ${Math.random().toString(36).substring(2, 7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/product-categories',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
      { name: 'description', type: 'textarea', defaultValue: 'Test Description' },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a product category by name.
 *
 * @param page - Playwright Page object.
 * @param name - Product category name to search for.
 */
export async function searchProductCategory(
  page: Page,
  name: string
): Promise<void> {
  await page.fill('input[placeholder="Search product categories..."]', name);
  await page.press('input[placeholder="Search product categories..."]', 'Enter');
  // Wait for the row containing the name to appear
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing product category via the UI.
 *
 * @param page - Playwright Page object.
 * @param name - Current product category name to locate.
 * @param updates - Fields to update.
 */
export async function editProductCategory(
  page: Page,
  name: string,
  updates: { name?: string; description?: string }
): Promise<void> {
  // Locate the product category first
  await searchProductCategory(page, name);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: name }).first();
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
  if (updates.description) {
    await page.fill('textarea[name="description"]', updates.description);
  }

  // Submit the edit dialog
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const updateBtn = dialog.locator('button[type="submit"]');
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
}

// ---------------------------------------------------
// Unit helpers
// ---------------------------------------------------

/**
 * Create a new unit via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created unit.
 */
export async function createUnit(
  page: Page,
  overrides: Partial<{
    name: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `Unit ${Math.random().toString(36).substring(2, 7)}${timestamp}`;

  const config: EntityConfig = {
    route: '/units',
    returnField: 'name',
    fields: [
      { name: 'name', type: 'text', defaultValue: defaultName },
      { name: 'symbol', type: 'text', defaultValue: 'kg' },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a unit by name.
 *
 * @param page - Playwright Page object.
 * @param name - Unit name to search for.
 */
export async function searchUnit(page: Page, name: string): Promise<void> {
  await page.fill('input[placeholder="Search units..."]', name);
  await page.press('input[placeholder="Search units..."]', 'Enter');
  // Wait for the row containing the name to appear
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing unit via the UI.
 *
 * @param page - Playwright Page object.
 * @param name - Current unit name to locate.
 * @param updates - Fields to update.
 */
export async function editUnit(
  page: Page,
  name: string,
  updates: { name?: string; symbol?: string }
): Promise<void> {
  // Locate the unit first
  await searchUnit(page, name);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: name }).first();
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
  if (updates.symbol) {
    await page.fill('input[name="symbol"]', updates.symbol);
  }

  // Submit the edit dialog
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const updateBtn = dialog.locator('button[type="submit"]');
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
}

// ---------------------------------------------------
// Product helpers
// ---------------------------------------------------

/**
 * Create a new product/service via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique product code used for the created product.
 */
export async function createProduct(
  page: Page,
  overrides: Partial<{
    code: string;
    name: string;
    type: string;
    category_id: string;
    unit_id: string;
    branch_id: string;
    cost: string;
    selling_price: string;
    billing_model: string;
    status: string;
    is_recurring: boolean;
    allow_one_time_purchase: boolean;
    is_manufactured: boolean;
    is_purchasable: boolean;
    is_sellable: boolean;
    is_taxable: boolean;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const productCode = overrides.code ?? `PRD-${timestamp}`;

  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to products page
  await page.goto('/products', { waitUntil: 'networkidle' });

  // 3️⃣ Open the "Add Product" dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // Wait for dialog to be visible
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // 4️⃣ Fill the form fields
  // General Info
  await page.fill('input[name="code"]', productCode);
  await page.fill('input[name="name"]', overrides.name ?? `Product ${timestamp}`);
  // Type Select
  // Type Select
  const typeTrigger = dialog.locator('label', { hasText: /Type/i }).locator('..').getByRole('combobox');
  await typeTrigger.click({ force: true });
  await page.waitForTimeout(1000);
  await page.getByRole('option', { name: overrides.type ?? 'Finished Good', exact: true }).click({ force: true });

  // Category (Async Select)
  const categoryTrigger = dialog.locator('button[role="combobox"]').filter({ hasText: /Select category/i });
  await categoryTrigger.click();
  const catSearch = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  const catName = overrides.category_id ?? 'Electronics';
  if (await catSearch.isVisible()) {
    await catSearch.fill(catName);
    // Wait for options to filter
    await page.waitForTimeout(1000);
  }
  await page.locator('[role="option"]').filter({ hasText: new RegExp(`^${catName}$`) }).first().click({ force: true });

  // Unit (Async Select)
  const unitTrigger = dialog.locator('button[role="combobox"]').filter({ hasText: /Select unit/i });
  await unitTrigger.click();
  const unitSearch = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  const unitName = overrides.unit_id ?? 'Piece';
  if (await unitSearch.isVisible()) {
    await unitSearch.fill(unitName);
    // Wait for options to filter
    await page.waitForTimeout(1000);
  }
  await page.locator('[role="option"]').filter({ hasText: new RegExp(`^${unitName}$`) }).first().click({ force: true });

  // Branch (Async Select)
  if (overrides.branch_id) {
    const branchTrigger = dialog.locator('button[role="combobox"]').filter({ hasText: /Select branch/i });
    await branchTrigger.click();
    const branchSearch = page.getByPlaceholder('Search...').filter({ visible: true }).last();
    if (await branchSearch.isVisible()) {
      await branchSearch.fill(overrides.branch_id);
      // Wait for options to filter
      await page.waitForTimeout(1000);
    }
    await page.locator('[role="option"]').filter({ hasText: new RegExp(`^${overrides.branch_id}$`) }).first().click({ force: true });
  }

  // Pricing
  await page.fill('input[name="cost"]', overrides.cost ?? '1000');
  await page.fill('input[name="selling_price"]', overrides.selling_price ?? '1500');

  // Config
  const billingTrigger = dialog.locator('label', { hasText: /^Billing Model$/i }).locator('..').getByRole('combobox');
  await billingTrigger.waitFor({ state: 'visible' });
  await billingTrigger.click({ force: true });
  await page.getByRole('option', { name: overrides.billing_model ?? 'One Time' }).click();

  // Checkboxes (Flags)
  const flags = [
    { name: 'is_recurring', value: overrides.is_recurring ?? false },
    { name: 'allow_one_time_purchase', value: overrides.allow_one_time_purchase ?? true },
    { name: 'is_manufactured', value: overrides.is_manufactured ?? false },
    { name: 'is_purchasable', value: overrides.is_purchasable ?? true },
    { name: 'is_sellable', value: overrides.is_sellable ?? true },
    { name: 'is_taxable', value: overrides.is_taxable ?? true },
  ];

  for (const flag of flags) {
    const checkbox = dialog.getByRole('checkbox', { name: flag.name });
    if (!await checkbox.isVisible()) {
      // Fallback to name if label not associated correctly, or just use the ID
      const cbById = dialog.locator(`button#${flag.name}`);
      const isChecked = await cbById.getAttribute('aria-checked') === 'true';
      if (isChecked !== flag.value) {
        await cbById.click();
      }
    } else {
      const isChecked = await checkbox.getAttribute('aria-checked') === 'true';
      if (isChecked !== flag.value) {
        await checkbox.click();
      }
    }
  }

  // Metadata
  const statusTrigger = dialog.locator('label', { hasText: /^Status$/ }).locator('..').getByRole('combobox');
  await statusTrigger.waitFor({ state: 'visible' });
  await statusTrigger.click({ force: true });
  await page.getByRole('option', { name: overrides.status ?? 'Active', exact: true }).click();
  // 5️⃣ Submit the form
  const submitButton = dialog.locator('button[type="submit"]');
  await expect(submitButton).toBeVisible();
  await expect(submitButton).toBeEnabled();
  
  // Wait for settlement
  await page.waitForTimeout(1000);
  await submitButton.click();
  
  // Fallback: Press Enter if still visible after 2 seconds
  await page.waitForTimeout(2000);
  if (await dialog.isVisible()) {
    await page.keyboard.press('Enter');
  }

  // Wait for dialog to close
  try {
    await expect(dialog).not.toBeVisible({ timeout: 15000 });
  } catch (error) {
    // If it fails, check for visible error messages to help debugging
    const errorMessages = await dialog.locator('.text-destructive, .text-red-500').allTextContents();
    if (errorMessages.length > 0) {
      throw new Error(`Form submission failed with validation errors: ${errorMessages.join(', ')}`);
    }
    throw error;
  }

  return productCode;
}

/**
 * Search for a product by code or name.
 *
 * @param page - Playwright Page object.
 * @param query - Code or Name to search for.
 */
export async function searchProduct(page: Page, query: string): Promise<void> {
  await page.fill('input[placeholder="Search code, name..."]', query);
  await page.press('input[placeholder="Search code, name..."]', 'Enter');
  // Wait for the row containing the query to appear
  await page.waitForSelector(`text=${query}`);
}

/**
 * Edit an existing product.
 *
 * @param page - Playwright Page object.
 * @param productCode - Code of the product to edit.
 * @param updates - Fields to update.
 */
export async function editProduct(
  page: Page,
  productCode: string,
  updates: { name?: string; selling_price?: string; status?: string }
): Promise<void> {
  // Locate the product first
  await searchProduct(page, productCode);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: productCode }).first();
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
  const dialog = page.getByRole('dialog'); // Define dialog here for use in updates
  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }
  if (updates.selling_price) {
    await dialog.locator('input[name="selling_price"]').fill(updates.selling_price);
  }
  if (updates.status) {
    const statusTrigger = dialog.locator('label', { hasText: /^Status$/ }).locator('..').getByRole('combobox');
    await statusTrigger.waitFor({ state: 'visible' });
    await statusTrigger.click({ force: true });
    await page.waitForTimeout(1000);
    // Be very broad for options as they are in portals
    await page.locator('[role="option"], [role="menuitem"], .select-item').filter({ hasText: updates.status }).first().click({ force: true });
  }

  // Submit the edit dialog
  const updateBtn = dialog.locator('button[type="submit"]');
  await expect(updateBtn).toBeVisible();
  await expect(updateBtn).toBeEnabled();
  
  // Wait for state settlement
  await page.waitForTimeout(1500);
  
  await updateBtn.click();
  
  // Wait for dialog to close
  try {
    await expect(dialog).not.toBeVisible({ timeout: 15000 });
  } catch (error) {
    // Check for validation errors
    const errorMessages = await dialog.locator('.text-destructive, .text-red-500').allTextContents();
    if (errorMessages.length > 0) {
      throw new Error(`Product update failed with validation errors: ${errorMessages.join(', ')}`);
    }
    throw error;
  }
}

/**
 * Delete a product via the UI.
 *
 * @param page - Playwright Page object.
 * @param productCode - Code of the product to delete.
 */
export async function deleteProduct(
  page: Page,
  productCode: string
): Promise<void> {
  // Locate the product first
  await searchProduct(page, productCode);

  // Locate the row and open the Actions menu
  const row = page.locator('tr', { hasText: productCode }).first();
  await expect(row).toBeVisible();
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i }).first();
  await actionsBtn.click({ force: true });

  // Click the Delete menu item
  const deleteBtn = page.getByRole('menuitem', { name: 'Delete' });
  await deleteBtn.click({ force: true });

  // Confirm delete in dialog
  const deleteDialog = page.getByRole('alertdialog');
  await expect(deleteDialog).toBeVisible();

  const confirmDeleteBtn = deleteDialog.getByRole('button', { name: 'Delete' });
  await confirmDeleteBtn.click();

  // Verify deletion
  await expect(deleteDialog).not.toBeVisible();
  // Custom check: wait for row to NOT contain the code
  await page.waitForSelector(`text=${productCode}`, { state: 'detached' });
}

// ---------------------------------------------------
// Fiscal Year helpers
// ---------------------------------------------------

/**
 * Helper to pick a date from the DatePickerField.
 * 
 * @param page - Playwright Page object.
 * @param label - Label of the date picker field.
 * @param day - Day of the month to select (e.g., "15").
 */
export async function pickDate(
  page: Page,
  label: string,
  day: string
): Promise<void> {
  // Find the button by its label. In shadcn/ui typical setup, the button may have the label as its name or be near it.
  const trigger = page.getByRole('button', { name: label, exact: true });
  await trigger.waitFor({ state: 'visible' });
  await trigger.click();
  
  // Wait for the calendar popover to appear.
  // We use data-slot="calendar" which is specific to our Calendar component.
  const calendar = page.locator('[data-slot="calendar"]').last();
  await calendar.waitFor({ state: 'visible', timeout: 15000 });
  await page.waitForTimeout(500); // Small delay for hydration

  // Select the day button inside the calendar.
  const dayButton = calendar.locator('button').filter({ hasText: new RegExp(`^${day}$`) }).first();
  await dayButton.waitFor({ state: 'visible' });
  await dayButton.click({ force: true });
  
  // Sometimes popovers stay open in headless/slow environments. Press Escape to ensure it closes.
  await page.keyboard.press('Escape');
  
  // Wait for it to close
  await expect(calendar).not.toBeVisible();
}

/**
 * Create a new fiscal year via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override.
 * @returns The name used for the created fiscal year.
 */
export async function createFiscalYear(
  page: Page,
  overrides: Partial<{
    name: string;
    start_date_day: string;
    end_date_day: string;
    status: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 10000);
  const name = overrides.name ?? `FY ${timestamp}-${random}`;

  await login(page);
  await page.goto('/fiscal-years');
  await page.waitForLoadState('networkidle');

  const addButton = page.getByRole('button', { name: /Add/i });
  await addButton.waitFor({ state: 'visible' });
  await addButton.click();

  const dialog = page.getByRole('dialog', { name: /Add New Fiscal Year/i });
  await expect(dialog).toBeVisible();

  await dialog.locator('input[name="name"]').fill(name);

  // Pick start date
  await pickDate(page, 'Start Date', overrides.start_date_day ?? '10');
  
  // Pick end date
  await pickDate(page, 'End Date', overrides.end_date_day ?? '20');

  if (overrides.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Open|Closed|Locked/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: overrides.status, exact: true }).click();
  }

  const submitButton = dialog.getByRole('button', { name: /Add/i }).last();
  await submitButton.click();

  await expect(dialog).not.toBeVisible();

  return name;
}

/**
 * Search for a fiscal year by name.
 */
export async function searchFiscalYear(page: Page, name: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search fiscal years...');
  await searchInput.waitFor({ state: 'visible' });
  await searchInput.fill(name);
  await searchInput.press('Enter');
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing fiscal year.
 */
export async function editFiscalYear(
  page: Page,
  name: string,
  updates: { name?: string; status?: string }
): Promise<void> {
  await searchFiscalYear(page, name);

  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await editItem.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }

  if (updates.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Open|Closed|Locked/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: updates.status, exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await updateBtn.click();

  await expect(dialog).not.toBeVisible();
}

/**
 * Create a new COA version via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created COA version.
 */
export async function createCoaVersion(
  page: Page,
  overrides: Partial<{
    name: string;
    fiscal_year_id: string; // This should be the label/name of fiscal year
    status: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `COA Version ${timestamp}`;

  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to COA versions page
  await page.goto('/coa-versions');

  // 3️⃣ Open the "Add COA Version" dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // Wait for dialog to be visible
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // 4️⃣ Fill the form fields
  const name = overrides.name ?? defaultName;
  await dialog.locator('input[name="name"]').fill(name);

  // Select fiscal year (Regular SelectField)
  const fiscalYearTrigger = dialog.locator('button').filter({ hasText: /Select fiscal year/i });
  await expect(fiscalYearTrigger).toBeVisible();
  await fiscalYearTrigger.click();
  
  const fiscalYearLabel = overrides.fiscal_year_id ?? '2025';
  await page.getByRole('option', { name: fiscalYearLabel }).first().click();

  // Select status (Regular SelectField)
  if (overrides.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Draft|Active|Archived/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: overrides.status, exact: true }).click();
  }

  // 5️⃣ Submit the form
  const submitButton = dialog.getByRole('button', { name: /Create/i }).last();
  await submitButton.click();

  // Wait for dialog to be hidden (means successful submission)
  await expect(dialog).not.toBeVisible({ timeout: 10000 });

  return name;
}

/**
 * Search for a COA version by name.
 */
export async function searchCoaVersion(page: Page, name: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search COA versions...');
  await searchInput.waitFor({ state: 'visible' });
  await searchInput.fill(name);
  await searchInput.press('Enter');
  await page.waitForSelector(`text=${name}`);
}

/**
 * Edit an existing COA version.
 */
export async function editCoaVersion(
  page: Page,
  name: string,
  updates: { name?: string; status?: string }
): Promise<void> {
  await searchCoaVersion(page, name);

  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await editItem.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }

  if (updates.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Draft|Active|Archived/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: updates.status, exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await updateBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
