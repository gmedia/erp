import { expect, test, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { login } from '../helpers';

const VIEWER_EMAIL = 'module-action-viewer@example.com';
const FIXTURE_PASSWORD = 'password';

const VIEW_PAGES: Array<{ path: string; apiPath: string; perm: string }> = [
    { path: '/purchase-orders', apiPath: '/api/purchase-orders', perm: 'purchase_order' },
    { path: '/goods-receipts', apiPath: '/api/goods-receipts', perm: 'goods_receipt' },
    { path: '/stock-transfers', apiPath: '/api/stock-transfers', perm: 'stock_transfer' },
    { path: '/supplier-bills', apiPath: '/api/supplier-bills', perm: 'supplier_bill' },
    { path: '/customer-invoices', apiPath: '/api/customer-invoices', perm: 'customer_invoice' },
    { path: '/assets', apiPath: '/api/assets', perm: 'asset' },
];

// Fixture hanya memegang izin lihat (modul.<nama> tanpa create/edit/delete).
// Dibuat ulang tiap run karena global-setup melakukan migrate:fresh + db:seed.
const FIXTURE_PHP = [
    `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
    `$names = ${JSON.stringify(VIEW_PAGES.map((p) => p.perm))};`,
    `$perms = \\App\\Models\\Permission::whereIn('name', $names)->get()->keyBy('name');`,
    `$user = \\App\\Models\\User::updateOrCreate(['email' => '${VIEWER_EMAIL}'], [`,
    `    'name' => 'Module Viewer', 'password' => $hash, 'email_verified_at' => now(),`,
    `]);`,
    `$code = 'EMP-' . strtoupper(substr(md5('${VIEWER_EMAIL}'), 0, 8));`,
    `$emp = \\App\\Models\\Employee::updateOrCreate(['user_id' => $user->id], [`,
    `    'employee_id' => $code, 'name' => 'Module Viewer', 'email' => '${VIEWER_EMAIL}',`,
    `]);`,
    `$emp->permissions()->sync($perms->pluck('id')->all());`,
].join('\n');

function ensureViewerFixture(): void {
    execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${FIXTURE_PHP}`],
        { stdio: 'inherit' },
    );
}

async function openListPage(
    page: Page,
    path: string,
    apiPath: string,
): Promise<void> {
    const listResponse = page.waitForResponse(
        (r) =>
            r.url().includes(apiPath) &&
            r.request().method() === 'GET' &&
            r.status() < 400,
    );
    await page.goto(path);
    await listResponse;
}

test.describe('Module action permissions (lihat-saja)', () => {
    test.beforeAll(() => {
        ensureViewerFixture();
    });

    test.beforeEach(async ({ page }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
    });

    for (const target of VIEW_PAGES) {
        test(`${target.path}: tombol Add tidak tampil untuk lihat-saja`, async ({
            page,
        }) => {
            await openListPage(page, target.path, target.apiPath);

            await expect(page.getByRole('button', { name: 'Add' })).toHaveCount(
                0,
            );
        });
    }
});
