// Drive page states (modals, selections) and record screenshot + layout dump for each.
// Usage: node states.mjs <outdir> <group> [width]
import { chromium } from "playwright";
import fs from "fs";

const [out, group, w] = process.argv.slice(2);
const width = +(w || 1280);
const base = process.env.EMONCMS_URL || "http://localhost/emoncms";

const GROUPS = {
  input: [
    { name: "input_list", page: "input/view", steps: [] },
    { name: "input_select", page: "input/view", steps: [{ click: ".input-select" }] },
    { name: "input_edit", page: "input/view", steps: [{ click: ".input-select" }, { click: ".input-edit" }] },
    { name: "input_delete", page: "input/view", steps: [{ click: ".input-select" }, { click: ".input-delete" }] },
    { name: "input_process", page: "input/view", steps: [{ click: "a[title='Configure Input processing']" }] },
    { name: "input_device", page: "input/view", steps: [{ click: "a[title='Configure device using device template']" }] },
    { name: "input_device_expand", page: "input/view", steps: [{ click: "a[title='Configure device using device template']" }, { click: ".category-heading .accordion-toggle" }, { click: ".group-heading .accordion-toggle" }] },
  ],
  feed: [
    { name: "feed_list", page: "feed/view", steps: [] },
    { name: "feed_select", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }] },
    { name: "feed_edit", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Edit']" }] },
    { name: "feed_delete", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Delete']" }] },
    { name: "feed_downsample", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Downsample']" }] },
    { name: "feed_download", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Download']" }] },
    { name: "feed_process", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Process config']" }] },
    { name: "feed_new", page: "feed/view", steps: [{ click: "#addnewfeed" }] },
    { name: "feed_import", page: "feed/view", steps: [{ click: "#importdata" }] },
  ],
  device: [
    { name: "device_list", page: "device/view", steps: [] },
    { name: "device_select", page: "device/view", steps: [{ click: ".group-list-row input[type=checkbox]" }] },
    { name: "device_config", page: "device/view", steps: [{ click: ".group-list-row a[title='Configure']" }] },
    { name: "device_init", page: "device/view", steps: [{ click: ".group-list-row a[title='Configure']" }, { dispatch: "#generate-template" }, { dispatch: "#prepare-custom-template" }] },
    { name: "device_delete", page: "device/view", steps: [{ click: ".group-list-row input[type=checkbox]" }, { click: "button[title='Delete']" }] },
    { name: "device_new", page: "device/view", steps: [{ click: "button[title='New device']" }] },
  ],
  // read-only: never click update, apply, start, stop or the user View (switches session)
  admin: [
    { name: "admin_log", page: "admin/log", steps: [] },
    { name: "admin_log_refresh", page: "admin/log", steps: [{ click: "#getlog" }] },
    { name: "admin_components", page: "admin/component", steps: [] },
    { name: "admin_components_custom", page: "admin/component", steps: [{ click: "button:has-text('Custom')" }] },
    { name: "admin_db", page: "admin/db", steps: [] },
    { name: "admin_serial", page: "admin/serial", steps: [{ wait: 2500 }] },
    { name: "admin_users", page: "admin/users", steps: [] },
    { name: "admin_users_add", page: "admin/users", steps: [{ click: "button:has-text('Add new user')" }] },
    { name: "admin_update", page: "admin/update", steps: [] },
    { name: "admin_update_hw", page: "admin/update", steps: [{ select: "#selected_hardware", index: 1 }] },
    { name: "admin_update_custom", page: "admin/update", steps: [{ select: "#selected_hardware", index: 1 }, { click: "#firmware_source_custom" }] },
  ],
  // modals are opened only, never confirmed
  user: [
    { name: "user_view", page: "user/view", steps: [] },
    { name: "user_edit_username", page: "user/view", steps: [{ dispatch: "tr:has-text('Username') .icon-pencil" }] },
    { name: "user_edit_email", page: "user/view", steps: [{ dispatch: "tr:has-text('Email') .icon-pencil" }] },
    { name: "user_edit_password", page: "user/view", steps: [{ dispatch: "tr:has-text('Password') .icon-pencil" }] },
    { name: "user_edit_timezone", page: "user/view", steps: [{ dispatch: "tr:has-text('Timezone') .icon-pencil" }] },
    { name: "user_edit_language", page: "user/view", steps: [{ dispatch: "tr:has-text('Language') .icon-pencil" }] },
    { name: "user_delete", page: "user/view", steps: [{ click: "button:has-text('Delete account')" }] },
    { name: "user_apikey", page: "user/view", steps: [{ click: "tr:has-text('Read Only API Key') button:has-text('Generate New')" }] },
  ],
  // admin login; never click view (switches session), the access cell or unlink
  account: [
    { name: "account_list", page: "account/list", steps: [{ wait: 1500 }] },
    { name: "account_add", page: "account/list", steps: [{ wait: 1500 }, { click: "#open-add-user-modal" }] },
    { name: "account_edit", page: "account/list", steps: [{ wait: 1500 }, { click: "td:has(.icon-pencil)" }] },
    { name: "account_filter", page: "account/list", steps: [{ wait: 1500 }, { fill: "#app input[type=text]", value: "test" }] },
  ],
  // test login, no linked accounts
  account_empty: [
    { name: "account_empty", page: "account/list", steps: [{ wait: 1500 }] },
  ],
  // never click New schedule (creates a record) or Save
  schedule: [
    { name: "schedule_list", page: "schedule/view", steps: [] },
    { name: "schedule_edit", page: "schedule/view", steps: [{ click: "button:has-text('Edit')" }] },
    { name: "schedule_edit_days", page: "schedule/view", steps: [{ click: "button:has-text('Edit')" }, { click: ".sb-presets button:has-text('M–F')" }] },
    { name: "schedule_custom", page: "schedule/view", steps: [{ click: "button:has-text('Edit')" }, { click: "a:has-text('Custom expression')" }] },
    { name: "schedule_help", page: "schedule/view", steps: [{ click: "button:has-text('Edit')" }, { click: "a:has-text('Custom expression')" }, { click: "button[title='Expression reference']" }] },
    { name: "schedule_delete", page: "schedule/view", steps: [{ click: "button:has-text('Delete')" }] },
    { name: "schedule_test", page: "schedule/view", steps: [{ click: "button:has-text('Test')" }] },
  ],
  // test login, dashboard 224 "bs5-test"; never click save, delete or New
  dashboard: [
    { name: "dash_list", page: "dashboard/list", steps: [] },
    { name: "dash_view", page: "dashboard/view?id=224", steps: [{ wait: 1500 }] },
    { name: "dash_edit", page: "dashboard/edit?id=224", steps: [{ wait: 1500 }] },
    { name: "dash_edit_select", page: "dashboard/edit?id=224", steps: [{ wait: 1500 }, { clickat: "#can", x: 220, y: 70 }] },
    { name: "dash_edit_options", page: "dashboard/edit?id=224", steps: [{ wait: 1500 }, { clickat: "#can", x: 220, y: 70 }, { click: "#options-button" }] },
    { name: "dash_edit_config", page: "dashboard/edit?id=224", steps: [{ wait: 1500 }, { click: "#dashboard-config-button" }] },
    { name: "dash_edit_menu", page: "dashboard/edit?id=224", steps: [{ wait: 1500 }, { click: "#widget-buttons .dropdown-toggle" }] },
  ],
  // never click the try buttons
  api: [
    { name: "api_main", page: "api", steps: [] },
    { name: "api_open", page: "api", steps: [{ click: ".endpoint-row" }] },
    { name: "api_filter", page: "api", steps: [{ fill: ".ref-filter input", value: "feed" }] },
    { name: "api_input", page: "input/api", steps: [] },
    { name: "api_feed", page: "feed/api", steps: [] },
    { name: "api_device", page: "device/api", steps: [] },
    { name: "api_process", page: "process/api", steps: [] },
    { name: "api_schedule", page: "schedule/api", steps: [] },
  ],
  embed: [
    { name: "embed_graph", page: "graph/embed?feedidsLH=623", steps: [{ wait: 1500 }] },
  ],
  graph: [
    { name: "graph_empty", page: "graph", steps: [] },
    { name: "graph_view", page: "graph/623", steps: [{ wait: 1500 }] },
    { name: "graph_manual", page: "graph/623", steps: [{ click: "button[title='Select time window']" }] },
    { name: "graph_config", page: "graph/623", steps: [{ click: "button:has-text('Feed Config')" }] },
    { name: "graph_stats", page: "graph/623", steps: [{ click: "button:has-text('Feed Stats')" }] },
    { name: "graph_csv", page: "graph/623", steps: [{ click: "button:has-text('CSV Export')" }] },
    { name: "graph_editor", page: "graph/623", steps: [{ click: "button:has-text('Editor')" }] },
  ],
};

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
await p.goto(base + "/", { waitUntil: "networkidle" });
await p.fill("input[name=username]", process.env.EMONCMS_USER);
await p.fill("input[name=password]", process.env.EMONCMS_PASS);
await Promise.all([p.waitForLoadState("networkidle"), p.click("#login")]);

const report = {};
for (const st of GROUPS[group]) {
  errors = [];
  await p.goto(`${base}/${st.page}`, { waitUntil: "networkidle" });
  await p.waitForTimeout(1200);
  const missing = [];
  for (const s of st.steps) {
    if (s.wait) await p.waitForTimeout(s.wait);
    if (s.click) {
      const loc = p.locator(s.click).first();
      if (await loc.count()) { await loc.click(); await p.waitForTimeout(900); }
      else missing.push(s.click);
    }
    if (s.clickat) {
      const loc = p.locator(s.clickat).first();
      // mouse events sent to the element, so an overlay cannot take the click
      if (await loc.count()) {
        const b = await loc.boundingBox();
        const init = { bubbles: true, clientX: b.x + s.x, clientY: b.y + s.y, button: 0 };
        for (const type of ["mousedown", "mouseup", "click"]) await loc.dispatchEvent(type, init);
        await p.waitForTimeout(900);
      }
      else missing.push(s.clickat);
    }
    if (s.fill) {
      const loc = p.locator(s.fill).first();
      if (await loc.count()) { await loc.fill(s.value); await p.waitForTimeout(900); }
      else missing.push(s.fill);
    }
    if (s.select) {
      const loc = p.locator(s.select).first();
      if (await loc.count()) { await loc.selectOption({ index: s.index }); await p.waitForTimeout(900); }
      else missing.push(s.select);
    }
    // click event without pointer hit test, for buttons under an overlay
    if (s.dispatch) {
      const loc = p.locator(s.dispatch).first();
      if (await loc.count()) { await loc.dispatchEvent("click"); await p.waitForTimeout(900); }
      else missing.push(s.dispatch);
    }
  }
  await p.screenshot({ path: `${out}/${st.name}@${width}.png`, fullPage: group === "graph" });
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
console.log(JSON.stringify(report));
await br.close();
