// Page states per group, used by states.mjs and abcss.mjs.
// Steps: click, dispatch, clickat, select, fill, wait (see README).
export const GROUPS = {
  input: [
    { name: "input_list", page: "input/view", steps: [] },
    { name: "input_select", page: "input/view", steps: [{ click: ".input-select" }] },
    { name: "input_edit", page: "input/view", steps: [{ click: ".input-select" }, { click: ".input-edit" }] },
    { name: "input_delete", page: "input/view", steps: [{ click: ".input-select" }, { click: ".input-delete" }] },
    { name: "input_process", page: "input/view", steps: [{ click: "a[title='Configure Input processing']" }] },
    { name: "input_device", page: "input/view", steps: [{ click: "a[title='Configure device using device template']" }] },
    { name: "input_device_expand", page: "input/view", steps: [{ click: "a[title='Configure device using device template']" }, { click: ".category-heading .tpl-toggle" }, { click: ".group-heading .tpl-toggle" }] },
  ],
  feed: [
    { name: "feed_list", page: "feed/view", steps: [] },
    { name: "feed_select", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }] },
    { name: "feed_edit", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Edit']" }] },
    { name: "feed_delete", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Delete']" }] },
    { name: "feed_downsample", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Downsample']" }] },
    { name: "feed_download", page: "feed/view", steps: [{ click: ".group-list-row .feed-select" }, { click: "button[title='Download']" }] },
    { name: "feed_process", page: "feed/view", steps: [{ click: ".group-list-row:has-text('VIRTUAL') .feed-select" }, { click: "button[title='Process config']" }] },
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
    { name: "user_edit_username", page: "user/view", steps: [{ dispatch: ".panel-row:has-text('Username') .svg-icon-pencil" }] },
    { name: "user_edit_email", page: "user/view", steps: [{ dispatch: ".panel-row:has-text('Email') .svg-icon-pencil" }] },
    { name: "user_edit_password", page: "user/view", steps: [{ dispatch: ".panel-row:has-text('Password') .svg-icon-pencil" }] },
    { name: "user_edit_timezone", page: "user/view", steps: [{ dispatch: ".panel-row:has-text('Timezone') .svg-icon-pencil" }] },
    { name: "user_edit_language", page: "user/view", steps: [{ dispatch: ".panel-row:has-text('Language') .svg-icon-pencil" }] },
    { name: "user_delete", page: "user/view", steps: [{ click: "button:has-text('Delete account')" }] },
    { name: "user_apikey", page: "user/view", steps: [{ click: ".panel-row:has-text('Read Only API Key') button:has-text('Generate New')" }] },
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
    { name: "schedule_edit_days", page: "schedule/view", steps: [{ click: "button:has-text('Edit')" }, { click: ".btn-group button:has-text('M–F')" }] },
    { name: "schedule_custom", page: "schedule/view", steps: [{ click: "button:has-text('Edit')" }, { click: "a:has-text('Custom expression')" }] },
    { name: "schedule_help", page: "schedule/view", steps: [{ click: "button:has-text('Edit')" }, { click: "a:has-text('Custom expression')" }, { click: "button[title='Expression reference']" }] },
    { name: "schedule_delete", page: "schedule/view", steps: [{ click: "button[title='Delete']" }] },
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
  // logged out; never submit the forms
  login: [
    { name: "login_page", page: "user/login", steps: [] },
    { name: "login_required", page: "feed/view", steps: [] },
    { name: "login_reset", page: "user/login", steps: [{ dispatch: "#passwordreset-link" }] },
    { name: "login_reset_back", page: "user/login", steps: [{ dispatch: "#passwordreset-link" }, { dispatch: "#passwordreset-link-cancel" }] },
    { name: "login_reset_confirm", page: "user/passwordreset-confirm?token=invalid", steps: [] },
  ],
  // admin login; read-only clicks only
  modules_admin: [
    { name: "mod_backup", page: "backup", steps: [{ wait: 1000 }] },
    { name: "mod_network", page: "network", steps: [{ wait: 1500 }] },
    { name: "mod_setup", page: "setup", steps: [{ wait: 1500 }] },
    { name: "mod_config", page: "config", steps: [{ wait: 1500 }] },
    { name: "mod_config_editor", page: "config", steps: [{ wait: 1500 }, { click: "#show-editor" }] },
    { name: "mod_config_level", page: "config", steps: [{ wait: 1500 }, { click: "#log-level .dropdown-toggle" }] },
  ],
  // test login
  modules_user: [
    { name: "mod_sync", page: "sync", steps: [{ wait: 1500 }] },
    { name: "mod_postprocess", page: "postprocess", steps: [{ wait: 1000 }] },
    { name: "mod_postprocess_new", page: "postprocess", steps: [{ wait: 1000 }, { select: "#app select", index: 1 }] },
    { name: "mod_emailreport", page: "emailreport", steps: [{ wait: 1000 }] },
  ],
  // test login; never click create, save, delete or the config ok buttons
  app: [
    { name: "app_list", page: "app/list", steps: [{ wait: 1000 }] },
    { name: "app_list_edit", page: "app/list", steps: [{ wait: 1000 }, { click: "button:has-text('Edit')" }] },
    { name: "app_new", page: "app/new", steps: [] },
    { name: "app_new_archived", page: "app/new", steps: [{ click: ".app-group-toggle" }] },
    { name: "app_new_modal", page: "app/new", steps: [{ click: ".app-item" }] },
    { name: "app_myelectricflow", page: "app/view?id=122", steps: [{ wait: 2500 }] },
    { name: "app_myelectricflow_manual", page: "app/view?id=122", steps: [{ wait: 2500 }, { click: "#time-manual-open" }] },
    { name: "app_myelectricflow_picker", page: "app/view?id=122", steps: [{ wait: 2500 }, { click: "#time-manual-open" }, { click: "#request-start + .dtp-host .dtp-toggle" }] },
    { name: "app_myelectricflow_config", page: "app/view?id=122", steps: [{ wait: 2500 }, { dispatch: ".config-open" }] },
    { name: "app_myheatpump", page: "app/view?id=126", steps: [{ wait: 3000 }] },
    { name: "app_myheatpump_power", page: "app/view?id=126", steps: [{ wait: 3000 }, { click: ".bargraph-day" }, { wait: 1500 }] },
    { name: "app_myheatpump_config", page: "app/view?id=126", steps: [{ wait: 3000 }, { dispatch: ".config-open" }] },
    { name: "app_timeofuse2", page: "app/view?id=140", steps: [{ wait: 2500 }] },
    { name: "app_timeofuse2_config", page: "app/view?id=140", steps: [{ wait: 2500 }, { dispatch: ".config-open" }] },
    { name: "app_myelectric2", page: "app/view?id=141", steps: [{ wait: 2500 }] },
    { name: "app_myelectric2_cost", page: "app/view?id=141", steps: [{ wait: 2500 }, { click: ".viewcostenergy" }] },
    { name: "app_myelectric2_config", page: "app/view?id=141", steps: [{ wait: 2500 }, { dispatch: ".config-open" }] },
    { name: "app_myboiler", page: "app/view?id=181", steps: [{ wait: 3000 }] },
    { name: "app_myboiler_config", page: "app/view?id=181", steps: [{ wait: 3000 }, { dispatch: ".config-open" }] },
    { name: "app_ukgrid", page: "app/view?id=182", steps: [{ wait: 3000 }] },
    { name: "app_ukgrid_fuelmix", page: "app/view?id=182", steps: [{ wait: 3000 }, { click: ".fuelmix" }, { wait: 1500 }] },
    { name: "app_ukgrid_config", page: "app/view?id=182", steps: [{ wait: 3000 }, { dispatch: ".config-open" }] },
    { name: "app_co2monitor", page: "app/view?id=201", steps: [{ wait: 2500 }] },
    { name: "app_co2monitor_decay", page: "app/view?id=201", steps: [{ wait: 2500 }, { dispatch: "#decay_mode" }] },
    { name: "app_co2monitor_config", page: "app/view?id=201", steps: [{ wait: 2500 }, { dispatch: ".config-open" }] },
  ],
  // admin login; never click Simulate
  app_admin: [
    { name: "app_solarbatterysim", page: "app/view?id=131", steps: [{ wait: 3000 }] },
    { name: "app_solarbatterysim_config", page: "app/view?id=131", steps: [{ wait: 3000 }, { dispatch: ".config-open" }] },
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

// Run the steps of one state on page p. Returns selectors that were not found.
export async function runSteps(p, steps) {
  const missing = [];
  for (const s of steps) {
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
  return missing;
}
