import { execSync } from 'child_process';

export default async function globalSetup() {
    execSync('php artisan migrate --force', { stdio: 'inherit' });
    execSync('php artisan db:seed --class=PermissionSeeder --force', { stdio: 'inherit' });
    execSync('php artisan db:seed --class=MenuSeeder --force', { stdio: 'inherit' });
}

