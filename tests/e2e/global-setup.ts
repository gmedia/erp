import { execSync } from 'node:child_process';

export default async function globalSetup() {
    execSync('php artisan migrate:fresh --force', { stdio: 'inherit' });
    execSync('php artisan db:seed --force', { stdio: 'inherit' });
}
