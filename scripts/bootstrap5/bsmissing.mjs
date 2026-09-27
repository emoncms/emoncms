// List Bootstrap classes that appear in the source (scan.mjs) but are not in the shipped build.
// A hit means a rebuild is needed (node build.mjs), or the component is commented out in scss/bootstrap.scss.
// Usage: node bsmissing.mjs [built.css]   Exit code 1 when classes are missing.
import fs from 'fs';
import path from 'path';
import { ROOT, sourceFiles, content } from './scan.mjs';

const HERE = path.dirname(new URL(import.meta.url).pathname);
const built = path.resolve(process.argv[2] || path.join(ROOT, 'Lib/bootstrap5/css/bootstrap.min.css'));
const stock = path.join(HERE, 'node_modules/bootstrap/dist/css/bootstrap.css');

// Words that are also Bootstrap class names but are not used as classes here
const IGNORE = new Set([
    'placeholder', 'navbar', 'pagination', 'ratio', 'vr', 'carousel', 'toast', 'offcanvas', 'popover',
    'accordion', 'breadcrumb', 'list-group', 'list-group-item',
    'bi', 'collapsed', 'hiding', 'showing',  // icon prefix in the Vue bundle; state classes of omitted components
]);

const classes = css => new Set([...css.replace(/\/\*[\s\S]*?\*\//g, '').matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)].map(m => m[1]));
const have = classes(fs.readFileSync(built, 'utf8'));
const missing = new Set([...classes(fs.readFileSync(stock, 'utf8'))].filter(c => !have.has(c) && !IGNORE.has(c)));

const found = new Map();
const files = sourceFiles();
content(files).forEach((c, i) => {
    c.raw.split('\n').forEach((line, n) => {
        for (const m of line.matchAll(/[\w-]+/g)) {
            if (!missing.has(m[0])) continue;
            if (!found.has(m[0])) found.set(m[0], []);
            found.get(m[0]).push(`${path.relative(ROOT, files[i])}:${n + 1}`);
        }
    });
});

if (!found.size) { console.log('no missing Bootstrap classes'); process.exit(0); }
for (const [cls, where] of [...found].sort()) console.log(`${cls.padEnd(24)} ${where.slice(0, 3).join(' ')}${where.length > 3 ? ` +${where.length - 3}` : ''}`);
process.exit(1);
