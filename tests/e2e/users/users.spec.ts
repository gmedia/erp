import { test, expect, Page } from '@playwright/test';
import { login } from '../helpers';
import { createEmployee } from '../employees/helpers';

function createUserAccessCode(seed: number): string {
    return `User-${seed}-Aa1!`;
}

async function selectAsyncOption(page: Page, text: string): Promise<void> {
    const option = page
        .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
        .filter({ hasText: new RegExp(text.replaceAll(/[.*+?^${}()|[\]\\]/g, String.raw`\$&`), 'i') })
        .first();

    await expect(option).toBeVisible({ timeout: 10000 });
    await option.click({ force: true });
}

async function openUsersPage(page: Page): Promise<void> {
    await page.goto('/users');
    await expect(page).toHaveTitle(/Users/);
    await expect(page.getByRole('heading', { name: 'Users' })).toBeVisible();
}

async function searchUsers(page: Page, text: string): Promise<void> {
    await page.getByPlaceholder('Search users...').fill(text);
    await expect(page.getByTestId('user-row').filter({ hasText: text }).first()).toBeVisible({
        timeout: 10000,
    });
}

async function openCreateDialog(page: Page): Promise<void> {
    await page.getByRole('button', { name: 'Add User' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
}

async function chooseEmployeeInDialog(page: Page, employeeName: string): Promise<void> {
    const selectTrigger = page.getByRole('dialog').locator('button[role="combobox"]');
    await expect(selectTrigger).toBeVisible();
    await selectTrigger.click();

    await page.getByPlaceholder('Search...').fill(employeeName);
    await selectAsyncOption(page, employeeName);
    await expect(selectTrigger).toHaveText(employeeName);
}

async function openEditForUser(page: Page, userName: string): Promise<void> {
    await searchUsers(page, userName);
    const row = page.getByTestId('user-row').filter({ hasText: userName }).first();
    await row.getByRole('button', { name: 'Edit User' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
}

test.describe('User Management', () => {
    test.describe.configure({ retries: 1 });

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('create and update user for an employee', async ({ page }) => {
        const timestamp = Date.now();
        const uniqueName = `Test User Employee ${timestamp}`;
        await createEmployee(page, { name: uniqueName });

        await openUsersPage(page);
        await openCreateDialog(page);
        await chooseEmployeeInDialog(page, uniqueName);

        await expect(page.locator('input#name')).toBeVisible();
        await expect(page.locator('input#email')).toBeVisible();
        await expect(page.locator('input#password')).toBeVisible();

        const newUserName = `User ${timestamp}`;
        const newUserEmail = `user${timestamp}@test.com`;
        const accessCode = createUserAccessCode(timestamp);

        await page.fill('input#name', newUserName);
        await page.fill('input#email', newUserEmail);
        await page.fill('input#password', accessCode);

        const saveButton = page.getByRole('button', { name: 'Save Changes' });
        await expect(saveButton).toBeEnabled();
        await saveButton.click();

        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder',
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        await page.reload();
        await openEditForUser(page, newUserName);

        await expect(page.locator('input#name')).toHaveValue(newUserName);
        await expect(page.locator('input#email')).toHaveValue(newUserEmail);
        await expect(page.locator('input#password')).toHaveValue('');
    });

    test('update existing user details', async ({ page }) => {
        const timestamp = Date.now();
        const uniqueName = `Existing User Employee ${timestamp}`;
        await createEmployee(page, { name: uniqueName });

        await openUsersPage(page);
        await openCreateDialog(page);
        await chooseEmployeeInDialog(page, uniqueName);

        const initialName = `Initial User ${timestamp}`;
        const initialEmail = `initial${timestamp}@test.com`;
        const accessCode = createUserAccessCode(timestamp);

        await page.fill('input#name', initialName);
        await page.fill('input#email', initialEmail);
        await page.fill('input#password', accessCode);

        await page.getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder',
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        await page.reload();
        await openEditForUser(page, initialName);

        await expect(page.locator('input#name')).toHaveValue(initialName);

        const updatedName = `Updated User ${timestamp}`;
        const updatedEmail = `updated${timestamp}@test.com`;

        await page.fill('input#name', updatedName);
        await page.fill('input#email', updatedEmail);

        await page.getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.getByRole('button', { name: 'Save Changes' })).toBeEnabled({ timeout: 10000 });
        await page.waitForTimeout(500);

        await page.reload();
        await openEditForUser(page, updatedName);

        await expect(page.locator('input#name')).toHaveValue(updatedName);
        await expect(page.locator('input#email')).toHaveValue(updatedEmail);
    });

    test('shows validation error for duplicate email', async ({ page }) => {
        const timestamp = Date.now();
        const employee1Name = `Duplicate Test 1 ${timestamp}`;
        const employee2Name = `Duplicate Test 2 ${timestamp}`;

        await createEmployee(page, { name: employee1Name });
        await createEmployee(page, { name: employee2Name });

        await openUsersPage(page);
        await openCreateDialog(page);
        await chooseEmployeeInDialog(page, employee1Name);

        const sharedEmail = `shared${timestamp}@test.com`;
        const accessCode = createUserAccessCode(timestamp);

        await page.fill('input#name', `User 1 ${timestamp}`);
        await page.fill('input#email', sharedEmail);
        await page.fill('input#password', accessCode);

        await page.getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder',
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        await page.keyboard.press('Escape');
        await openCreateDialog(page);
        await chooseEmployeeInDialog(page, employee2Name);

        await page.fill('input#name', `User 2 ${timestamp}`);
        await page.fill('input#email', sharedEmail);
        await page.fill('input#password', accessCode);

        await page.getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.getByText('The email has already been taken')).toBeVisible({ timeout: 10000 });
    });

    test('shows password required error for new user', async ({ page }) => {
        const timestamp = Date.now();
        const uniqueName = `Password Required ${timestamp}`;
        await createEmployee(page, { name: uniqueName });

        await openUsersPage(page);
        await openCreateDialog(page);
        await chooseEmployeeInDialog(page, uniqueName);

        await page.fill('input#name', `User ${timestamp}`);
        await page.fill('input#email', `user${timestamp}@test.com`);

        await page.getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.getByText('The password field is required')).toBeVisible({ timeout: 10000 });
    });
});
