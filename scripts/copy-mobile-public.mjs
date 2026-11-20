import { cpSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = resolve(root, 'public');
const target = resolve(root, 'dist');

const entries = [
    'asset-files',
    'favicon.ico',
    'favicon.svg',
    'apple-touch-icon.png',
    'logo.svg',
];

for (const entry of entries) {
    const from = resolve(source, entry);

    if (!existsSync(from)) {
        continue;
    }

    const to = resolve(target, entry);
    mkdirSync(dirname(to), { recursive: true });
    cpSync(from, to, { recursive: true });
    console.log(`copied public/${entry} -> dist/${entry}`);
}
