import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { login } from '../helpers';

const EDITOR_EMAIL = 'budget-editor@example.com';
const VIEWER_EMAIL = 'budget-viewer@example.com';
const FIXTURE_PASSWORD = 'password';
const FIXTURE_NAME = 'Budget Action Fixture';

const FIXTURE_PHP = [
    `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
    `$names = ['budget', 'budget.edit'];`,
    `$perms = \\App\\Models\\Permission::whereIn('name', $names)->get()->keyBy('name');`,
    `$make = function (string $email, string $name, array $codes) use ($hash, $perms) {`,
    `    $user = \\App\\Models\\User::updateOrCreate(['email' => $email], [`,
    `        'name' => $name, 'password' => $hash, 'email_verified_at' => now(),`,
    `    ]);`,
    `    $code = 'EMP-' . strtoupper(substr(md5($email), 0, 8));`,
    `    $emp = \\App\\Models\\Employee::updateOrCreate(['user_id' => $user->id], [`,
    `        'employee_id' => $code, 'name' => $name, 'email' => $email,`,
    `    ]);`,
    `    $emp->permissions()->sync(collect($codes)->map(fn ($n) => $perms[$n]->id)->all());`,
    `};`,
    `$make('${VIEWER_EMAIL}', 'Budget Viewer', ['budget']);`,
    `$make('${EDITOR_EMAIL}', 'Budget Editor', ['budget', 'budget.edit']);`,
    `$fy = \\App\\Models\\FiscalYear::first();`,
    `$creator = \\App\\Models\\User::where('email', '${EDITOR_EMAIL}')->first();`,
    `\\App\\Models\\Budget::updateOrCreate(`,
    `    ['name' => '${FIXTURE_NAME}'],`,
    `    ['ulid' => \\Illuminate\\Support\\Str::ulid()->toBase32(),`,
    `     'fiscal_year_id' => $fy->id, 'budget_type' => 'operational',`,
    `     'description' => null, 'status' => 'draft', 'total_amount' => 0,`,
    `     'approved_by' => null, 'approved_at' => null, 'created_by' => $creator->id],`,
    `);`,
].join('\n');

function runTinker(php: string): string {
    return execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${php}`],
        { encoding: 'utf8' },
    );
}

function resetFixtureBudget(status: 'draft' | 'approved'): void {
    const approvedAt = status === 'approved' ? 'now()' : 'null';
    runTinker(
        `\\App\\Models\\Budget::where('name', '${FIXTURE_NAME}')->update([` +
            `'status' => '${status}', 'approved_at' => ${approvedAt},` +
            ` 'approved_by' => null]);`,
    );
}

async function openFixtureRow(page: import('@playwright/test').Page) {
    const row = page.locator('tbody tr', { hasText: FIXTURE_NAME }).first();
    await expect(row).toBeVisible({ timeout: 15000 });
    return row;
}

test.describe('Budget actions (approve/lock)', () => {
    test.beforeAll(() => {
        runTinker(FIXTURE_PHP);
    });

    test.beforeEach(() => {
        resetFixtureBudget('draft');
    });

    test('editor menyetujui lalu mengunci anggaran lewat menu baris', async ({
        page,
    }) => {
        await login(page, EDITOR_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/budgets');

        const row = await openFixtureRow(page);
        await row.getByRole('button').last().click();
        const approveItem = page.getByRole('menuitem', {
            name: 'Approve Budget',
        });
        await expect(approveItem).toBeVisible({ timeout: 15000 });
        await approveItem.click();

        const dialog = page.getByRole('alertdialog');
        await expect(dialog).toBeVisible();
        await expect(dialog).toContainText(/will be approved/i);

        const approveResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/approve') && r.request().method() === 'POST',
        );
        await dialog.getByRole('button', { name: 'Yes, approve' }).click();
        expect((await approveResponse).status()).toBeLessThan(400);
        await expect(dialog).toBeHidden();
        await expect(row).toContainText('Approved');

        await row.getByRole('button').last().click();
        const lockItem = page.getByRole('menuitem', { name: 'Lock Budget' });
        await expect(lockItem).toBeVisible({ timeout: 15000 });
        await lockItem.click();

        const lockDialog = page.getByRole('alertdialog');
        await expect(lockDialog).toContainText(/will be locked/i);
        const lockResponse = page.waitForResponse(
            (r) => r.url().includes('/lock') && r.request().method() === 'POST',
        );
        await lockDialog.getByRole('button', { name: 'Yes, lock' }).click();
        expect((await lockResponse).status()).toBeLessThan(400);
        await expect(lockDialog).toBeHidden();
        await expect(row).toContainText('Locked');
    });

    test('pengguna lihat-saja tidak melihat aksi approve/lock', async ({
        page,
    }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/budgets');

        const row = await openFixtureRow(page);
        await row.getByRole('button').last().click();
        await expect(
            page.getByRole('menuitem', { name: 'Approve Budget' }),
        ).toHaveCount(0);
        await expect(
            page.getByRole('menuitem', { name: 'Lock Budget' }),
        ).toHaveCount(0);
    });

    test('galat backend ditampilkan saat anggaran sudah berubah di server', async ({
        page,
    }) => {
        await login(page, EDITOR_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/budgets');

        const row = await openFixtureRow(page);
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Approve Budget' }).click();

        // Setujui di server dari luar UI, sehingga halaman masih menampilkan "draft".
        resetFixtureBudget('approved');

        const dialog = page.getByRole('alertdialog');
        const failed = page.waitForResponse(
            (r) =>
                r.url().includes('/approve') && r.request().method() === 'POST',
        );
        await dialog.getByRole('button', { name: 'Yes, approve' }).click();
        expect((await failed).status()).toBe(422);

        await expect(
            page.getByText('Only draft budgets can be approved.'),
        ).toBeVisible();
    });
});
