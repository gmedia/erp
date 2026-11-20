import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const menuSeeder = readFileSync(
    resolve(root, 'database/seeders/MenuSeeder.php'),
    'utf8',
);
const iconMapSource = readFileSync(
    resolve(root, 'resources/js/lib/icon-map.ts'),
    'utf8',
);

const menuIcons = [
    ...new Set(
        [...menuSeeder.matchAll(/'icon'\s*=>\s*'([^']+)'/g)].map(
            (match) => match[1],
        ),
    ),
].sort();

if (menuIcons.length === 0) {
    console.error('No menu icons found in database/seeders/MenuSeeder.php.');
    process.exit(1);
}

const importBlock = iconMapSource.match(
    /import\s*\{([\s\S]*?)\}\s*from\s*'lucide-react';/,
);

if (!importBlock) {
    console.error('Could not find the lucide-react import in icon-map.ts.');
    process.exit(1);
}

const mapBlock = iconMapSource.match(
    /const iconMap:\s*Record<string,\s*LucideIcon>\s*=\s*\{([\s\S]*?)\n\};/,
);

if (!mapBlock) {
    console.error('Could not find the iconMap declaration in icon-map.ts.');
    process.exit(1);
}

const importedIcons = new Set(
    importBlock[1]
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean),
);

const registeredIcons = new Set(
    [...mapBlock[1].matchAll(/^\s*([A-Za-z0-9]+),\s*$/gm)].map(
        (match) => match[1],
    ),
);

const missingImports = menuIcons.filter((icon) => !importedIcons.has(icon));
const missingEntries = menuIcons.filter((icon) => !registeredIcons.has(icon));

if (missingImports.length > 0 || missingEntries.length > 0) {
    console.error('Sidebar icon audit failed.');

    if (missingImports.length > 0) {
        console.error(
            `Missing lucide-react imports: ${missingImports.join(', ')}`,
        );
    }

    if (missingEntries.length > 0) {
        console.error(`Missing iconMap entries: ${missingEntries.join(', ')}`);
    }

    console.error(
        'Add each icon to both the import block and iconMap in resources/js/lib/icon-map.ts.',
    );
    process.exit(1);
}

console.log(`Sidebar icon audit passed: ${menuIcons.length} icons registered.`);
