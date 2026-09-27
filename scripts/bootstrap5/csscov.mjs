// csscov.mjs dirs...: used share of each stylesheet from the css-coverage files in the given
// state output folders, merged across them. Bootstrap and hand written CSS are summed apart.
import fs from "fs";
import { gzipSync } from "zlib";

const VENDOR = /bootstrap\.min\.css|bootstrap2-icons\.css|svg-icons\.css|montserrat\.css/;
const root = new URL("../../", import.meta.url).pathname;
const cov = {};
for (const dir of process.argv.slice(2)) for (const f of fs.readdirSync(dir)) {
  if (!f.startsWith("css-coverage@")) continue;
  for (const [url, c] of Object.entries(JSON.parse(fs.readFileSync(`${dir}/${f}`)))) {
    const t = cov[url] || (cov[url] = { size: c.size, ranges: [] });
    t.size = Math.max(t.size, c.size);
    t.ranges.push(...c.ranges);
  }
}
const kb = n => (n / 1024).toFixed(1).padStart(7);
const tot = { bootstrap: [0, 0, 0], hand: [0, 0, 0] };
console.log("      used     size  gzipped  share  file");
for (const [url, c] of Object.entries(cov).sort()) {
  c.ranges.sort((a, b) => a.start - b.start);
  const m = [];
  for (const r of c.ranges) { const l = m[m.length - 1]; if (l && r.start <= l.end) l.end = Math.max(l.end, r.end); else m.push({ ...r }); }
  const used = m.reduce((n, r) => n + r.end - r.start, 0);
  let gz = 0;
  if (url !== "<style>" && fs.existsSync(root + url)) gz = gzipSync(fs.readFileSync(root + url)).length;
  const k = /bootstrap\.min\.css/.test(url) ? "bootstrap" : VENDOR.test(url) ? null : "hand";
  if (k) { tot[k][0] += used; tot[k][1] += c.size; tot[k][2] += gz; }
  console.log(`${kb(used)} ${kb(c.size)} ${kb(gz)}  ${(100 * used / c.size).toFixed(0).padStart(4)}%  ${url}${url.endsWith(".css") ? "" : " (style block)"}`);
}
for (const [k, [u, s, g]] of Object.entries(tot)) console.log(`${kb(u)} ${kb(s)} ${kb(g)}  ${(100 * u / s).toFixed(0).padStart(4)}%  ${k} (KB)`);
