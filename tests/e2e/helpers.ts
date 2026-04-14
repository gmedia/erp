import { Page, expect } from '@playwright/test';

type ApiResponseMatcher = string | RegExp | ((url: string) => boolean);

function matchesApiUrl(url: string, matcher: ApiResponseMatcher): boolean {
  if (typeof matcher === 'string') {
    return url.includes(matcher);
  }

  if (matcher instanceof RegExp) {
    return matcher.test(url);
  }

  return matcher(url);
}

export async function waitForApiAfterAction(
  page: Page,
  matcher: ApiResponseMatcher,
  action: () => Promise<unknown>,
  timeout = 10000,
): Promise<void> {
  const responsePromise = page
    .waitForResponse(
      (response) =>
        matchesApiUrl(response.url(), matcher) && response.status() < 400,
      { timeout },
    )
    .catch(() => null);

  await action();
  await responsePromise;
}

export async function reloadAndWaitForApi(
  page: Page,
  matcher: ApiResponseMatcher,
  timeout = 10000,
): Promise<void> {
  await waitForApiAfterAction(page, matcher, () => page.reload(), timeout);
}

export async function searchAndWaitForApi(
  page: Page,
  searchInput: ReturnType<Page['getByPlaceholder']>,
  value: string,
  matcher: ApiResponseMatcher,
  timeout = 10000,
): Promise<void> {
  await waitForApiAfterAction(
    page,
    matcher,
    async () => {
      await searchInput.fill(value);
      await searchInput.press('Enter');
    },
    timeout,
  );
}

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
  try {
    await expect(addButton).toBeVisible({ timeout: 5000 });
  } catch (error) {
    console.error("Add button hidden. Page content:");
    console.error(await page.textContent('body'));
    await page.screenshot({ path: 'tests/e2e/test-results/debug-add-button.png' });
    throw error;
  }
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

      // Support both legacy listbox/option and current AsyncSelect popover list.
      const listbox = page.locator('[role="listbox"]:visible, ul[aria-busy]:visible').last();
      await expect(listbox).toBeVisible();

      // Search if input is available
      const searchInput = page.locator('input[placeholder="Search..."]:visible').last();
      if (await searchInput.isVisible().catch(() => false)) {
        await searchInput.fill(value);
        // Wait for debounce and fetch
        await page.waitForTimeout(500);
      }

      // Click the option
      const option = page
        .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
        .filter({ hasText: new RegExp(value, 'i') })
        .first();
      await expect(option).toBeVisible();
      await option.click({ force: true });

      // Wait for listbox to disappear
      await expect(page.locator('[role="listbox"]:visible, ul[aria-busy]:visible')).toHaveCount(0, { timeout: 10000 }).catch(() => null);
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
 * @param email - User email address. Defaults to 'admin@dokfin.id'.
 * @param password - User password. Defaults to 'password'.
 *
 * @returns A promise that resolves when navigation to the dashboard is complete.
 */
export async function login(
  page: Page,
  email = 'admin@dokfin.id',
  password = 'password'
): Promise<void> {
  // Listen for uncaught JS errors
  page.on('pageerror', exception => {
    console.error(`Uncaught exception: "${exception}"`);
  });
  
  // Listen for console errors
  page.on('console', msg => {
    if (msg.type() === 'error') {
      const text = msg.text();
      // Ignore known expected errors to keep test output clean
      if (text.includes('Failed to send logs: TypeError: Failed to fetch')) return; // Laravel Boost logger
      if (text.includes('the server responded with a status of 422')) return; // Expected validation error
      if (text.includes('AxiosError')) return; // Expected validation error
      if (text.includes('Download the React DevTools')) return;

      console.error(`Console Error text: "${text}"`);
    }
  });

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

  const clearStoredAuth = async (): Promise<void> => {
    await page.context().clearCookies();
    await page.evaluate(() => {
      localStorage.removeItem('api_token');
      sessionStorage.clear();
    });
  };

  // Auth in this SPA is driven by a bearer token in localStorage, not cookies.
  // Reset the client auth state before every login so stale tokens do not redirect
  // us away from the login page or keep us logged in as the wrong user.
  await gotoWithRetry('/login');
  await clearStoredAuth();
  await gotoWithRetry('/login');

  const emailInput = page.locator('input[name="email"]');
  await page.evaluate(() => {
    document.querySelector('vite-error-overlay')?.remove();
  });
  await expect(emailInput).toBeVisible({ timeout: 15000 });
  await emailInput.fill(email);
  await page.fill('input[name="password"]', password);

  const loginResponse = page.waitForResponse(
    response =>
      response.url().includes('/api/login') && response.request().method() === 'POST',
    { timeout: 60000 },
  ).catch(() => null);

  await page
    .locator(
      'button[type="submit"], button[data-test="login-button"], button[data-testid="login-button"]',
    )
    .first()
    .click({ force: true });

  await loginResponse;
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





