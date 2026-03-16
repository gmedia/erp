import { execFileSync } from 'node:child_process';

export default async function globalSetup() {
    const phpBinary = '/usr/bin/php';

    execFileSync(phpBinary, ['artisan', 'migrate:fresh', '--force'], {
        stdio: 'inherit',
    });
    execFileSync(phpBinary, ['artisan', 'db:seed', '--force'], {
        stdio: 'inherit',
    });
}
