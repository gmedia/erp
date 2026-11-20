import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

/**
 * Guard bahasa visual (docs/ui-design-rules.md §0): warna di resources/js wajib
 * lewat token tema (text-success, bg-warning-soft, text-destructive-foreground, ...),
 * bukan kelas palet Tailwind mentah seperti text-red-500 / bg-emerald-100.
 */

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const scanDir = resolve(root, 'resources/js');

// Pengecualian yang disengaja (relatif ke root repo) + alasannya.
const allowlist = new Map([
    [
        'resources/js/layouts/auth/auth-split-layout.tsx',
        'panel brand halaman login yang selalu gelap',
    ],
]);

const palette = [
    'slate',
    'gray',
    'zinc',
    'neutral',
    'stone',
    'red',
    'orange',
    'amber',
    'yellow',
    'lime',
    'green',
    'emerald',
    'teal',
    'cyan',
    'sky',
    'blue',
    'indigo',
    'violet',
    'purple',
    'fuchsia',
    'pink',
    'rose',
].join('|');

const utilities = [
    'bg',
    'text',
    'border(?:-[xytrbl])?',
    'ring(?:-offset)?',
    'outline',
    'divide',
    'from',
    'via',
    'to',
    'fill',
    'stroke',
    'decoration',
    'shadow',
    'accent',
    'caret',
    'placeholder',
].join('|');

const rawColor = new RegExp(
    `(?<![\\w-])(?:[\\w-]+:)*(?:${utilities})-(?:${palette})-\\d{2,3}(?:\\/\\d{1,3})?(?![\\w-])`,
    'g',
);

function walk(dir) {
    return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) {
            return walk(path);
        }
        return /\.(tsx?|jsx?|css)$/.test(entry.name) ? [path] : [];
    });
}

const violations = [];

for (const file of walk(scanDir)) {
    const rel = relative(root, file).split('\\').join('/');
    if (allowlist.has(rel)) {
        continue;
    }

    readFileSync(file, 'utf8')
        .split('\n')
        .forEach((line, index) => {
            for (const match of line.matchAll(rawColor)) {
                violations.push(`${rel}:${index + 1}  ${match[0]}`);
            }
        });
}

if (violations.length > 0) {
    console.error(
        `Found ${violations.length} raw Tailwind palette class(es) in resources/js:\n`,
    );
    console.error(violations.map((v) => `  ${v}`).join('\n'));
    console.error(
        '\nUse theme tokens instead (see docs/ui-design-rules.md §0), e.g. text-success, bg-warning-soft, text-destructive-foreground, bg-muted.',
    );
    process.exit(1);
}

console.log(
    `Raw color check passed: 0 raw palette classes (${allowlist.size} allowlisted file).`,
);
