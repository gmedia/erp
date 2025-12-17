import { Page, expect } from '@playwright/test';

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
  await page.goto('/login');

  // Wait for either a redirect to the dashboard or the email input to become visible
  const dashboardWait = page.waitForURL('**/dashboard', { timeout: 60000 }).catch(() => null);
  const emailWait = page.waitForSelector('input[name="email"]', { state: 'visible', timeout: 10000 }).catch(() => null);
  await Promise.race([dashboardWait, emailWait]);

  // If we have been redirected to the dashboard, finish early
  if (page.url().includes('/dashboard')) {
    await page.waitForLoadState('networkidle');
    return;
  }

  // If the email input is not present, wait for a possible later redirect to the dashboard
  const emailElement = await page.$('input[name="email"]');
  if (!emailElement) {
    await page.waitForURL('**/dashboard', { timeout: 60000 }).catch(() => {});
    return;
  }

  // Perform login steps
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForURL('**/dashboard', { timeout: 60000 }),
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
  overrides?: Partial<{
    name: string;
    email: string;
    phone: string;
    salary: string;
    department: string;
    position: string;
  }>
): Promise<string> {
  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to employee list page
  await page.goto('/employees');

  // 3️⃣ Open the “Add Employee” dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // 4️⃣ Fill the form fields (use defaults, allow overrides)
  const timestamp = Date.now();
// Updated email generation to ensure uniqueness without fixed prefix
  const defaultEmail = `${Math.random().toString(36).substring(2,7)}${timestamp}@example.com`;
  const email = overrides?.email ?? defaultEmail;

  await page.fill('input[name="name"]', overrides?.name ?? 'John Doe');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', overrides?.phone ?? '+628123456789');
  await page.fill('input[name="salary"]', overrides?.salary ?? '5000');

  // Department select
  await page.click('button:has-text("Select a department")');
  await page
    .getByRole('option', { name: overrides?.department ?? 'Engineering' })
    .click();

  // Position select
  await page.click('button:has-text("Select a position")');
  await page
    .getByRole('option', { name: overrides?.position ?? 'Senior', exact: true })
    .click();

  // Ensure any modal overlay/backdrop is removed before submitting
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const submitButton = dialog.getByRole('button', { name: /Add/ });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  // Return the email used for later lookup
  return email;
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
 * Create a new employee via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique name used for the created employee.
 */
export async function createPosition(
  page: Page,
  overrides?: Partial<{
    name: string;
  }>
): Promise<string> {
  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to position list page
  await page.goto('/positions');

  // 3️⃣ Open the “Add Position” dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // 4️⃣ Fill the form fields (use defaults, allow overrides)
  const timestamp = Date.now();
// Updated name generation to ensure uniqueness without fixed prefix
  const defaultName = `${Math.random().toString(36).substring(2,7)}${timestamp}`;
  const name = overrides?.name ?? defaultName;

  await page.fill('input[name="name"]', name);

  // Ensure any modal overlay/backdrop is removed before submitting
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const submitButton = dialog.getByRole('button', { name: /Add/ });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  // Return the name used for later lookup
  return name;
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
  overrides?: Partial<{
    name: string;
  }>
): Promise<string> {
  // 1️⃣ Login
  await login(page);

  // 2️⃣ Navigate to department list page
  await page.goto('/departments');

  // 3️⃣ Open the “Add Department” dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // 4️⃣ Fill the form fields (use defaults, allow overrides)
  const timestamp = Date.now();
  // Updated name generation to ensure uniqueness without fixed prefix
  const defaultName = `${Math.random()
    .toString(36)
    .substring(2, 7)}${timestamp}`;
  const name = overrides?.name ?? defaultName;

  await page.fill('input[name="name"]', name);

  // Ensure any modal overlay/backdrop is removed before submitting
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', {
    state: 'detached',
  });
  const dialog = page.getByRole('dialog');
  const submitButton = dialog.getByRole('button', { name: /Add/ });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  // Return the name used for later lookup
  return name;
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
