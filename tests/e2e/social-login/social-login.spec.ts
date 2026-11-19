import { expect, test } from '@playwright/test';

test.use({ storageState: { cookies: [], origins: [] } });

const POLICY = {
  socialLoginEnabled: true,
  passwordLoginEnabled: true,
  personalAccountsEnabled: false,
  providers: ['google', 'microsoft'],
};

async function stubPolicy(page: import('@playwright/test').Page, overrides = {}) {
  await page.route('**/api/social/policy', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ ...POLICY, ...overrides }),
    }),
  );
}

test('login page shows social buttons and hides password form when disabled', async ({
  page,
}) => {
  await stubPolicy(page, { passwordLoginEnabled: false });

  await page.goto('/login');

  await expect(page.getByTestId('social-login-google')).toBeVisible();
  await expect(page.getByTestId('social-login-microsoft')).toBeVisible();
  await expect(page.getByTestId('login-button')).toHaveCount(0);
});

test('login page hides social buttons when social login is disabled', async ({
  page,
}) => {
  await stubPolicy(page, { socialLoginEnabled: false });

  await page.goto('/login');

  await expect(page.getByTestId('social-login-google')).toHaveCount(0);
  await expect(page.getByTestId('login-button')).toBeVisible();
});

test('social redirect endpoint is called with the selected provider', async ({
  page,
}) => {
  await stubPolicy(page);
  let requested = '';
  await page.route('**/api/social/google/redirect', (route) => {
    requested = route.request().url();
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ url: '/login', state: 'test-state' }),
    });
  });

  await page.goto('/login');
  await page.getByTestId('social-login-google').click();

  expect(requested).toContain('/api/social/google/redirect');
});

test('callback exchanges ticket and lands on dashboard', async ({ page }) => {
  await stubPolicy(page);
  await page.route('**/api/social/exchange', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ token: 'test-token', user: { id: 1, name: 'User' } }),
    }),
  );
  await page.route('**/api/me', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ user: { id: 1, name: 'User' }, menus: [] }),
    }),
  );

  await page.goto('/auth/social/callback#ticket=fake-ticket');

  await expect(page).toHaveURL(/\/dashboard/);
  await expect
    .poll(() => page.evaluate(() => localStorage.getItem('api_token')))
    .toBe('test-token');
});

test('callback shows an actionable message for domain_not_allowed', async ({
  page,
}) => {
  await stubPolicy(page);

  await page.goto('/auth/social/callback#error=domain_not_allowed');

  await expect(page.getByText(/domain is not allowed/i)).toBeVisible();
  await expect(page.getByTestId('social-callback-back')).toBeVisible();
});

test('callback shows account_not_registered message', async ({ page }) => {
  await stubPolicy(page);

  await page.goto('/auth/social/callback#error=account_not_registered');

  await expect(page.getByText(/not registered/i)).toBeVisible();
});
