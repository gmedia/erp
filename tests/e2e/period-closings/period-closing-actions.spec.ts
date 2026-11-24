import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { login } from '../helpers';

const EDITOR_EMAIL = 'period-closing-editor@example.com';
const VIEWER_EMAIL = 'period-closing-viewer@example.com';
const FIXTURE_PASSWORD = 'password';

const FIXTURE_YEAR = 2099;
const FIXTURE_MONTH = 12;
const ANNUAL_YEAR = 2098;

const FIXTURE_PHP = [
    `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
    `$names = ['period_closing', 'period_closing.create', 'period_closing.edit', 'fiscal_year', 'account'];`,
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
    `$make('${VIEWER_EMAIL}', 'Period Viewer', ['period_closing']);`,
    `$make('${EDITOR_EMAIL}', 'Period Editor', ['period_closing', 'period_closing.create', 'period_closing.edit', 'fiscal_year', 'account']);`,
    `$fy = \\App\\Models\\FiscalYear::where('name', 'like', '%${FIXTURE_YEAR}%')->first()`,
    `    ?? \\App\\Models\\FiscalYear::first();`,
    `\\App\\Models\\FiscalYear::firstOrCreate(['name' => 'FY ${ANNUAL_YEAR}'], ['start_date' => '${ANNUAL_YEAR}-01-01', 'end_date' => '${ANNUAL_YEAR}-12-31', 'status' => 'open']);`,
    `$account = \\App\\Models\\Account::first();`,
    `$creator = \\App\\Models\\User::where('email', '${EDITOR_EMAIL}')->first();`,
    `\\App\\Models\\PeriodClosing::updateOrCreate(`,
    `    ['fiscal_year_id' => $fy->id, 'period_month' => ${FIXTURE_MONTH},`,
    `     'period_year' => ${FIXTURE_YEAR}, 'closing_type' => 'monthly'],`,
    `    ['status' => 'draft', 'net_income' => 0,`,
    `     'retained_earnings_account_id' => $account->id, 'created_by' => $creator->id,`,
    `     'closed_by' => null, 'closed_at' => null, 'reopened_by' => null,`,
    `     'reopened_at' => null, 'closing_journal_entry_id' => null],`,
    `);`,
].join('\n');

function runTinker(php: string): string {
    return execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${php}`],
        {
            encoding: 'utf8',
        },
    );
}

function resetFixturePeriod(status: 'draft' | 'closed'): void {
    const closedAt = status === 'closed' ? 'now()' : 'null';
    runTinker(
        `\\App\\Models\\PeriodClosing::where('period_year', ${FIXTURE_YEAR})` +
            `->where('period_month', ${FIXTURE_MONTH})->update([` +
            `'status' => '${status}', 'closed_at' => ${closedAt}, 'closed_by' => null,` +
            ` 'reopened_at' => null, 'reopened_by' => null, 'closing_journal_entry_id' => null]);`,
    );
}

async function openFixtureRow(page: import('@playwright/test').Page) {
    const row = page
        .locator('tbody tr', { hasText: String(FIXTURE_YEAR) })
        .first();
    await expect(row).toBeVisible({ timeout: 15000 });
    return row;
}

