// node appshot.mjs <outdir> <user> name=page[|click|click...] ...   (ONLY=regex, DARK=1)
import { chromium } from "/var/www/emoncms/scripts/bootstrap5/node_modules/playwright/index.mjs";
import fs from "fs";
const [out, user, ...specs] = process.argv.slice(2);
const base = "http://localhost/emoncms";
fs.mkdirSync(out, { recursive: true });
const br = await chromium.launch({ executablePath: "/usr/bin/google-chrome" });
const p = await (await br.newContext({ viewport: { width: +(process.env.W||1280), height: 1000 }, reducedMotion: "reduce" })).newPage();
p.on("dialog", d => { console.log("  dialog", d.message().split("\n").slice(0,3).join(" / ")); d.dismiss(); });
p.on("pageerror", e => console.log("  err", e.message));
p.on("console", m => { if (m.type() === "error") console.log("  console", m.text().slice(0, 200)); });
await p.goto(base + "/", { waitUntil: "networkidle" });
await p.fill("input[name=username]", user); await p.fill("input[name=password]", user);
await Promise.all([p.waitForLoadState("networkidle"), p.click("#login")]);
for (const spec of specs) {
  const i = spec.indexOf("="), name = spec.slice(0, i), rest = spec.slice(i + 1); const [pg, ...clicks] = rest.split("|");
  await p.goto(`${base}/${pg}`, { waitUntil: "networkidle" });
  await p.mouse.move(1275, 995); await p.waitForTimeout(+(process.env.WAIT||5000));
  for (const c of clicks) { await p.evaluate(s => { const e = document.querySelector(s); if (!e) throw new Error("no " + s); e.click(); }, c).catch(e => console.log("  click", name, e.message.split("\n")[0])); await p.waitForTimeout(2000); }
  await p.mouse.move(1275, 995); await p.waitForTimeout(300);
  if (process.env.DARK) { await p.evaluate(() => document.documentElement.setAttribute("data-bs-theme", "dark")); await p.waitForTimeout(300); }
  await p.screenshot({ path: `${out}/${name}.png`, fullPage: true });
}
await br.close();
