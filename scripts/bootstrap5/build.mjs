// Build Lib/bootstrap5/css/bootstrap.min.css from scss/bootstrap.scss with Bootstrap's own pipeline
// (Sass, autoprefixer, clean-css), remove selectors whose classes do not appear in the source,
// then list Bootstrap classes the source uses that the build lacks.
// Usage: node build.mjs [--check]   --check lists missing classes only, exit code 1 when there are any.
import fs from 'fs';
import path from 'path';
import zlib from 'zlib';
import { fileURLToPath } from 'url';
import * as sass from 'sass';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';
import CleanCSS from 'clean-css';
import { PurgeCSS } from 'purgecss';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(HERE, '../..');
const OUT = path.join(ROOT, 'Lib/bootstrap5/css/bootstrap.min.css');
const STOCK = path.join(HERE, 'node_modules/bootstrap/dist/css/bootstrap.css');

// Browser targets from Bootstrap 5.3 .browserslistrc
const BROWSERS = [
    '>= 0.5%', 'last 2 major versions', 'not dead',
    'Chrome >= 60', 'Firefox >= 60', 'Firefox ESR', 'iOS >= 12', 'Safari >= 12',
    'not Explorer <= 11', 'not kaios <= 2.5',
];

const BANNER = '@charset "UTF-8";/*!\n * Bootstrap  v5.3.8 (https://getbootstrap.com/)\n'
    + ' * Copyright 2011-2025 The Bootstrap Authors\n'
    + ' * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)\n'
    + ' * Custom build for emoncms: scripts/bootstrap5/build.mjs\n */';

// -- Source scan ------------------------------------------------------------
// Every source file in core and the modules, symlinks followed. <style> blocks are removed,
// so a page rule does not count as a use of its own class.

const EXT = new Set(['.php', '.js', '.html', '.htm', '.json', '.vue']);
const SKIP = new Set(['node_modules', 'vendor', '.git', 'scripts', 'docs', 'bootstrap5']);
const STYLE = /<style[^>]*>([\s\S]*?)<\/style>/g;

function walk(dir, files, seen) {
    const real = fs.realpathSync(dir);
    if (seen.has(real)) return;
    seen.add(real);
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        if (SKIP.has(e.name)) continue;
        const p = path.join(dir, e.name);
        let st;
        try { st = fs.statSync(p); } catch { continue; }
        if (st.isDirectory()) walk(p, files, seen);
        else if (EXT.has(path.extname(e.name))) files.push(p);
    }
}

const files = [];
walk(ROOT, files, new Set());
const content = files.map(f => ({
    raw: fs.readFileSync(f, 'utf8').replace(STYLE, ''),
    extension: path.extname(f).slice(1),
}));

// -- Purge rules --------------------------------------------------------------

// Classes and attributes set by Bootstrap JS or built at runtime from a prefix
const COLORS = '(primary|secondary|success|danger|warning|info|light|dark)';
const SAFELIST = {
    standard: [
        'show', 'showing', 'hiding', 'fade', 'collapsing', 'active', 'disabled',
        'modal-open', 'modal-backdrop', 'modal-static',
        'dropdown-menu-end', 'dropdown-menu-start', 'was-validated',
    ],
    deep: [],
    greedy: [
        /data-bs-popper/, /data-popper-placement/,
        /^bs-tooltip-/, /^tooltip/,
        new RegExp(`^(bg|text|border|alert|btn|btn-outline)-${COLORS}(-subtle|-emphasis)?$`),
        /^(theme|sidebar|svg-icon|icon|wifi-signal|sync|ref)-/,
        // Whole families kept whether used or not, so common classes work without a rebuild:
        // the grid at every breakpoint, and the utility families without their breakpoint variants.
        /^(container|row|col|offset|g|gx|gy|row-cols)(-|$)/,
        /^(d|flex|justify-content|align-items|align-self|order|gap|m|mt|mb|ms|me|mx|my|p|pt|pb|ps|pe|px|py|w|h|float|position|text|fw|fst|fs|lh|bg|border|rounded|shadow|opacity|overflow)-(?!(sm|md|lg|xl|xxl)-)[a-z0-9-]+$/,
    ],
};

// Words that are also Bootstrap class names but are not used as classes here
const IGNORE = new Set([
    'placeholder', 'navbar', 'pagination', 'ratio', 'vr', 'carousel', 'toast', 'offcanvas', 'popover',
    'accordion', 'breadcrumb', 'list-group', 'list-group-item',
    'bi', 'collapsed', 'hiding', 'showing',  // icon prefix in the Vue bundle; state classes of omitted components
]);

// -- Build --------------------------------------------------------------------

if (!process.argv.includes('--check')) {
    const compiled = sass.compile(path.join(HERE, 'scss/bootstrap.scss'), {
        style: 'expanded',
        loadPaths: [path.join(HERE, 'node_modules')],
        quietDeps: true,
    }).css;

    const prefixed = (await postcss([autoprefixer({ cascade: false, overrideBrowserslist: BROWSERS })])
        .process(compiled, { from: undefined })).css;

    const min = new CleanCSS({ level: 1, format: { breakWith: 'lf' } }).minify(prefixed);
    if (min.errors.length) throw new Error(min.errors.join('\n'));

    const [purged] = await new PurgeCSS().purge({
        content,
        css: [{ raw: BANNER + min.styles.replace(/^@charset "UTF-8";/, '') }],
        safelist: SAFELIST,
        keyframes: false,
        fontFace: false,
        variables: false,
    });
    fs.writeFileSync(OUT, purged.css);
    console.log(`${path.relative(ROOT, OUT)}: ${purged.css.length} B, ${zlib.gzipSync(purged.css, { level: 9 }).length} B gz`);
}

// -- Missing classes ----------------------------------------------------------
// Bootstrap classes in the stock file that the build lacks and the source uses.

const classes = css => new Set([...css.replace(/\/\*[\s\S]*?\*\//g, '').matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)].map(m => m[1]));
const have = classes(fs.readFileSync(OUT, 'utf8'));
const missing = new Set([...classes(fs.readFileSync(STOCK, 'utf8'))].filter(c => !have.has(c) && !IGNORE.has(c)));

const found = new Map();
content.forEach((c, i) => {
    c.raw.split('\n').forEach((line, n) => {
        for (const m of line.matchAll(/[\w-]+/g)) {
            if (!missing.has(m[0])) continue;
            if (!found.has(m[0])) found.set(m[0], []);
            found.get(m[0]).push(`${path.relative(ROOT, files[i])}:${n + 1}`);
        }
    });
});

if (!found.size) { console.log('no missing Bootstrap classes'); process.exit(0); }
console.log('Used but not in the build. Rebuild, or uncomment the component in scss/bootstrap.scss:');
for (const [cls, where] of [...found].sort()) console.log(`${cls.padEnd(24)} ${where.slice(0, 3).join(' ')}${where.length > 3 ? ` +${where.length - 3}` : ''}`);
process.exit(1);
