import { test, expect, Page } from '@playwright/test';
import { login } from '../helpers';
import { createEmployee } from '../employees/helpers';

function createUserAccessCode(seed: number): string {
    return `User-${seed}-Aa1!`;
}

async function selectAsyncOption(page: Page, text: string): Promise<void> {
    const option = page
        .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
        .filter({ hasText: new RegExp(text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i') })
        .first();

    await expect(option).toBeVisible({ timeout: 10000 });
    await option.click({ force: true });
}

test.describe('User Management', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('create and update user for an employee', async ({ page }) => {
        // 1. Create a new employee with a unique name
        const timestamp = Date.now();
        const uniqueName = `Test User Employee ${timestamp}`;
        await createEmployee(page, { name: uniqueName });
        
        // 2. Navigate to Users page
        await page.goto('/users');
        await expect(page).toHaveTitle(/Users/);

        // 3. Select the employee using AsyncSelectField
        const selectTrigger = page.locator('button[role="combobox"]');
        await expect(selectTrigger).toBeVisible();
        await selectTrigger.click();

        // Type in search
        const searchInput = page.getByPlaceholder('Search...');
        await expect(searchInput).toBeVisible();
        await searchInput.fill(uniqueName);

        // Select option
        await selectAsyncOption(page, uniqueName);
        
        // Wait for selection to reflect in the button
        await expect(selectTrigger).toHaveText(uniqueName);

        // 4. Verify form fields are auto-populated
        await expect(page.locator('input#name')).toBeVisible();
        await expect(page.locator('input#email')).toBeVisible();
        await expect(page.locator('input#password')).toBeVisible();

        // 5. Fill in user details
        const newUserName = `User ${timestamp}`;
        const newUserEmail = `user${timestamp}@test.com`;
        const accessCode = createUserAccessCode(timestamp);

        await page.fill('input#name', newUserName);
        await page.fill('input#email', newUserEmail);
        await page.fill('input#password', accessCode);

        // 6. Save changes
        const saveButton = page.getByRole('button', { name: 'Save Changes' });
        await expect(saveButton).toBeEnabled();
        await saveButton.click();

        // 7. Wait for save to complete by waiting for button to be re-enabled (loading ends)
        // and password placeholder to change (indicates userExists changed to true)
        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder', 
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        // 8. Verify persistence by reloading and re-selecting
        await page.reload();
        
        // Re-select employee
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await selectAsyncOption(page, uniqueName);

        // Verify saved data is displayed
        await expect(page.locator('input#name')).toHaveValue(newUserName);
        await expect(page.locator('input#email')).toHaveValue(newUserEmail);
        // Password field should be empty (for security)
        await expect(page.locator('input#password')).toHaveValue('');
    });

    test('update existing user details', async ({ page }) => {
        // 1. Create a new employee and user first
        const timestamp = Date.now();
        const uniqueName = `Existing User Employee ${timestamp}`;
        await createEmployee(page, { name: uniqueName });
        
        // 2. Navigate to Users page and create user
        await page.goto('/users');
        
        const selectTrigger = page.locator('button[role="combobox"]');
        await selectTrigger.click();
        
        const searchInput = page.getByPlaceholder('Search...');
        await searchInput.fill(uniqueName);
        
        await selectAsyncOption(page, uniqueName);

        // Create user initially
        const initialName = `Initial User ${timestamp}`;
        const initialEmail = `initial${timestamp}@test.com`;
        const accessCode = createUserAccessCode(timestamp);
        
        await page.fill('input#name', initialName);
        await page.fill('input#email', initialEmail);
        await page.fill('input#password', accessCode);
        
        await page.getByRole('button', { name: 'Save Changes' }).click();
        
        // Wait for save to complete
        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder', 
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        // 3. Reload and update user
        await page.reload();
        
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await selectAsyncOption(page, uniqueName);

        // Wait for form to load with existing data
        await expect(page.locator('input#name')).toHaveValue(initialName);

        // Update user details
        const updatedName = `Updated User ${timestamp}`;
        const updatedEmail = `updated${timestamp}@test.com`;
        
        await page.fill('input#name', updatedName);
        await page.fill('input#email', updatedEmail);
        // Leave password empty to keep existing
        
        await page.getByRole('button', { name: 'Save Changes' }).click();
        
        // Wait for save to complete (button re-enabled)
        await expect(page.getByRole('button', { name: 'Save Changes' })).toBeEnabled({ timeout: 10000 });
        
        // Brief wait for state to settle
        await page.waitForTimeout(500);

        // 4. Verify update persisted
        await page.reload();
        
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await selectAsyncOption(page, uniqueName);

        await expect(page.locator('input#name')).toHaveValue(updatedName);
        await expect(page.locator('input#email')).toHaveValue(updatedEmail);
    });

    test('shows validation error for duplicate email', async ({ page }) => {
        // 1. Create two employees
        const timestamp = Date.now();
        const employee1Name = `Duplicate Test 1 ${timestamp}`;
        const employee2Name = `Duplicate Test 2 ${timestamp}`;
        
        await createEmployee(page, { name: employee1Name });
        await createEmployee(page, { name: employee2Name });
        
        // 2. Navigate to Users page and create user for employee 1
        await page.goto('/users');
        
        const selectTrigger = page.locator('button[role="combobox"]');
        await selectTrigger.click();
        
        const searchInput = page.getByPlaceholder('Search...');
        await searchInput.fill(employee1Name);
        
        await selectAsyncOption(page, employee1Name);

        const sharedEmail = `shared${timestamp}@test.com`;
        const accessCode = createUserAccessCode(timestamp);
        
        await page.fill('input#name', `User 1 ${timestamp}`);
        await page.fill('input#email', sharedEmail);
        await page.fill('input#password', accessCode);
        
        await page.getByRole('button', { name: 'Save Changes' }).click();
        
        // Wait for save to complete
        await expect(page.locator('input#password')).toHaveAttribute(
            'placeholder', 
            'Leave empty to keep current password',
            { timeout: 10000 }
        );

        // 3. Try to use the same email for employee 2
        await selectTrigger.click();
        await searchInput.fill(employee2Name);
        
        await selectAsyncOption(page, employee2Name);

        await page.fill('input#name', `User 2 ${timestamp}`);
        await page.fill('input#email', sharedEmail); // Same email as user 1
        await page.fill('input#password', accessCode);
        
        await page.getByRole('button', { name: 'Save Changes' }).click();

        // 4. Verify validation error is shown
        await expect(page.getByText('The email has already been taken')).toBeVisible({ timeout: 10000 });
    });

    test('shows password required error for new user', async ({ page }) => {
        // 1. Create a new employee
        const timestamp = Date.now();
        const uniqueName = `Password Required ${timestamp}`;
        await createEmployee(page, { name: uniqueName });
        
        // 2. Navigate to Users page
        await page.goto('/users');
        
        const selectTrigger = page.locator('button[role="combobox"]');
        await selectTrigger.click();
        
        const searchInput = page.getByPlaceholder('Search...');
        await searchInput.fill(uniqueName);
        
        await selectAsyncOption(page, uniqueName);

        // 3. Try to save without password
        await page.fill('input#name', `User ${timestamp}`);
        await page.fill('input#email', `user${timestamp}@test.com`);
        // Leave password empty
        
        await page.getByRole('button', { name: 'Save Changes' }).click();

        // 4. Verify validation error is shown
        await expect(page.getByText('The password field is required')).toBeVisible({ timeout: 10000 });
    });
});

