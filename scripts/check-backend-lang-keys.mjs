import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

/**
 * Guard i18n backend (Issue #387): __('a.b') di app/ dibaca Laravel sebagai kunci
 * DATAR dari lang/{locale}.json. Kunci bertitik yang hanya ada sebagai objek
 * bersarang (mis. "supplier_return": { ... }) tidak ditemukan, lalu pesan tampil
 * sebagai kunci mentah. Setiap kunci yang dipanggil wajib ada datar di en dan id.
 */

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const appDir = resolve(root, 'app');
const locales = ['en', 'id'];

// Pola __('kunci.bertitik') dengan kutip tunggal atau ganda.
const callPattern = /\b__\(\s*(['"])([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)\1/g;

function walk(dir) {
    return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) {
            return walk(path);
        }
        return entry.name.endsWith('.php') ? [path] : [];
    });
}

function loadJson(locale) {
    return JSON.parse(readFileSync(resolve(root, 'lang', `${locale}.json`), 'utf8'));
}

const translations = Object.fromEntries(locales.map((locale) => [locale, loadJson(locale)]));

// Kunci yang berbahaya: induknya (sebelum titik pertama) ada sebagai objek bersarang,
// tetapi kunci datarnya tidak ada. Kunci tanpa objek induk (mis. "auth.failed",
// yang berasal dari paket framework) tidak termasuk jebakan ini dan dilewati.
function isNestedOnly(data, key) {
    const parent = key.split('.')[0];
    return typeof data[parent] === 'object' && data[parent] !== null && !(key in data);
}

const violations = [];
const seen = new Set();

for (const file of walk(appDir)) {
    const rel = relative(root, file).split('\\').join('/');
    const source = readFileSync(file, 'utf8');

    for (const match of source.matchAll(callPattern)) {
        const key = match[2];
        const line = source.slice(0, match.index).split('\n').length;

        for (const locale of locales) {
            if (isNestedOnly(translations[locale], key)) {
                violations.push(`${rel}:${line}  '${key}' hanya ada sebagai objek bersarang di lang/${locale}.json`);
            }
        }
        seen.add(key);
    }
}

if (violations.length > 0) {
    console.error(`Found ${violations.length} missing flat backend translation key(s):\n`);
    console.error(violations.map((v) => `  ${v}`).join('\n'));
    console.error(
        '\nTambahkan kunci datar (mis. "supplier_return.items_locked") di lang/en.json DAN lang/id.json.',
    );
    process.exit(1);
}

console.log(
    `Backend lang key check passed: ${seen.size} dotted key(s) found in app/, all flat in ${locales.join(' + ')}.`,
);