test.describe('Period closing actions (close/reopen)', () => {
    test.beforeAll(() => {
        runTinker(FIXTURE_PHP);
    });

    test.beforeEach(() => {
        resetFixturePeriod('draft');
    });

    test('editor menutup lalu membuka periode lewat menu baris', async ({
        page,
    }) => {
        await login(page, EDITOR_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/period-closings');

        const row = await openFixtureRow(page);
        await row.getByRole('button', { name: 'Actions' }).click();
        const closeItem = page.getByRole('menuitem', { name: 'Close Period' });
        await expect(closeItem).toBeVisible({ timeout: 15000 });
        await closeItem.click();

        const dialog = page.getByRole('alertdialog');
        await expect(dialog).toBeVisible();
        await expect(dialog).toContainText(/will be closed/i);

        const closeResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/close') && r.request().method() === 'POST',
        );
        await dialog.getByRole('button', { name: 'Yes, close' }).click();
        expect((await closeResponse).status()).toBeLessThan(400);
        await expect(dialog).toBeHidden();
        await expect(row).toContainText('Closed');

        await row.getByRole('button', { name: 'Actions' }).click();
        const reopenItem = page.getByRole('menuitem', {
            name: 'Reopen Period',
        });
        await expect(reopenItem).toBeVisible({ timeout: 15000 });
        await reopenItem.click();

        const reopenDialog = page.getByRole('alertdialog');
        await expect(reopenDialog).toContainText(/will be reopened/i);
        const reopenResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/reopen') && r.request().method() === 'POST',
        );
        await reopenDialog.getByRole('button', { name: 'Yes, reopen' }).click();
        expect((await reopenResponse).status()).toBeLessThan(400);
        await expect(reopenDialog).toBeHidden();
        await expect(row).toContainText('Reopened');
    });

    test('editor menutup periode lewat view modal', async ({ page }) => {
        await login(page, EDITOR_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/period-closings');

        const row = await openFixtureRow(page);
        await row.getByRole('button', { name: 'Actions' }).click();
        await page.getByRole('menuitem', { name: 'View' }).click();

        const modal = page.getByRole('dialog', {
            name: 'Period Closing Details',
        });
        await expect(modal).toBeVisible();
        await modal.getByRole('button', { name: 'Close Period' }).click();

        const dialog = page.getByRole('alertdialog');
        await expect(dialog).toContainText(/will be closed/i);
        const closeResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/close') && r.request().method() === 'POST',
        );
        await dialog.getByRole('button', { name: 'Yes, close' }).click();
        expect((await closeResponse).status()).toBeLessThan(400);

        await expect(
            modal.getByRole('button', { name: 'Reopen Period' }),
        ).toBeVisible();
        await expect(
            modal.getByRole('button', { name: 'Close Period' }),
        ).toHaveCount(0);
    });

    test('editor membuat penutupan tahunan dari form', async ({ page }) => {
        await login(page, EDITOR_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/period-closings');

        await page.getByRole('button', { name: /^Add/ }).first().click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        // Pilihan AsyncSelect ada di popover; 'li button' polos juga cocok dengan menu sidebar.
        const options = page.locator('[data-slot="popover-content"] li button');

        await dialog.getByRole('combobox', { name: 'Closing Type' }).click();
        await page.getByRole('option', { name: 'Annual', exact: true }).click();

        await dialog.getByRole('combobox', { name: 'Fiscal Year' }).click();
        await options
            .filter({ hasText: String(ANNUAL_YEAR) })
            .first()
            .click();

        await dialog.getByLabel('Period Year').fill(String(ANNUAL_YEAR));

        await dialog
            .getByRole('combobox', { name: /Retained Earnings Account/ })
            .click();
        await options.first().click();

        const submitResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/period-closings') &&
                r.request().method() === 'POST',
        );
        await dialog.getByRole('button', { name: 'Add', exact: true }).click();
        const response = await submitResponse;
        expect(response.status()).toBeLessThan(300);

        const row = page
            .locator('tbody tr', { hasText: String(ANNUAL_YEAR) })
            .first();
        await expect(row).toBeVisible({ timeout: 15000 });
        await expect(row).toContainText(/Annual/i);

        await row.getByRole('button', { name: 'Actions' }).click();
        await page.getByRole('menuitem', { name: 'Close Period' }).click();
        await page.getByRole('button', { name: 'Yes, close' }).click();
        await expect(row).toContainText('Closed', { timeout: 15000 });
    });

    test('pengguna lihat-saja tidak melihat aksi close/reopen', async ({
        page,
    }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/period-closings');

        const row = await openFixtureRow(page);
        await row.getByRole('button', { name: 'Actions' }).click();
        await expect(
            page.getByRole('menuitem', { name: 'View' }),
        ).toBeVisible();
        await expect(
            page.getByRole('menuitem', { name: 'Close Period' }),
        ).toHaveCount(0);
        await expect(
            page.getByRole('menuitem', { name: 'Reopen Period' }),
        ).toHaveCount(0);
    });

    test('galat backend ditampilkan saat periode sudah ditutup di server', async ({
        page,
    }) => {
        await login(page, EDITOR_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
        await page.goto('/period-closings');

        const row = await openFixtureRow(page);
        await row.getByRole('button', { name: 'Actions' }).click();
        await page.getByRole('menuitem', { name: 'View' }).click();

        const modal = page.getByRole('dialog', {
            name: 'Period Closing Details',
        });
        await expect(
            modal.getByRole('button', { name: 'Close Period' }),
        ).toBeVisible();

        // Tutup di server dari luar UI, sehingga modal masih menampilkan "draft".
        resetFixturePeriod('closed');

        await modal.getByRole('button', { name: 'Close Period' }).click();
        const dialog = page.getByRole('alertdialog');
        const failed = page.waitForResponse(
            (r) =>
                r.url().includes('/close') && r.request().method() === 'POST',
        );
        await dialog.getByRole('button', { name: 'Yes, close' }).click();
        expect((await failed).status()).toBe(422);

        await expect(page.getByText('Period is already closed.')).toBeVisible();
    });
});
