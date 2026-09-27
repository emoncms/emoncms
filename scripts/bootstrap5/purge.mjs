// Remove Bootstrap rules whose selectors name classes that never appear in the source (scan.mjs),
// except the families in FAMILIES.
// Usage: node purge.mjs [in.css] [--out file.css] [--report dir]
// Without --out it only reports sizes and the rejected selectors.
import fs from 'fs';
import path from 'path';
import zlib from 'zlib';
import { PurgeCSS } from 'purgecss';
import { ROOT, sourceFiles, content, safelist } from './scan.mjs';

const args = process.argv.slice(2);
const opt = (name) => { const i = args.indexOf(name); return i >= 0 ? args.splice(i, 2)[1] : null; };
const out = opt('--out');
const report = opt('--report');
const input = path.resolve(args[0] || path.join(ROOT, 'Lib/bootstrap5/css/bootstrap.min.css'));

// Whole families kept whether used or not, so common classes work without a rebuild:
// the grid at every breakpoint, and the utility families without their breakpoint variants.
const FAMILIES = [
    /^(container|row|col|offset|g|gx|gy|row-cols)(-|$)/,
    /^(d|flex|justify-content|align-items|align-self|order|gap|m|mt|mb|ms|me|mx|my|p|pt|pb|ps|pe|px|py|w|h|float|position|text|fw|fst|fs|lh|bg|border|rounded|shadow|opacity|overflow)-(?!(sm|md|lg|xl|xxl)-)[a-z0-9-]+$/,
];
const keep = { ...safelist, greedy: [...safelist.greedy, ...FAMILIES] };

async function main() {
    const files = sourceFiles();
    const css = fs.readFileSync(input, 'utf8');

    const [res] = await new PurgeCSS().purge({
        content: content(files),
        css: [{ raw: css }],
        safelist: keep,
        rejected: true,
        keyframes: false,
        fontFace: false,
        variables: false,
    });

    const size = (s) => `${s.length} B, ${zlib.gzipSync(s, { level: 9 }).length} B gz`;
    console.log(`files scanned: ${files.length}`);
    console.log(`before: ${size(css)}`);
    console.log(`after:  ${size(res.css)}`);
    console.log(`rejected selectors: ${res.rejected.length}`);

    // Rejected selectors grouped by first class name prefix
    const groups = {};
    for (const s of res.rejected) {
        const m = s.match(/\.([a-z]+)/);
        const k = m ? m[1] : '(other)';
        groups[k] = (groups[k] || 0) + 1;
    }
    const top = Object.entries(groups).sort((a, b) => b[1] - a[1]);
    console.log(top.slice(0, 40).map(([k, n]) => `${String(n).padStart(5)}  ${k}`).join('\n'));

    if (report) {
        fs.mkdirSync(report, { recursive: true });
        fs.writeFileSync(path.join(report, 'rejected.txt'), res.rejected.join('\n') + '\n');
        fs.writeFileSync(path.join(report, 'files.txt'), files.map(f => path.relative(ROOT, f)).join('\n') + '\n');
    }
    if (out) fs.writeFileSync(out, res.css);
}

main();
