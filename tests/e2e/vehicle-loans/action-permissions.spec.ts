import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { expectResponseOk, login } from '../helpers';
import { VEHICLE_LOAN_REQUEST_PATH } from './helpers';

const enPage = (
    JSON.parse(
        readFileSync(resolve(process.cwd(), 'lang/en.json'), 'utf8'),
    ) as {
        vehicle_loan_page: Record<string, string>;
    }
).vehicle_loan_page;

test.use({ locale: 'en-US' });

const VIEWER_EMAIL = 'vehicle-loan-viewer@example.com';
const REQUESTER_EMAIL = 'vehicle-loan-requester@example.com';
const FIXTURE_PASSWORD = 'password';

// Dibuat ulang tiap run karena global-setup melakukan migrate:fresh + db:seed.
// Viewer: modul vehicle_loan saja (boleh membuka halaman, tanpa aksi request).
// Requester: ditambah vehicle_loan.request (boleh mengirim permintaan).
const FIXTURE_PHP = [
    `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
    `$names = ['vehicle_loan', 'vehicle_loan.request'];`,
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
    `$make('${VIEWER_EMAIL}', 'Vehicle Viewer', ['vehicle_loan']);`,
    `$make('${REQUESTER_EMAIL}', 'Vehicle Requester', ['vehicle_loan', 'vehicle_loan.request']);`,
].join('\n');

function ensureVehicleLoanFixtures(): void {
    execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${FIXTURE_PHP}`],
        { stdio: 'inherit' },
    );
}

test.describe('Vehicle loan action permissions', () => {
    test.beforeAll(() => {
        ensureVehicleLoanFixtures();
    });

    test('pengguna tanpa vehicle_loan.request tidak melihat tombol kirim/simpan draf', async ({
        page,
    }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });

        const optionsResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/vehicle-loans/assignment-options') &&
                r.request().method() === 'GET',
        );
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);
        await expectResponseOk(await optionsResponse);

        await expect(page.locator('#purpose')).toBeVisible();
        await expect(
            page.getByRole('button', {
                name: enPage.request.submit_button,
            }),
        ).toHaveCount(0);
        await expect(
            page.getByRole('button', {
                name: enPage.request.save_draft_button,
            }),
        ).toHaveCount(0);
    });

    test('pengguna berizin request tetap melihat tombol kirim dan simpan draf', async ({
        page,
    }) => {
        await login(page, REQUESTER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });

        const optionsResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/vehicle-loans/assignment-options') &&
                r.request().method() === 'GET',
        );
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);
        await expectResponseOk(await optionsResponse);

        await expect(
            page.getByRole('button', {
                name: enPage.request.submit_button,
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', {
                name: enPage.request.save_draft_button,
            }),
        ).toBeVisible();
    });
});
