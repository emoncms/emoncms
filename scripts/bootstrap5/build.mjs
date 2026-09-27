// Build Bootstrap CSS from Sass with Bootstrap's own pipeline (Sass, autoprefixer, clean-css),
// then remove unused selectors with purge.mjs and check for missing classes with bsmissing.mjs.
// Usage: node build.mjs [entry.scss] [--out file.css] [--no-purge]
// Defaults: scss/bootstrap.scss to Lib/bootstrap5/css/bootstrap.min.css.
import fs from 'fs';
import path from 'path';
import zlib from 'zlib';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';
import * as sass from 'sass';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';
import CleanCSS from 'clean-css';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(HERE, '../..');

const args = process.argv.slice(2);
const flag = (name) => { const i = args.indexOf(name); return i >= 0 ? (args.splice(i, 1), true) : false; };
const opt = (name) => { const i = args.indexOf(name); return i >= 0 ? args.splice(i, 2)[1] : null; };
const noPurge = flag('--no-purge');
const out = path.resolve(opt('--out') || path.join(ROOT, 'Lib/bootstrap5/css/bootstrap.min.css'));
const entry = path.resolve(args[0] || path.join(HERE, 'scss/bootstrap.scss'));

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

const compiled = sass.compile(entry, {
    style: 'expanded',
    loadPaths: [path.join(HERE, 'node_modules')],
    quietDeps: true,
}).css;

const prefixed = (await postcss([autoprefixer({ cascade: false, overrideBrowserslist: BROWSERS })])
    .process(compiled, { from: undefined })).css;

const min = new CleanCSS({ level: 1, format: { breakWith: 'lf' } }).minify(prefixed);
if (min.errors.length) throw new Error(min.errors.join('\n'));
const css = BANNER + min.styles.replace(/^@charset "UTF-8";/, '');

if (noPurge) {
    fs.writeFileSync(out, css);
} else {
    const tmp = out + '.tmp';
    fs.writeFileSync(tmp, css);
    execFileSync('node', [path.join(HERE, 'purge.mjs'), tmp, '--out', out], { stdio: 'inherit' });
    fs.unlinkSync(tmp);
}
const final = fs.readFileSync(out);
console.log(`${path.relative(ROOT, out)}: ${final.length} B, ${zlib.gzipSync(final, { level: 9 }).length} B gz`);
// Classes used in the source but missing from this build
try { execFileSync('node', [path.join(HERE, 'bsmissing.mjs'), out], { stdio: 'inherit' }); }
catch { console.log('Warning: the classes above are used but not in the build. Uncomment their component in scss/bootstrap.scss.'); }
