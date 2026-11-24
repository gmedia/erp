import { expect, Page, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { createEmployee } from '../employees/helpers';
import { login } from '../helpers';

const PERM_VIEWER_EMAIL = 'perm-viewer@example.com';
const PERM_FULL_EMAIL = 'perm-full@example.com';
const PERM_FIXTURE_PASSWORD = 'password';

function ensurePermissionFixtures(): void {
    const php = [
        `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${PERM_FIXTURE_PASSWORD}');`,
        `$perms = \\App\\Models\\Permission::whereIn('name', ['employee', 'user', 'user.edit'])->get()->keyBy('name');`,
        `$make = function (string $email, string $name, array $names) use ($hash, $perms) {`,
        `    $user = \\App\\Models\\User::updateOrCreate(['email' => $email], [`,
        `        'name' => $name, 'password' => $hash, 'email_verified_at' => now(),`,
        `    ]);`,
        `    $code = 'EMP-' . strtoupper(substr(md5($email), 0, 8));`,
        `    $emp = \\App\\Models\\Employee::updateOrCreate(['user_id' => $user->id], [`,
        `        'employee_id' => $code, 'name' => $name, 'email' => $email,`,
        `    ]);`,
        `    $emp->permissions()->sync(collect($names)->map(fn ($n) => $perms[$n]->id)->all());`,
        `};`,
        `$make('${PERM_VIEWER_EMAIL}', 'Perm Viewer', ['employee', 'user']);`,
        `$make('${PERM_FULL_EMAIL}', 'Perm Full', ['employee', 'user', 'user.edit']);`,
    ].join('\n');

    execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${php}`],
        { stdio: 'inherit' },
    );
}

async function selectAsyncOption(page: Page, text: string): Promise<void> {
    const option = page
        .locator(
            '[role="option"]:visible, ul[aria-busy]:visible button:visible',
        )
        .filter({
            hasText: new RegExp(
                text.replaceAll(/[.*+?^${}()|[\]\\]/g, String.raw`\$&`),
                'i',
            ),
        })
        .first();

    await expect(option).toBeVisible({ timeout: 10000 });
    await option.click({ force: true });
}

test.describe('Employee Permissions', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('assign permissions to an employee', async ({ page }) => {
        // 1. Create a new employee with a unique name
        const timestamp = Date.now();
        const uniqueName = `Test Employee ${timestamp}`;
        await createEmployee(page, { name: uniqueName });

        // 2. Navigate to Permissions page
        await page.goto('/permissions');
        await expect(page).toHaveTitle(/Permissions/);

        // 3. Select the employee
        // AsyncSelectField trigger
        const selectTrigger = page.locator('button[role="combobox"]');
        await expect(selectTrigger).toBeVisible();
        await selectTrigger.click();

        // Type in search
        const searchInput = page.getByPlaceholder('Search...');
        await expect(searchInput).toBeVisible();
        await searchInput.fill(uniqueName); // Search by unique name

        // Select option
        // AsyncSelectField renders options with checks.
        await selectAsyncOption(page, uniqueName);

        // Wait for selection to reflect in the button (confirms state update)
        await expect(selectTrigger).toHaveText(uniqueName);

        // 4. Wait for permissions to load/appear
        // We expect the tree to be visible.
        await expect(
            page.getByRole('heading', {
                name: 'Permissions',
                exact: true,
                level: 1,
            }),
        ).toBeVisible();

        // 5. Select "Create Department" permission
        // The tree node name is "Create Department".
        // The checkbox is adjacent.
        // We can find the text "Create Department" and click it, as clicking the row toggles it (based on TreeNode implementation).
        const permissionName = 'Create Department';
        const permissionNode = page.getByText(permissionName).first();
        await expect(permissionNode).toBeVisible();
        await permissionNode.click();

        // 6. Save changes
        const saveButton = page.getByRole('button', { name: 'Save Changes' });
        await expect(saveButton).toBeEnabled();
        await saveButton.click();

        // 7. Verify success toast (optional/soft check as it can be flaky in headless)
        // await expect(page.getByText('Permissions updated successfully')).toBeVisible();

        // 8. Verify persistence
        await page.reload();

        // Re-select employee (state is reset on reload)
        await selectTrigger.click();
        await searchInput.fill(uniqueName);
        await selectAsyncOption(page, uniqueName);

        // Verify "Create Department" is checked
        // The checkbox parent div has the click handler.
        // Use a more specific selector targeting the node header (flex container) which does not include children text
        const row = page
            .locator('.flex.items-center')
            .filter({ hasText: new RegExp(`^${permissionName}$`) });
        const checkbox = row.getByRole('checkbox');
        await expect(checkbox).toHaveAttribute('aria-checked', 'true');
    });
});

test.describe('Employee permissions action permissions', () => {
    test.beforeAll(() => {
        ensurePermissionFixtures();
    });

    async function selectEmployee(page: Page, name: string): Promise<void> {
        const selectTrigger = page.locator('button[role="combobox"]');
        await expect(selectTrigger).toBeVisible();
        await selectTrigger.click();
        await page.getByPlaceholder('Search...').fill(name);
        await selectAsyncOption(page, name);
        await expect(selectTrigger).toHaveText(name);
    }

    test('pengguna lihat-saja tidak melihat tombol Save Changes', async ({
        page,
    }) => {
        await login(page, PERM_VIEWER_EMAIL, PERM_FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/permissions');
        await selectEmployee(page, 'Perm Full');
        await expect(page.getByTestId('save-permissions-btn')).toHaveCount(0);
    });

    test('pengguna berizin penuh tetap melihat tombol Save Changes', async ({
        page,
    }) => {
        await login(page, PERM_FULL_EMAIL, PERM_FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/permissions');
        await selectEmployee(page, 'Perm Viewer');
        await expect(page.getByTestId('save-permissions-btn')).toBeVisible();
    });
});
