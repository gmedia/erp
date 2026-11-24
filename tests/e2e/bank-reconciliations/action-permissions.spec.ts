import { expect, test, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { login } from '../helpers';

const VIEWER_EMAIL = 'bank-rec-viewer@example.com';
const FULL_EMAIL = 'bank-rec-full@example.com';
const FIXTURE_PASSWORD = 'password';

// Dibuat ulang tiap run karena global-setup melakukan migrate:fresh + db:seed.
// Hanya menyentuh user fixture modul bank_reconciliation, idempoten.
const ALL_BANK_REC_PERMS = [
    'bank_reconciliation',
    'bank_reconciliation.create',
    'bank_reconciliation.edit',
    'bank_reconciliation.delete',
];

const FIXTURE_PHP = [
    `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
    `$perms = \\App\\Models\\Permission::whereIn('name', ${JSON.stringify(ALL_BANK_REC_PERMS)})`,
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
    `$make('${VIEWER_EMAIL}', 'Bank Rec Viewer', ['bank_reconciliation']);`,
    `$make('${FULL_EMAIL}', 'Bank Rec Full', ${JSON.stringify(ALL_BANK_REC_PERMS)});`,
].join('\n');

function ensureBankReconciliationFixtures(): void {
    execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${FIXTURE_PHP}`],
        {
            stdio: 'inherit',
        },
    );
}

async function openBankReconciliations(page: Page): Promise<void> {
    const listResponse = page.waitForResponse(
        (r) =>
            r.url().includes('/api/bank-reconciliations') &&
            r.request().method() === 'GET' &&
            r.status() < 400,
    );
    await page.goto('/bank-reconciliations');
    await listResponse;
}

test.describe('Bank reconciliation action permissions', () => {
    test.beforeAll(() => {
        ensureBankReconciliationFixtures();
    });

    test('pengguna lihat-saja tidak melihat tombol Add, Edit, Delete', async ({
        page,
    }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await openBankReconciliations(page);

        await expect(page.getByRole('button', { name: /Add/i })).toHaveCount(0);

        const firstRow = page.locator('tbody tr').first();
        await expect(firstRow).toBeVisible();
        await firstRow.getByRole('button').last().click();

        await expect(
            page.getByRole('menuitem', { name: /View/i }),
        ).toBeVisible();
        await expect(page.getByRole('menuitem', { name: /Edit/i })).toHaveCount(
            0,
        );
        await expect(
            page.getByRole('menuitem', { name: /Delete/i }),
        ).toHaveCount(0);
    });

    test('pengguna lihat-saja tidak melihat Reconcile dan Import Statement di View', async ({
        page,
    }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await openBankReconciliations(page);

        const firstRow = page
            .locator('tbody tr')
            .filter({ hasNotText: /completed/i })
            .first();
        await expect(firstRow).toBeVisible();
        await firstRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });
        await expect(
            viewDialog.getByRole('button', { name: /Reconcile/i }),
        ).toHaveCount(0);
        await expect(
            viewDialog.getByRole('button', { name: /Import Statement/i }),
        ).toHaveCount(0);
    });

    test('pengguna berizin penuh tetap melihat Add, Edit, Delete', async ({
        page,
    }) => {
        await login(page, FULL_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await openBankReconciliations(page);

        await expect(page.getByRole('button', { name: /Add/i })).toBeVisible();

        const firstRow = page
            .locator('tbody tr')
            .filter({ hasNotText: /completed/i })
            .first();
        await expect(firstRow).toBeVisible();
        await firstRow.getByRole('button').last().click();

        await expect(
            page.getByRole('menuitem', { name: /Edit/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('menuitem', { name: /Delete/i }),
        ).toBeVisible();
    });

    test('pengguna berizin penuh melihat Reconcile dan Import Statement di View', async ({
        page,
    }) => {
        await login(page, FULL_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await openBankReconciliations(page);

        const firstRow = page
            .locator('tbody tr')
            .filter({ hasNotText: /completed/i })
            .first();
        await expect(firstRow).toBeVisible();
        await firstRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });
        await expect(
            viewDialog.getByRole('button', { name: /Reconcile/i }),
        ).toBeVisible();
        await expect(
            viewDialog.getByRole('button', { name: /Import Statement/i }),
        ).toBeVisible();
    });
});
