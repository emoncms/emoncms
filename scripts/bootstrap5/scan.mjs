// Source files scanned for class names, shared by purge.mjs and deadcss.mjs.
// Core and the modules, symlinked modules followed. <style> blocks are removed from the
// content so a rule does not count as its own use.
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

const EXT = new Set(['.php', '.js', '.html', '.htm', '.json', '.vue']);
const SKIP = new Set(['node_modules', 'vendor', '.git', 'scripts', 'docs', 'bootstrap5']);
export const STYLE = /<style[^>]*>([\s\S]*?)<\/style>/g;

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

export function sourceFiles() {
    const files = [];
    walk(ROOT, files, new Set());
    return files;
}

// PurgeCSS raw content entries with <style> blocks removed
export function content(files) {
    return files.map(f => ({
        raw: fs.readFileSync(f, 'utf8').replace(STYLE, ''),
        extension: path.extname(f).slice(1),
    }));
}

// Classes and attributes set by Bootstrap JS or built at runtime from a prefix
const COLORS = '(primary|secondary|success|danger|warning|info|light|dark)';
export const safelist = {
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
    ],
};
