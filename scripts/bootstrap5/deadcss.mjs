// Report rules in the hand written CSS whose selectors name classes or ids that never appear
// in the source (scan.mjs), per file, for review by hand. Nothing is changed.
// Same file set as cssbytes.sh, plus the <style> blocks in views.
// Also lists custom properties that are defined but never read with var().
// Usage: node deadcss.mjs [-v]   (-v lists every selector with its line)
import fs from 'fs';
import path from 'path';
import { PurgeCSS } from 'purgecss';
import { ROOT, STYLE, sourceFiles, content, safelist } from './scan.mjs';

const verbose = process.argv.includes('-v');
const VENDOR = /bootstrap\.min\.css|bootstrap2-icons\.css|svg-icons\.css|montserrat\.css/;
const SKIP = new Set(['node_modules', 'vendor', '.git', 'scripts', 'tools']);

function cssFiles(dir, out, seen) {
    const real = fs.realpathSync(dir);
    if (seen.has(real)) return;
    seen.add(real);
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        if (SKIP.has(e.name)) continue;
        const p = path.join(dir, e.name);
        let st;
        try { st = fs.statSync(p); } catch { continue; }
        if (st.isDirectory()) cssFiles(p, out, seen);
        else if (e.name.endsWith('.css') && !VENDOR.test(e.name)) out.push(p);
    }
}

// Known uses the scan cannot see: names built at runtime and selectors without a class
const known = {
    ...safelist,
    greedy: [
        ...safelist.greedy,
        /^(bg|text)-(purple|orange)-/,  // tag helpers, 'bg-' + colour
        /data-hide-/,                   // list columns, 'data-hide-' + key
        /^no-gravatar$/,                // theme.php, 'no-' + 'gravatar'
        /_autocomplete-list$/,          // autocomplete.js, id + '_autocomplete-list'
        /^:/,                           // pseudo-class only, e.g. :hover, :fullscreen
        /input-(75|105|165|220|285|545|auto)/,  // theme width classes inside :is()
    ],
};

const files = sourceFiles();
const scanned = content(files);

// Sheets: .css files and the <style> blocks of each view
const sheets = [];
const css = [];
cssFiles(ROOT, css, new Set());
for (const f of css.sort()) sheets.push({ name: path.relative(ROOT, f), css: fs.readFileSync(f, 'utf8'), line0: 0 });
for (const f of files.filter(f => /\.(php|html?)$/.test(f) && !f.includes('/tools/'))) {
    const src = fs.readFileSync(f, 'utf8');
    for (const m of src.matchAll(STYLE)) {
        const line0 = src.slice(0, m.index).split('\n').length - 1;
        sheets.push({ name: path.relative(ROOT, f) + ' <style>', css: m[1], line0 });
    }
}

let total = 0, count = 0;
const rows = [], errors = [];
for (const s of sheets) {
    const [res] = await new PurgeCSS().purge({
        content: scanned,
        css: [{ raw: s.css }],
        safelist: known,
        rejected: true,
        keyframes: true,
        fontFace: false,
        variables: false,
    }).catch(e => { errors.push(`${s.name}:${s.line0 + e.line}: ${e.reason}`); return [null]; });
    if (!res || !res.rejected.length) continue;
    const saved = s.css.length - res.css.length;
    total += saved; count += res.rejected.length;
    const lines = s.css.split('\n');
    const where = sel => {
        const key = sel.split(/[\s>+~]/)[0];
        const i = lines.findIndex(l => l.includes(sel) || l.includes(key));
        return i < 0 ? '?' : s.line0 + i + 1;
    };
    rows.push({ name: s.name, saved, sel: res.rejected.map(r => `${where(r)}: ${r}`) });
}

rows.sort((a, b) => b.saved - a.saved);
for (const r of rows) {
    console.log(`${String(r.saved).padStart(6)} B  ${String(r.sel.length).padStart(3)} sel  ${r.name}`);
    if (verbose) for (const l of r.sel) console.log(`             ${l}`);
}
console.log(`${String(total).padStart(6)} B  ${String(count).padStart(3)} sel  total`);
if (errors.length) console.log(`\nparse errors, not scanned:\n  ${errors.join('\n  ')}`);

// Custom properties defined in our CSS but never read with var() anywhere
const all = sheets.map(s => s.css).join('\n') + scanned.map(c => c.raw).join('\n');
const defined = new Set([...sheets.map(s => s.css).join('\n').matchAll(/(?:^|[{;\s])(--[\w-]+)\s*:/g)].map(m => m[1]));
const read = new Set([...all.matchAll(/var\(\s*(--[\w-]+)/g)].map(m => m[1]));
// also set or read from JS by name, e.g. setProperty('--x')
const named = new Set([...scanned.map(c => c.raw).join('\n').matchAll(/['"`](--[\w-]+)['"`]/g)].map(m => m[1]));
const unused = [...defined].filter(v => !v.startsWith('--bs-') && !read.has(v) && !named.has(v)).sort();
console.log(`\ncustom properties never read: ${unused.length}`);
if (unused.length) console.log('  ' + unused.join(' '));
