import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Admin Settings', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('page loads and shows General settings by default', async ({ page }) => {
        await page.goto('/admin-settings');
        await expect(page).toHaveTitle(/Admin Settings/);

        // Sidebar navigation should be visible
        await expect(page.getByRole('link', { name: 'General' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Regional' })).toBeVisible();

        // General settings heading should show
        await expect(page.getByText('General Settings')).toBeVisible();

        // General form fields should be visible
        await expect(page.locator('input[name="company_name"]')).toBeVisible();
        await expect(page.locator('input[name="company_address"]')).toBeVisible();
        await expect(page.locator('input[name="company_phone"]')).toBeVisible();
        await expect(page.locator('input[name="company_email"]')).toBeVisible();
    });

    test('can navigate to Regional settings', async ({ page }) => {
        await page.goto('/admin-settings');

        // Click Regional tab
        await page.getByRole('link', { name: 'Regional' }).click();
        await expect(page).toHaveURL(/group=regional/);

        // Regional settings heading should show
        await expect(page.getByText('Regional Settings')).toBeVisible();

        // Regional form fields should be visible
        await expect(page.locator('input[name="timezone"]')).toBeVisible();
        await expect(page.locator('input[name="currency"]')).toBeVisible();
        await expect(page.locator('input[name="date_format"]')).toBeVisible();
        await expect(page.locator('input[name="number_format_decimal"]')).toBeVisible();
        await expect(page.locator('input[name="number_format_thousand"]')).toBeVisible();
    });

    test('can update general settings', async ({ page }) => {
        await page.goto('/admin-settings');

        const timestamp = Date.now();
        const companyName = `Test Company ${timestamp}`;

        // Fill in company name
        const nameInput = page.locator('input[name="company_name"]');
        await nameInput.clear();
        await nameInput.fill(companyName);

        // Save
        const saveButton = page.getByTestId('save-general-settings');
        await saveButton.click();

        // Wait for page to reload/settle
        await page.waitForTimeout(1000);

        // Verify the value persisted
        await page.reload();
        await expect(page.locator('input[name="company_name"]')).toHaveValue(companyName);
    });

    test('can update regional settings', async ({ page }) => {
        await page.goto('/admin-settings?group=regional');

        // Update currency
        const currencyInput = page.locator('input[name="currency"]');
        await currencyInput.clear();
        await currencyInput.fill('USD');

        // Save
        const saveButton = page.getByTestId('save-regional-settings');
        await saveButton.click();

        // Wait for page to reload/settle
        await page.waitForTimeout(1000);

        // Verify persistence
        await page.reload();
        await expect(page.locator('input[name="currency"]')).toHaveValue('USD');

        // Reset back to IDR to not affect other tests
        await currencyInput.clear();
        await currencyInput.fill('IDR');
        await saveButton.click();
        await page.waitForTimeout(1000);
    });

    test('settings page is accessible from Admin menu in sidebar', async ({ page }) => {
        await page.goto('/dashboard');

        // Look for Admin group in sidebar - click to expand
        const adminMenu = page.locator('button').filter({ hasText: /^Admin$/ });
        if (await adminMenu.isVisible()) {
            await adminMenu.click();
        }

        // Look for Setting link
        const settingLink = page.getByRole('link', { name: 'Setting' });
        await expect(settingLink).toBeVisible();
        await settingLink.click();

        await expect(page).toHaveURL(/admin-settings/);
        await expect(page).toHaveTitle(/Admin Settings/);
    });
});
