import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test('user can change their name in profile settings', async ({ page }) => {
    // 1. Log in using existing credentials
    await login(page, undefined, undefined, { requireDashboard: false });

    // 2. Navigate to the profile page
    await page.goto('/settings/profile');

    // 3. Email is read-only and changed only by an administrator
    const emailInput = page.locator('input[name="email"]');
    await expect(emailInput).toBeDisabled();

    // 4. Fill the name input with a new value
    const nameInput = page.locator('input[name="name"]');
    await nameInput.fill('New Admin');

    // 5. Click the save button and wait for the PATCH response
    const saved = page.waitForResponse(
        (res) =>
            res.url().includes('/api/profile') &&
            res.request().method() === 'PATCH',
    );
    await page.click('[data-test="update-profile-button"]');
    expect((await saved).status()).toBe(200);

    // 6. Wait for the success indicator and assert it is visible
    const successIndicator = page.locator('text=Saved');
    await expect(successIndicator).toBeVisible();
});
