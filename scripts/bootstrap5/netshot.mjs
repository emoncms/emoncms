// node netshot.mjs <outdir> : network list with injected networks and an open row, page and wizard
import { chromium } from "/var/www/emoncms/scripts/bootstrap5/node_modules/playwright/index.mjs";
import fs from "fs";
const [out] = process.argv.slice(2);
fs.mkdirSync(out, { recursive: true });
const base = "http://localhost/emoncms";
const br = await chromium.launch({ executablePath: "/usr/bin/google-chrome" });
const p = await (await br.newContext({ viewport: { width: 1280, height: 1000 } })).newPage();
p.on("dialog", d => { console.log("  dialog", d.message().slice(0,150)); d.dismiss(); });
p.on("pageerror", e => console.log("  err", e.message));
await p.goto(base + "/", { waitUntil: "networkidle" });
await p.fill("input[name=username]", "admin"); await p.fill("input[name=password]", "admin");
await Promise.all([p.waitForLoadState("networkidle"), p.click("#login")]);
for (const [name, pg] of [["netlist", "network"], ["setuplist", "setup"]]) {
  await p.goto(base + "/" + pg, { waitUntil: "networkidle" }); await p.waitForTimeout(2500);
  await p.evaluate(() => {
    const vm = window.app;
    vm.scan_for_networks = () => {};
    vm.setup_stage = 2; vm.wifi_client_mode = "list";
    vm.available_networks = [{SSID:"HomeNet", level:4, SIGNAL:82, SECURITY:"WPA2"}, {SSID:"Guest", level:2, SIGNAL:41, SECURITY:""}, {SSID:"Neighbour", level:1, SIGNAL:18, SECURITY:"WPA2"}];
    vm.selected_SSID = "HomeNet";
  });
  await p.waitForTimeout(500);
  await p.screenshot({ path: `${out}/${name}.png`, fullPage: true });
}
await br.close();
