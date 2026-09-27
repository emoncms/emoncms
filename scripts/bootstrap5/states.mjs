// Drive page states (modals, selections) and record screenshot + layout dump for each.
// Usage: node states.mjs <outdir> <group> [width]
import { chromium } from "playwright";
import fs from "fs";
import { GROUPS, runSteps } from "./groups.mjs";

const [out, group, w] = process.argv.slice(2);
const width = +(w || 1280);
const base = process.env.EMONCMS_URL || "http://localhost/emoncms";

const PROPS = ["display", "position", "float", "box-sizing", "width", "height",
  "margin-top", "margin-right", "margin-bottom", "margin-left",
  "padding-top", "padding-right", "padding-bottom", "padding-left",
  "border-top-width", "border-bottom-width", "font-family", "font-size", "font-weight", "line-height",
  "color", "background-color", "text-decoration-line", "border-radius"];

fs.mkdirSync(out, { recursive: true });
const br = await chromium.launch({ executablePath: process.env.CHROME || "/usr/bin/google-chrome" });
const ctx = await br.newContext({ viewport: { width, height: 900 } });
const p = await ctx.newPage();
let errors = [];
p.on("pageerror", e => errors.push(String(e.message)));
p.on("console", m => { if (m.type() === "error") errors.push(m.text()); });
p.on("response", r => { if (r.status() >= 400) errors.push("HTTP " + r.status() + " " + r.url()); });
// CSS coverage over the whole run, including the login page
await p.coverage.startCSSCoverage({ resetOnNavigation: false });
// groups that run logged out
const LOGGED_OUT = ["login"];
if (!LOGGED_OUT.includes(group)) {
  await p.goto(base + "/", { waitUntil: "networkidle" });
  await p.fill("input[name=username]", process.env.EMONCMS_USER);
  await p.fill("input[name=password]", process.env.EMONCMS_PASS);
  await Promise.all([p.waitForLoadState("networkidle"), p.click("#login")]);
}

const report = {};
for (const st of GROUPS[group]) {
  errors = [];
  await p.goto(`${base}/${st.page}`, { waitUntil: "networkidle" });
  await p.waitForTimeout(1200);
  const missing = await runSteps(p, st.steps);
  await p.screenshot({ path: `${out}/${st.name}@${width}.png`, fullPage: group === "graph" || group.startsWith("app") });
  const data = await p.evaluate(PROPS => {
    const res = [];
    const path = el => { const parts = []; for (let e = el; e && e.nodeType === 1 && e !== document.documentElement; e = e.parentElement) {
      let i = 0; for (let s = e; (s = s.previousElementSibling);) if (s.tagName === e.tagName) i++;
      parts.unshift(e.tagName.toLowerCase() + (e.id ? "#" + e.id : "") + "[" + i + "]"); } return parts.join(">"); };
    for (const el of document.querySelectorAll("body *")) {
      if (["SCRIPT", "STYLE", "LINK", "META"].includes(el.tagName)) continue;
      const r = el.getBoundingClientRect(); const cs = getComputedStyle(el);
      if (cs.display === "none") continue;
      const st = {}; for (const k of PROPS) st[k] = cs.getPropertyValue(k);
      res.push({ path: path(el), cls: typeof el.className === "string" ? el.className : "",
        text: (el.childNodes[0] && el.childNodes[0].nodeType === 3 ? el.childNodes[0].textContent.trim().slice(0, 30) : ""),
        rect: [r.x, r.y, r.width, r.height].map(v => Math.round(v * 10) / 10), st });
    }
    return res;
  }, PROPS);
  fs.writeFileSync(`${out}/${st.name}@${width}.json`, JSON.stringify(data));
  report[st.name] = { errors, missing, elements: data.length };
}
fs.writeFileSync(`${out}/report@${width}.json`, JSON.stringify(report, null, 1));
// used byte ranges per stylesheet, merged across page loads. Inline style blocks have no url.
const cov = {};
for (const e of await p.coverage.stopCSSCoverage()) {
  const key = e.url ? e.url.replace(base + "/", "").replace(/\?.*/, "") : "<style>";
  const c = cov[key] || (cov[key] = { size: 0, ranges: [] });
  c.size = Math.max(c.size, Buffer.byteLength(e.text));
  c.ranges.push(...e.ranges);
}
for (const c of Object.values(cov)) {
  c.ranges.sort((a, b) => a.start - b.start);
  const m = [];
  for (const r of c.ranges) { const l = m[m.length - 1]; if (l && r.start <= l.end) l.end = Math.max(l.end, r.end); else m.push({ ...r }); }
  c.ranges = m;
  c.used = m.reduce((n, r) => n + r.end - r.start, 0);
}
fs.writeFileSync(`${out}/css-coverage@${width}.json`, JSON.stringify(cov));
console.log(JSON.stringify(report));
await br.close();
