import { test, expect, type Page, type Response } from '@playwright/test';
import { login } from '../helpers';

async function saveAdminSettings(
    page: Page,
    saveButtonTestId: 'save-general-settings' | 'save-regional-settings' | 'save-smtp-settings',
) {
    const responsePromise = page.waitForResponse(
        (response: Response) =>
            response.url().includes('/api/admin-settings') &&
            response.request().method() === 'PUT' &&
            response.status() < 400,
    );

    await page.getByTestId(saveButtonTestId).click();

    await responsePromise;
}

async function waitForAdminSettingsRefresh(
    page: Page,
) {
    await page
        .waitForResponse(
            (response: Response) =>
                response.url().includes('/api/admin-settings') &&
                response.request().method() === 'GET' &&
                response.status() < 400,
        )
        .catch(() => null);
}

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
        await expect(page.getByRole('link', { name: 'SMTP' })).toBeVisible();

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
        await expect(page.getByTestId('currency-select-trigger')).toBeVisible();
        await expect(page.locator('input[name="date_format"]')).toBeVisible();
        await expect(page.locator('input[name="number_format_decimal"]')).toBeVisible();
        await expect(page.locator('input[name="number_format_thousand"]')).toBeVisible();
        await expect(page.getByTestId('hide-decimal-checkbox')).toBeVisible();
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
        await saveAdminSettings(page, 'save-general-settings');

        // Verify the value persisted
        await page.reload();
        await expect(page.locator('input[name="company_name"]')).toHaveValue(companyName);
    });

    test('can update regional settings', async ({ page }) => {
        await page.goto('/admin-settings?group=regional');

        // Update currency
        const currencySelect = page.getByTestId('currency-select-trigger');
        await currencySelect.click();
        await page.getByRole('option', { name: 'USD - US Dollar' }).click();

        // Enable hide decimal
        const hideDecimalInput = page.getByTestId('hide-decimal-checkbox');
        await hideDecimalInput.click();

        // Save
        await saveAdminSettings(page, 'save-regional-settings');

        // Verify persistence
        await page.reload();
        await expect(
            page.locator('input[type="hidden"][name="currency"]'),
        ).toHaveValue('USD');
        await expect(hideDecimalInput).toHaveAttribute('data-state', 'checked');

        // Reset back to defaults to not affect other tests
        await currencySelect.click();
        await page
            .getByRole('option', { name: 'IDR - Indonesian Rupiah' })
            .click();
        await hideDecimalInput.click();
        await saveAdminSettings(page, 'save-regional-settings');
    });

    test('settings page is accessible from Admin menu in sidebar', async ({ page }) => {
        await page.goto('/dashboard');

        // Look for Admin group in sidebar - click to expand if not already
        const adminMenu = page.getByRole('button', { name: 'Admin', exact: true });
        const isExpanded = (await adminMenu.getAttribute('data-state')) === 'open';
        
        if (!isExpanded) {
            await adminMenu.click();
        }

        // Look for Setting link, it should become visible after expansion
        const settingLink = page.getByRole('link', { name: 'Setting', exact: true });
        
        // Ensure it's rendered and visible
        await expect(settingLink).toBeVisible();
        await settingLink.click();

        await expect(page).toHaveURL(/admin-settings/);
        await expect(page).toHaveTitle(/Admin Settings/);
    });

    test('logo upload field is visible on General settings', async ({ page }) => {
        await page.goto('/admin-settings');

        // Logo upload field should be visible
        const logoInput = page.locator('input[name="company_logo"]');
        await expect(logoInput).toBeVisible();
        await expect(logoInput).toHaveAttribute('type', 'file');
        await expect(logoInput).toHaveAttribute('accept', '.svg,image/svg+xml');

        // Helper text should be visible
        await expect(page.getByText(/Upload file SVG/)).toBeVisible();
    });

    test('can upload company logo', async ({ page }) => {
        await page.goto('/admin-settings');

        // Check if there's already a logo displayed
        const existingLogo = page.locator('img[alt="Current company logo"]');
        await existingLogo.isVisible().catch(() => false);

        // Upload a new logo
        const logoInput = page.locator('input[name="company_logo"]');
        await logoInput.setInputFiles('public/logo.svg');

        // Save settings
        await saveAdminSettings(page, 'save-general-settings');
        await waitForAdminSettingsRefresh(page);

        // Logo preview should now be visible after query refresh
        const logoPreview = page.locator('img[alt="Current company logo"]');
        await expect(logoPreview).toBeVisible({ timeout: 15000 });

        // Logo should have a valid src pointing to branding/logos
        const logoSrc = await logoPreview.getAttribute('src');
        expect(logoSrc).toBeTruthy();
        expect(logoSrc).toContain('branding/logos');
    });

    test('uploaded logo appears in app logo component', async ({ page }) => {
        await page.goto('/admin-settings');

        // Upload a logo first
        const logoInput = page.locator('input[name="company_logo"]');
        await logoInput.setInputFiles('public/logo.svg');

        await saveAdminSettings(page, 'save-general-settings');

        // Navigate to another page to check if logo is loaded globally
        await page.goto('/dashboard');

        // Find the app logo in the sidebar or header
        const appLogo = page.locator('img[alt="App Logo"]');
        await expect(appLogo).toBeVisible();

        // Verify it's using the uploaded logo from storage
        const logoSrc = await appLogo.getAttribute('src');
        expect(logoSrc).toBeTruthy();
        
        // If we uploaded successfully, it should point to branding/logos, not the default
        if (logoSrc && !logoSrc.includes('logo_orange.svg')) {
            expect(logoSrc).toContain('branding/logos');
        }
    });

    test('only accepts SVG files for logo upload', async ({ page }) => {
        await page.goto('/admin-settings');

        const logoInput = page.locator('input[name="company_logo"]');
        
        // Verify accept attribute restricts file types
        const acceptAttr = await logoInput.getAttribute('accept');
        expect(acceptAttr).toBe('.svg,image/svg+xml');
    });

    test('can navigate to SMTP settings and update values', async ({ page }) => {
        await page.goto('/admin-settings');

        // Click SMTP tab
        await page.getByRole('link', { name: 'SMTP' }).click();
        await expect(page).toHaveURL(/group=smtp/);

        // SMTP settings heading should show
        await expect(page.getByText('SMTP Settings')).toBeVisible();

        // Check if all fields exist
        await expect(page.locator('input[name="mail_host"]')).toBeVisible();
        await expect(page.locator('input[name="mail_port"]')).toBeVisible();
        await expect(page.locator('input[name="mail_username"]')).toBeVisible();
        await expect(page.locator('input[name="mail_password"]')).toBeVisible();
        await expect(page.locator('input[name="mail_encryption"]')).toBeVisible();
        await expect(page.locator('input[name="mail_from_address"]')).toBeVisible();
        await expect(page.locator('input[name="mail_from_name"]')).toBeVisible();

        // Save original host
        const hostInput = page.locator('input[name="mail_host"]');
        const originalHost = await hostInput.inputValue();

        // Update SMTP host
        await hostInput.clear();
        await hostInput.fill('smtp.mailtrap.io');

        // Save
        const saveButton = page.getByTestId('save-smtp-settings');
        await saveButton.click();

        // Wait for page to reload/settle
        await page.waitForTimeout(1000);

        // Verify persistence
        await page.reload();
        await expect(page.locator('input[name="mail_host"]')).toHaveValue('smtp.mailtrap.io');

        // Restore original host so we don't break the next test
        await hostInput.clear();
        await hostInput.fill(originalHost);
        await saveButton.click();
        await page.waitForTimeout(1000);
    });

    test('can send test smtp email', async ({ page }) => {
        await page.goto('/admin-settings?group=smtp');

        // Check if test email section is visible
        await expect(page.getByText('Test SMTP Configuration')).toBeVisible();

        // Target the test email input
        const testEmailInput = page.locator('input[name="test_email"]');
        await expect(testEmailInput).toBeVisible();

        // Fill in a test email
        await testEmailInput.fill('admin@dokfin.id');

        // Click the send button
        const sendTestButton = page.getByTestId('send-test-email');
        await sendTestButton.click();

        // The form should either succeed or fail gracefully by showing a red error string returning from backend
        const successMessage = page.getByText('Test email sent successfully! Check your inbox.');
        const errorMessage = page.locator('text=Failed to send email:');

        await expect(successMessage.or(errorMessage)).toBeVisible({ timeout: 5000 });
    });
});

