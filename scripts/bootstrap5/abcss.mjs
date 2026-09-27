// Compare two builds of bootstrap.min.css on the same page states.
// Each state is captured with the served stylesheet (A), then with the <link> swapped to b.css (B),
// then swapped back (A2). Elements that change between A and A2 (live values, timers) are ignored.
// Usage: node abcss.mjs <b.css> <outdir> <user|-> <group...>
// Password is EMONCMS_PASS or the user name. "-" runs logged out. W sets the width (default 1280).
import { chromium } from "playwright";
import fs from "fs";
import path from "path";
import { GROUPS, runSteps } from "./groups.mjs";

const [bcss, out, user, ...groups] = process.argv.slice(2);
const base = process.env.EMONCMS_URL || "http://localhost/emoncms";
const width = +(process.env.W || 1280);
const bfile = path.resolve(bcss);
fs.mkdirSync(out, { recursive: true });

const br = await chromium.launch({ executablePath: process.env.CHROME || "/usr/bin/google-chrome" });
const ctx = await br.newContext({ viewport: { width, height: 900 }, reducedMotion: "reduce" });
const p = await ctx.newPage();
await p.route("**/__ab/b.css", r => r.fulfill({ path: bfile, contentType: "text/css" }));

if (user !== "-") {
  await p.goto(base + "/", { waitUntil: "networkidle" });
  await p.fill("input[name=username]", user);
  await p.fill("input[name=password]", process.env.EMONCMS_PASS || user);
  await Promise.all([p.waitForLoadState("networkidle"), p.click("#login")]);
}

// Replace the Bootstrap <link> in place, so cascade order is kept
const swap = (href) => p.evaluate(href => new Promise(res => {
  const l = document.querySelector("link[data-ab], link[href*='bootstrap5/css/bootstrap.min.css']");
  if (!l) return res(false);
  if (!l.dataset.ab) l.dataset.ab = l.href;
  const n = l.cloneNode();
  n.href = href || l.dataset.ab;
  n.onload = () => { l.remove(); requestAnimationFrame(() => requestAnimationFrame(() => res(true))); };
  l.after(n);
}), href);

// Computed style of every element and its ::before and ::after, kept in the page
const snap = (key) => p.evaluate(key => {
  const m = new Map();
  const ser = cs => { let s = ""; for (let i = 0; i < cs.length; i++) s += cs[i] + ":" + cs.getPropertyValue(cs[i]) + ";"; return s; };
  for (const el of document.querySelectorAll("*")) {
    const r = el.getBoundingClientRect();
    m.set(el, [ser(getComputedStyle(el)), ser(getComputedStyle(el, "::before")), ser(getComputedStyle(el, "::after")),
      [r.x, r.y, r.width, r.height].map(v => Math.round(v * 10) / 10).join(",")]);
  }
  (window.__ab = window.__ab || {})[key] = m;
}, key);

// Elements where B differs from A while A equals A2, with the changed properties
const compare = () => p.evaluate(() => {
  const { A, B, A2 } = window.__ab;
  const name = el => el.tagName.toLowerCase() + (el.id ? "#" + el.id : "") + (typeof el.className === "string" && el.className.trim() ? "." + el.className.trim().split(/\s+/).join(".") : "");
  const props = s => Object.fromEntries(s.split(";").filter(Boolean).map(d => { const i = d.indexOf(":"); return [d.slice(0, i), d.slice(i + 1)]; }));
  const res = [], removed = new Set(); let noise = 0;
  for (const [el, a] of A) {
    const b = B.get(el), a2 = A2.get(el);
    if (!b || !a2) continue;
    if (a.join("|") !== a2.join("|")) { noise++; continue; }
    if (a.join("|") === b.join("|")) continue;
    const diff = {};
    ["", "::before", "::after"].forEach((pe, i) => {
      if (a[i] === b[i]) return;
      const pa = props(a[i]), pb = props(b[i]);
      for (const k of new Set([...Object.keys(pa), ...Object.keys(pb)])) {
        if (pa[k] === pb[k]) continue;
        // custom property no longer defined, counted apart
        if (k.startsWith("--") && pb[k] === undefined) { removed.add(k); continue; }
        diff[pe + k] = [pa[k], pb[k]];
      }
    });
    if (a[3] !== b[3]) diff.rect = [a[3], b[3]];
    if (!Object.keys(diff).length) continue;
    let path = name(el); for (let e = el.parentElement; e && e !== document.body && path.length < 200; e = e.parentElement) path = name(e) + " > " + path;
    res.push({ el: path, diff });
  }
  delete window.__ab;
  return { noise, removed: [...removed], diffs: res };
});

const pixels = (fa, fb) => p.evaluate(async ([x, y]) => {
  const L = s => new Promise(r => { const i = new Image(); i.onload = () => r(i); i.src = "data:image/png;base64," + s; });
  const [i, j] = await Promise.all([L(x), L(y)]);
  if (i.width != j.width || i.height != j.height) return -1;
  const c = new OffscreenCanvas(i.width, i.height), g = c.getContext("2d");
  g.drawImage(i, 0, 0); const d1 = g.getImageData(0, 0, i.width, i.height).data;
  g.clearRect(0, 0, i.width, i.height); g.drawImage(j, 0, 0); const d2 = g.getImageData(0, 0, i.width, i.height).data;
  let n = 0; for (let k = 0; k < d1.length; k += 4) if (d1[k] != d2[k] || d1[k + 1] != d2[k + 1] || d1[k + 2] != d2[k + 2]) n++;
  return n;
}, [fs.readFileSync(fa).toString("base64"), fs.readFileSync(fb).toString("base64")]);

const report = {};
for (const group of groups) for (const st of GROUPS[group]) {
  await p.goto(`${base}/${st.page}`, { waitUntil: "networkidle" });
  await p.waitForTimeout(1200);
  const missing = await runSteps(p, st.steps);
  await p.mouse.move(width - 5, 895);
  const f = n => `${out}/${st.name}.${n}.png`;
  await snap("A"); await p.screenshot({ path: f("a") });
  if (!await swap(p.url().replace(/^(https?:\/\/[^/]+).*/, "$1") + "/__ab/b.css")) { console.log(st.name.padEnd(28), "no bootstrap link"); continue; }
  await snap("B"); await p.screenshot({ path: f("b") });
  await swap(null);
  await snap("A2"); await p.screenshot({ path: f("a2") });
  const { noise, removed, diffs } = await compare();
  const px = await pixels(f("a"), f("b")), pxNoise = await pixels(f("a"), f("a2"));
  report[st.name] = { missing, noise, removed, px, pxNoise, diffs };
  console.log(st.name.padEnd(28), `style diffs ${String(diffs.length).padStart(3)}  px ${px} (noise ${pxNoise}, ${noise} el)`, removed.length ? `vars removed ${removed.length}` : "", missing.length ? "missing " + missing.join(", ") : "");
  if (!diffs.length && px === pxNoise) for (const n of ["a", "b", "a2"]) fs.unlinkSync(f(n));
}
fs.writeFileSync(`${out}/report.json`, JSON.stringify(report, null, 1));
await br.close();
