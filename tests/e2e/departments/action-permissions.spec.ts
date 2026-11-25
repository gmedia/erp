import { expect, test, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { expectResponseOk, login } from '../helpers';

const VIEWER_EMAIL = 'dept-viewer@example.com';
const FULL_EMAIL = 'dept-full@example.com';
const FIXTURE_PASSWORD = 'password';

// Dibuat ulang tiap run karena global-setup melakukan migrate:fresh + db:seed.
// Hanya menyentuh user fixture modul departments, idempoten.
const ALL_DEPARTMENT_PERMS = [
    'department',
    'department.create',
    'department.edit',
    'department.delete',
];

const FIXTURE_PHP = [
    `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
    `$perms = \\App\\Models\\Permission::whereIn('name', ${JSON.stringify(ALL_DEPARTMENT_PERMS)})`,
    `    ->get()->keyBy('name');`,
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
    `$make('${VIEWER_EMAIL}', 'Dept Viewer', ['department']);`,
    `$make('${FULL_EMAIL}', 'Dept Full', ${JSON.stringify(ALL_DEPARTMENT_PERMS)});`,
].join('\n');

function ensureDepartmentFixtures(): void {
    execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${FIXTURE_PHP}`],
        {
            stdio: 'inherit',
        },
    );
}

async function openDepartments(page: Page): Promise<void> {
    const listResponse = page.waitForResponse(
        (r) =>
            r.url().includes('/api/departments') &&
            r.request().method() === 'GET',
    );
    await page.goto('/departments');
    await expectResponseOk(await listResponse);
}

test.describe('Department action permissions', () => {
    test.beforeAll(() => {
        ensureDepartmentFixtures();
    });

    test('pengguna lihat-saja tidak melihat tombol Add, Edit, Delete', async ({
        page,
    }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await openDepartments(page);

        await expect(
            page.getByPlaceholder('Search departments...'),
        ).toBeVisible();
        await expect(page.getByRole('button', { name: 'Add' })).toHaveCount(0);

        const firstRow = page.locator('tbody tr').first();
        await expect(firstRow).toBeVisible();
        await firstRow.getByRole('button').last().click();

        await expect(
            page.getByRole('menuitem', { name: 'View' }),
        ).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Edit' })).toHaveCount(
            0,
        );
        await expect(
            page.getByRole('menuitem', { name: 'Delete' }),
        ).toHaveCount(0);
    });

    test('pengguna berizin penuh tetap melihat Add, Edit, Delete', async ({
        page,
    }) => {
        await login(page, FULL_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await openDepartments(page);

        await expect(page.getByRole('button', { name: 'Add' })).toBeVisible();

        const firstRow = page.locator('tbody tr').first();
        await expect(firstRow).toBeVisible();
        await firstRow.getByRole('button').last().click();

        await expect(
            page.getByRole('menuitem', { name: 'Edit' }),
        ).toBeVisible();
        await expect(
            page.getByRole('menuitem', { name: 'Delete' }),
        ).toBeVisible();
    });
});
