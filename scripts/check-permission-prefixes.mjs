import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

/**
 * Guard permission UI (issue #392): setiap permissionPrefix di konfigurasi entity
 * wajib cocok dengan nama permission yang dipakai middleware di routes/api
 * (sumber kebenaran). Konfigurasi yang belum diisi dicetak sebagai daftar.
 */

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const configFile = resolve(root, 'resources/js/utils/entityConfigs.ts');
const routesDir = resolve(root, 'routes/api');

const routePermissions = new Set(
    readdirSync(routesDir)
        .filter((name) => name.endsWith('.php'))
        .flatMap((name) =>
            [
                ...readFileSync(join(routesDir, name), 'utf8').matchAll(
                    /permission:([\w.]+)/g,
                ),
            ].map((match) => match[1].replace(/\.(create|edit|delete)$/, '')),
        ),
);

const source = readFileSync(configFile, 'utf8');
const declarations = [
    ...source.matchAll(
        /export const (\w+Config)\s*=\s*create(?:Simple|Complex)EntityConfig/g,
    ),
];

const unset = [];
const invalid = [];

declarations.forEach((match, index) => {
    const end =
        index + 1 < declarations.length
            ? declarations[index + 1].index
            : source.length;
    const body = source.slice(match.index, end);
    const prefix = body.match(/permissionPrefix:\s*'([\w]+)'/);

    if (!prefix) {
        unset.push(match[1]);
    } else if (!routePermissions.has(prefix[1])) {
        invalid.push(`${match[1]} -> '${prefix[1]}'`);
    }
});

const rel = relative(root, configFile);

if (invalid.length > 0) {
    console.error(
        `Found ${invalid.length} permissionPrefix not present in routes/api (${rel}):\n`,
    );
    console.error(invalid.map((line) => `  ${line}`).join('\n'));
    process.exit(1);
}

console.log(
    `Permission prefix check passed: ` +
        `${declarations.length - unset.length}/${declarations.length} configs set, ` +
        `all match routes/api.`,
);
if (unset.length > 0) {
    console.log(`Belum diisi (${unset.length}):`);
    console.log(unset.map((name) => `  ${name}`).join('\n'));
}
