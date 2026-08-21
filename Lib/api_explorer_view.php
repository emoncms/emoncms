<?php

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  ---------------------------------------------------------------------
  Shared API reference explorer.

  Renders a grouped, filterable, interactive endpoint reference from the
  structured definitions in input_api_obj.php / feed_api_obj.php.
  Used by the combined API docs page (api, or site/api where the emoncms.org
  site module is installed, see api_docs_route) and by input/api and feed/api.

  Expects (via view()):
  - $api:            array of endpoint definitions, each with a 'module' key
  - $title:          heading, e.g. "Input API"
  - $sub:            optional subheading text
  - $show_docs_link: optional, adds a button linking to the combined getting
                     started page
  - $standalone:     optional, renders as a complete page: constrained width
                     with the API keys and authentication cards on top
                     (used by the input/api, feed/api, device/api and
                     process/api pages)
  - $extra:          optional path of a view rendered below the authentication
                     cards, for module specific documentation that is not tied
                     to a single endpoint (used by device/api)

  The explorer always renders with the dark site palette, defined as CSS
  variables on .api-explorer, matching the emoncms.org site pages.

  */

  defined('EMONCMS_EXEC') or die('Restricted access');
  global $path, $session;

  // api_docs_route(): where this install serves the combined API docs pages
  require_once "Lib/api_docs_export.php";
  $docs_route = $path.api_docs_route();

  // $apikeys is passed in by the controller via session_apikeys()
  $explorer_logged_in = $apikeys['logged_in'];
  $explorer_apikey_read = $explorer_logged_in ? $apikeys['read'] : false;
  $explorer_apikey_write = $explorer_logged_in ? $apikeys['write'] : false;

  // Public profile pages can read public feeds without an apikey
  $explorer_public = !empty($session['public_userid']);
  $explorer_can_read = !empty($session['read']) || $explorer_public;

  $feed_list_path = "feed/list.json";
  if ($explorer_public && !empty($session['public_username'])) {
      $feed_list_path = $session['public_username']."/feed/list.json";
  }

  if (!isset($sub)) $sub = tr('Every endpoint, generated from the live server code');
  $standalone = !empty($standalone);

  load_js("Lib/js/vue.global.prod-3.5.22.min.js");
?>
<style>
  body { background-color: #222; }

  /* The explorer always uses the dark site palette, on every page */
  .api-explorer {
    --bg-card: #2e2e2e;
    --border-card: #3f3f3f;
    --border: #333;
    --text-primary: #fff;
    --text-body: #ccc;
    --text-muted: #999;
    --bg-input: #1a1a1a;
    --bg-badge: #2a2a2a;
    --bg-card-row-hover: #383838;
    --accent: #44b3e2;
    --accent-bg: rgba(68, 179, 226, 0.15);
    --accent-border: rgba(68, 179, 226, 0.3);
    --exp-write: #e7a944;
    --exp-write-bg: rgba(231, 169, 68, 0.15);
    --exp-public: #5cb85c;
    --exp-public-bg: rgba(92, 184, 92, 0.15);
    --exp-response: #9ecbdd;
  }

  .api-explorer [v-cloak] { display: none; }

  .api-explorer .mono {
    font-family: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace;
  }

  .api-explorer a { color: var(--accent, #2a8fc7); text-decoration: none; font-weight: bold; }
  .api-explorer a:hover { text-decoration: underline; }

  .api-muted { color: var(--text-muted, #666) !important; font-size: 14px !important; line-height: 21px !important; }

  .copy-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 6px;
    background-color: var(--bg-badge, #efefef);
    color: var(--text-body, #000);
    padding: 8px 10px;
    cursor: pointer;
    font-size: 12px;
    flex-shrink: 0;
  }
  .copy-btn:hover { background-color: var(--bg-card-row-hover, #f5f5f5); color: var(--text-primary, #000); }

  .code-block {
    font-family: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace;
    font-size: 12.5px;
    line-height: 20px;
    color: var(--text-body, #000);
    background-color: var(--bg-input, #fafafa);
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 8px;
    padding: 13px 16px;
    word-break: break-all;
  }

  .code-block .hl { color: var(--text-primary, #000); font-weight: bold; }
  .code-block .key { color: var(--accent, #2a8fc7); }

  /* Standalone page container for the module API pages */
  .api-page {
    max-width: 1150px;
    margin: 0 auto;
    padding: 28px 20px 64px 20px;
    display: flex;
    flex-direction: column;
    gap: 28px;
  }

  .ref-header { display: flex; align-items: center; justify-content: space-between; gap: 16px 24px; flex-wrap: wrap; }
  .ref-header h2 { margin: 0; font-size: 26px; line-height: 34px; color: var(--text-primary, #000); }
  .ref-header .ref-sub { color: var(--text-muted, #666) !important; font-size: 15px !important; line-height: 22px !important; margin: 2px 0 0 0 !important; }

  .ref-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

  .ref-docs-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: var(--bg-card, #fff);
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    color: var(--text-body, #000) !important;
    font-weight: normal !important;
    white-space: nowrap;
  }
  .ref-docs-btn:hover { background-color: var(--bg-card-row-hover, #f5f5f5); color: var(--text-primary, #000) !important; text-decoration: none !important; }

  .ref-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    background-color: var(--bg-card, #fff);
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 8px;
    padding: 4px 14px;
    width: 300px;
    color: var(--text-muted, #666);
  }

  .ref-filter input[type=text] {
    flex-grow: 1;
    background: none;
    border: none;
    box-shadow: none;
    color: var(--text-body, #000);
    font-size: 14px;
    margin: 0;
    padding: 6px 0;
    height: auto;
  }
  .ref-filter input[type=text]:focus { outline: none; }

  .ref-filter .filter-key {
    color: var(--text-muted, #666);
    font-size: 12px;
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 4px;
    padding: 1px 6px;
  }

  .ref-layout { display: flex; gap: 28px; align-items: flex-start; }

  .ref-sidebar {
    width: 250px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 22px;
    font-size: 14px;
    position: sticky;
    top: 60px;
  }

  .ref-sidebar .module-label {
    font-size: 12px;
    font-weight: bold;
    color: var(--text-muted, #666);
    letter-spacing: 1px;
    padding: 0 12px 6px 12px;
  }

  .ref-sidebar .group-link {
    color: var(--text-body, #000);
    padding: 7px 12px;
    border-radius: 6px;
    cursor: pointer;
  }

  .ref-sidebar .group-link:hover { background-color: var(--bg-card-row-hover, #f5f5f5); color: var(--text-primary, #000); }

  .ref-sidebar .group-link.active {
    color: var(--text-primary, #000);
    background-color: var(--accent-bg, rgba(42, 143, 199, 0.10));
    font-weight: bold;
  }

  .ref-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-top: 1px solid var(--border-card, #e0e0e0);
    padding: 16px 12px 0 12px;
  }

  .ref-main { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; gap: 12px; }

  .ref-group-heading {
    font-size: 13px;
    font-weight: bold;
    color: var(--text-muted, #666);
    letter-spacing: 1px;
    text-transform: uppercase;
    margin: 16px 0 0 0;
    scroll-margin-top: 60px;
  }
  .ref-group-heading:first-child { margin-top: 0; }

  .badge {
    font-size: 11px;
    font-weight: bold;
    padding: 3px 8px;
    border-radius: 4px;
    flex-shrink: 0;
  }

  .badge-get { color: var(--accent, #2a8fc7); background-color: var(--accent-bg, rgba(42, 143, 199, 0.10)); width: 38px; text-align: center; }
  .badge-post { color: var(--exp-write); background-color: var(--exp-write-bg); width: 38px; text-align: center; }
  .badge-read { color: var(--accent, #2a8fc7); background-color: var(--accent-bg, rgba(42, 143, 199, 0.10)); }
  .badge-write { color: var(--exp-write); background-color: var(--exp-write-bg); }
  .badge-public { color: var(--exp-public); background-color: var(--exp-public-bg); }

  .endpoint {
    background-color: var(--bg-card, #fff);
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 10px;
  }

  .endpoint.open { border-color: var(--accent-border, rgba(42, 143, 199, 0.35)); }

  .endpoint-row {
    padding: 14px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    cursor: pointer;
  }

  .endpoint.open .endpoint-row { border-bottom: 1px solid var(--border-card, #e0e0e0); }

  .endpoint-row .path { font-family: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace; font-size: 14px; color: var(--text-primary, #000); }
  .endpoint-row .desc { font-size: 14px; color: var(--text-muted, #666); flex-grow: 1; min-width: 0; }
  .endpoint-row .chevron { color: var(--text-muted, #666); flex-shrink: 0; transition: transform 0.15s ease-out; }
  .endpoint.open .endpoint-row .chevron { transform: rotate(180deg); }

  .endpoint-body { padding: 20px; display: flex; flex-direction: column; gap: 18px; }

  .param-table { display: flex; flex-direction: column; }

  .param-row {
    display: grid;
    grid-template-columns: 130px 190px 1fr;
    gap: 14px;
    padding: 9px 0;
    border-bottom: 1px solid var(--border, #e0e0e0);
    align-items: center;
  }
  .param-row:last-child { border-bottom: none; }

  .param-row.param-head { padding: 8px 0; }
  .param-row.param-head span { font-size: 12px; font-weight: bold; color: var(--text-muted, #666); letter-spacing: 0.5px; }

  .param-name { font-family: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace; font-size: 13px; color: var(--text-primary, #000); word-break: break-all; }
  .param-desc { font-size: 13px; color: var(--text-body, #000); line-height: 19px; }
  .param-desc .mono { font-size: 12px; }

  .param-row input[type=text], .param-row select {
    width: 100%;
    box-sizing: border-box;
    font-size: 13px;
    color: var(--text-body, #000);
    background-color: var(--bg-input, #fafafa);
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 6px;
    padding: 6px 10px;
    height: auto;
    margin: 0;
    box-shadow: none;
  }

  .request-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
  .request-header .req-label { font-size: 12px; font-weight: bold; color: var(--text-muted, #666); letter-spacing: 0.5px; }
  .request-buttons { display: flex; gap: 8px; }

  .try-btn {
    font-size: 12px;
    font-weight: bold;
    color: #fff;
    background-color: var(--accent, #2a8fc7);
    border: none;
    border-radius: 5px;
    padding: 5px 14px;
    cursor: pointer;
  }
  .try-btn:hover { opacity: 0.85; }
  .try-btn:disabled { background-color: var(--border-card, #e0e0e0); color: var(--text-muted, #666); cursor: not-allowed; opacity: 1; }

  .response-block {
    font-family: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace;
    font-size: 12.5px;
    line-height: 20px;
    color: var(--exp-response);
    background-color: var(--bg-input, #fafafa);
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 8px;
    padding: 13px 16px;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 300px;
    overflow-y: auto;
    margin: 0;
  }

  .endpoint-notes {
    font-size: 13px !important;
    line-height: 20px !important;
    color: var(--text-muted, #666) !important;
    background-color: var(--bg-badge, #efefef);
    border: 1px solid var(--border-card, #e0e0e0);
    border-radius: 8px;
    padding: 12px 16px;
    margin: 0;
  }

  @media (max-width: 900px) {
    .ref-layout { flex-direction: column; }
    .ref-sidebar { position: static; width: 100%; flex-direction: row; flex-wrap: wrap; gap: 6px; }
    .ref-sidebar .module-label { width: 100%; padding-bottom: 0; }
    .ref-sidebar .group-link { border: 1px solid var(--border-card, #e0e0e0); }
    .ref-legend { display: none; }
    .param-row { grid-template-columns: 110px 1fr; }
    .param-row .param-desc { grid-column: 1 / -1; }
    .endpoint-row { flex-wrap: wrap; }
  }
</style>

<?php if ($standalone) { ?>
<div class="api-page">

  <div class="ref-header" style="margin-bottom:4px">
    <div>
      <h2 style="font-size:26px; line-height:34px; margin:0; color:#fff"><?php echo $title; ?></h2>
      <p style="color:#999; font-size:15px; line-height:22px; margin:2px 0 0 0"><?php echo $sub; ?></p>
    </div>
    <?php if (!empty($show_docs_link)) { ?>
    <a class="ref-docs-btn" href="<?php echo $docs_route; ?>" style="color:#ccc; font-weight:normal; text-decoration:none">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      <?php echo tr('Getting started guide'); ?>
    </a>
    <?php } ?>
  </div>

  <?php echo view("Lib/api_auth_view.php", array('apikeys'=>$apikeys)); ?>

  <?php if (!empty($extra)) echo view($extra, array('apikeys'=>$apikeys)); ?>

<?php } ?>
<div id="api-reference" class="api-explorer" v-cloak>

  <div style="display:flex; flex-direction:column; gap:28px">

    <div class="ref-header" id="reference" style="scroll-margin-top:60px">
      <div>
        <?php if ($standalone) { ?>
        <h2 style="font-size:20px; line-height:28px"><?php echo tr('Reference'); ?></h2>
        <?php } else { ?>
        <h2><?php echo $title; ?></h2>
        <p class="ref-sub"><?php echo $sub; ?></p>
        <?php } ?>
      </div>
      <div class="ref-actions">
        <?php if (!empty($show_docs_link) && !$standalone) { ?>
        <a class="ref-docs-btn" href="<?php echo $docs_route; ?>">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          <?php echo tr('Getting started guide'); ?>
        </a>
        <?php } ?>
        <div class="ref-filter">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.5-4.5"></path></svg>
          <input type="text" ref="filterbox" v-model="filter" placeholder="<?php echo tr('Filter endpoints...'); ?>">
          <span class="mono filter-key">/</span>
        </div>
      </div>
    </div>

    <div class="ref-layout">

      <!-- Sidebar -->
      <div class="ref-sidebar">
        <div v-for="module in modules" style="display:flex; flex-direction:column; gap:2px">
          <span class="module-label" v-if="modules.length > 1">{{ module.name.toUpperCase() }} API</span>
          <span v-for="group in module.groups" class="group-link" :class="{active: active_group==group.anchor}" @click="goto_group(group.anchor)">{{ group.name }}</span>
        </div>
        <div class="ref-legend">
          <span class="module-label" style="padding:0"><?php echo tr('KEY REQUIRED'); ?></span>
          <div style="display:flex; gap:8px; flex-wrap:wrap">
            <span class="badge badge-read">read</span>
            <span class="badge badge-write">write</span>
            <span class="badge badge-public">public ok</span>
          </div>
        </div>
      </div>

      <!-- Endpoint list -->
      <div class="ref-main">
        <template v-for="module in modules">
          <template v-for="group in module.groups">
            <h3 class="ref-group-heading" :id="group.anchor"><template v-if="modules.length > 1">{{ module.name }} &middot; </template>{{ group.name }}</h3>
            <div v-for="e in group.endpoints" class="endpoint" :class="{open: e.open}">

              <div class="endpoint-row" @click="toggle(e)">
                <span class="badge" :class="e.http=='post' ? 'badge-post' : 'badge-get'">{{ e.http=='post' ? 'POST' : 'GET' }}</span>
                <span class="path">{{ e.path }}</span>
                <span class="desc">{{ e.description }}</span>
                <span class="badge" :class="e.mode=='write' ? 'badge-write' : 'badge-read'">{{ e.mode }}</span>
                <span v-if="e.public" class="badge badge-public">public ok</span>
                <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"></path></svg>
              </div>

              <div v-if="e.open" class="endpoint-body">

                <p v-if="e.notes" class="endpoint-notes">{{ e.notes }}</p>

                <div v-if="param_names(e).length" class="param-table">
                  <div class="param-row param-head">
                    <span><?php echo tr('PARAMETER'); ?></span>
                    <span><?php echo tr('VALUE'); ?></span>
                    <span><?php echo tr('DESCRIPTION'); ?></span>
                  </div>
                  <div v-for="name in param_names(e)" class="param-row">
                    <span class="param-name">{{ name }}</span>
                    <span>
                      <select v-if="e.parameters[name].type=='feed' && feeds.length" v-model="e.values[name]" @change="param_changed(e)">
                        <optgroup v-for="(node_feeds, nodename) in nodes" :label="nodename">
                          <option v-for="f in node_feeds" :value="f.id">{{ f.name }}</option>
                        </optgroup>
                      </select>
                      <select v-else-if="e.parameters[name].type=='bool'" v-model="e.values[name]" @change="param_changed(e)">
                        <option value="0"><?php echo tr('No'); ?></option>
                        <option value="1"><?php echo tr('Yes'); ?></option>
                      </select>
                      <select v-else-if="e.parameters[name].type=='select'" v-model="e.values[name]" @change="param_changed(e)">
                        <option v-for="option in e.parameters[name].options">{{ option }}</option>
                      </select>
                      <input v-else type="text" v-model="e.values[name]" @input="param_changed(e)">
                    </span>
                    <span class="param-desc">{{ e.parameters[name].description || '' }}</span>
                  </div>
                </div>

                <div v-if="!e.notry" style="display:flex; flex-direction:column; gap:8px">
                  <div class="request-header">
                    <span class="req-label"><?php echo tr('REQUEST'); ?></span>
                    <div class="request-buttons">
                      <button class="copy-btn" @click="copy_url(e, $event)"><?php echo tr('Copy'); ?></button>
                      <button class="copy-btn" @click="copy_curl(e, $event)">curl</button>
                      <button class="try-btn" @click="try_endpoint(e)" :disabled="!can_try(e)" :title="can_try(e) ? '' : '<?php echo tr('Log in to try'); ?>'"><?php echo tr('Try it'); ?></button>
                    </div>
                  </div>
                  <div class="code-block">{{ display_url(e) }}<span class="key" v-if="e.http!='post' && apikey_for(e)">{{ has_query(e) ? '&' : '?' }}apikey={{ key_trunc(e) }}&hellip;</span><template v-if="e.http=='post'"><br><?php echo tr('POST body:'); ?> {{ display_body(e) }}<span class="key" v-if="apikey_for(e)">&amp;apikey={{ key_trunc(e) }}&hellip;</span></template></div>
                </div>

                <div v-if="e.loading || e.response!==null" style="display:flex; flex-direction:column; gap:8px">
                  <span class="request-header"><span class="req-label"><?php echo tr('RESPONSE'); ?><span v-if="e.status">: {{ e.status }}</span></span></span>
                  <pre class="response-block">{{ e.loading ? '...' : e.response }}</pre>
                </div>
              </div>
            </div>
          </template>
        </template>

        <p v-if="!modules.length" class="api-muted" style="text-align:center; padding:24px 0"><?php echo tr('No endpoints match your filter'); ?></p>
      </div>
    </div>
  </div>
</div>
<?php if ($standalone) { ?>

  <!-- Machine readable renditions, also linked from /llms.txt. The link text
       survives HTML to markdown conversion, so AI tools that fetch this page
       without running javascript can find the full reference. -->
  <p style="text-align:center; color:#999; font-size:14px; line-height:21px; margin:0">
    <?php echo tr('Using an AI assistant to write API client code? Point it at the machine readable reference:'); ?>
    <a href="<?php echo $docs_route; ?>.md" style="color:#44b3e2; text-decoration:none; font-weight:bold"><?php echo api_docs_route(); ?>.md</a> &middot;
    <a href="<?php echo $docs_route; ?>.json" style="color:#44b3e2; text-decoration:none; font-weight:bold"><?php echo api_docs_route(); ?>.json</a> &middot;
    <a href="<?php echo $path; ?>llms.txt" style="color:#44b3e2; text-decoration:none; font-weight:bold">llms.txt</a>
  </p>
</div>
<?php } ?>

<script>
// ---------------------------------------------------------------------
// Clipboard helper, also used by pages that embed this explorer
// ---------------------------------------------------------------------
function api_copy(text, button) {
    var done = function() {
        if (!button) return;
        var original = button.innerHTML;
        button.innerHTML = "<?php echo tr('Copied!'); ?>";
        setTimeout(function() { button.innerHTML = original; }, 1200);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done);
    } else {
        var textarea = document.createElement("textarea");
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        done();
    }
}

// ---------------------------------------------------------------------
// Reference explorer
// ---------------------------------------------------------------------
var apikey_read = <?php echo json_encode($explorer_apikey_read); ?>;
var apikey_write = <?php echo json_encode($explorer_apikey_write); ?>;
var explorer_can_read = <?php echo json_encode($explorer_can_read); ?>;
var explorer_public = <?php echo json_encode($explorer_public); ?>;
var feed_list_path = <?php echo json_encode($feed_list_path); ?>;
var api = <?php echo json_encode($api); ?>;

// Every endpoint that needs the write key is confirmed before it runs, so that
// "Try it" can never quietly delete, rename or overwrite something. Endpoints
// that remove or overwrite existing data warn more strongly: a definition can
// opt in to that with "destructive" => true as well as by path.
var destructive_paths = /delete|clear|trim|scalerange|clean|reset/;

var now = Math.round(Date.now() * 0.001);

for (var i in api) {
    api[i].open = false;
    api[i].loading = false;
    api[i].response = null;
    api[i].status = "";
    api[i].values = {};
    for (var p in api[i].parameters) {
        var def = api[i].parameters[p].default;
        if (p == "start" && !def) def = now - 3600;
        if (p == "end" && !def) def = now;
        if (p == "time" && !def) def = now;
        api[i].values[p] = (def === undefined) ? "" : def;
    }
}

var app = Vue.createApp({
    data() { return {
        api: api,
        feeds: [],
        nodes: {},
        filter: "",
        active_group: "",
        debounce: null
    }; },
    computed: {
        // Endpoints filtered and grouped: [{name:module, groups:[{name, anchor, endpoints}]}]
        modules: function() {
            var filter = this.filter.toLowerCase();
            var modules = [];
            var module_index = {};
            for (var i in this.api) {
                var e = this.api[i];
                if (filter != "" && e.path.toLowerCase().indexOf(filter) == -1 && e.description.toLowerCase().indexOf(filter) == -1) continue;

                if (module_index[e.module] == undefined) {
                    module_index[e.module] = modules.length;
                    modules.push({name: e.module, groups: [], group_index: {}});
                }
                var module = modules[module_index[e.module]];
                if (module.group_index[e.group] == undefined) {
                    module.group_index[e.group] = module.groups.length;
                    module.groups.push({name: e.group, anchor: this.anchor(e.module, e.group), endpoints: []});
                }
                module.groups[module.group_index[e.group]].endpoints.push(e);
            }
            return modules;
        }
    },
    methods: {
        anchor: function(module, group) {
            return "ref-" + module + "-" + group.toLowerCase().replace(/[^a-z0-9]+/g, "-");
        },
        param_names: function(e) {
            return Object.keys(e.parameters);
        },
        apikey_for: function(e) {
            return e.mode == "write" ? apikey_write : apikey_read;
        },
        can_try: function(e) {
            if (this.apikey_for(e) !== false) return true;
            // Public profile pages can try read endpoints without a key
            return e.mode == "read" && explorer_public;
        },
        // Readable request preview without apikey or url-encoding
        display_url: function(e) {
            var parts = [];
            for (var p in e.parameters) {
                if (e.parameters[p].post) continue;
                parts.push(p + "=" + e.values[p]);
            }
            return path + e.path + (parts.length ? "?" + parts.join("&") : "");
        },
        display_body: function(e) {
            var parts = [];
            for (var p in e.parameters) {
                if (e.parameters[p].post) parts.push(p + "=" + e.values[p]);
            }
            return parts.join("&");
        },
        has_query: function(e) {
            for (var p in e.parameters) {
                if (!e.parameters[p].post) return true;
            }
            return false;
        },
        key_trunc: function(e) {
            return this.apikey_for(e) ? this.apikey_for(e).substr(0, 8) : "";
        },
        query_string: function(e, include_post_params) {
            var parts = [];
            for (var p in e.parameters) {
                if (!include_post_params && e.parameters[p].post) continue;
                parts.push(p + "=" + encodeURIComponent(e.values[p]));
            }
            return parts.length ? "?" + parts.join("&") : "";
        },
        full_url: function(e) {
            var url = path + e.path + this.query_string(e, e.http != "post");
            if (this.apikey_for(e)) {
                url += (url.indexOf("?") == -1 ? "?" : "&") + "apikey=" + this.apikey_for(e);
            }
            return url;
        },
        copy_url: function(e, event) {
            api_copy(this.full_url(e), event.target);
        },
        copy_curl: function(e, event) {
            var curl;
            if (e.http == "post") {
                var body = [];
                for (var p in e.parameters) {
                    if (e.parameters[p].post) body.push(p + "=" + encodeURIComponent(e.values[p]));
                }
                if (this.apikey_for(e)) body.push("apikey=" + this.apikey_for(e));
                curl = 'curl --data "' + body.join("&") + '" "' + path + e.path + this.query_string(e, false) + '"';
            } else {
                curl = 'curl "' + this.full_url(e) + '"';
            }
            api_copy(curl, event.target);
        },
        toggle: function(e) {
            e.open = !e.open;
            // Fetch a live response straight away for read endpoints
            if (e.open && e.mode == "read" && !e.notry && this.can_try(e) && e.response === null) {
                this.try_endpoint(e);
            }
        },
        param_changed: function(e) {
            if (e.mode != "read" || e.notry || !this.can_try(e)) return;
            var self = this;
            clearTimeout(this.debounce);
            this.debounce = setTimeout(function() { self.try_endpoint(e); }, 500);
        },
        is_destructive: function(e) {
            return e.destructive === true || destructive_paths.test(e.path);
        },
        // Shown before running a write endpoint, so it is clear what is about
        // to change and against which account
        confirm_message: function(e) {
            var request = this.display_url(e);
            if (e.http == "post") request += "\n<?php echo tr('POST body:'); ?> " + this.display_body(e);
            var warning = this.is_destructive(e)
                ? "<?php echo tr('This permanently changes data in your account and cannot be undone:'); ?>"
                : "<?php echo tr('This writes to your account:'); ?>";
            return warning + "\n\n" + request + "\n\n<?php echo tr('Are you sure you want to continue?'); ?>";
        },
        try_endpoint: function(e) {
            if (!this.can_try(e) || e.notry) return;
            if (e.mode == "write" && !confirm(this.confirm_message(e))) return;
            e.loading = true;
            e.status = "";

            var request;
            if (e.http == "post") {
                var body = new URLSearchParams();
                for (var p in e.parameters) {
                    if (e.parameters[p].post) body.append(p, e.values[p]);
                }
                if (this.apikey_for(e)) body.append("apikey", this.apikey_for(e));
                request = fetch(path + e.path + this.query_string(e, false), {method: "POST", body: body});
            } else {
                request = fetch(this.full_url(e));
            }

            request.then(function(response) {
                e.status = response.status + " " + response.statusText;
                return response.text();
            }).then(function(text) {
                try { text = JSON.stringify(JSON.parse(text), null, 2); } catch (error) {}
                if (text.length > 3000) text = text.substr(0, 3000) + "\n... <?php echo tr('truncated'); ?>";
                e.response = text;
                e.loading = false;
            }).catch(function(error) {
                e.response = "" + error;
                e.loading = false;
            });
        },
        goto_group: function(anchor) {
            this.active_group = anchor;
            var element = document.getElementById(anchor);
            if (element) element.scrollIntoView({behavior: "smooth"});
        }
    },
    mounted: function() {
        var self = this;

        // Feed selector options for parameters of type "feed"
        if (explorer_can_read) {
            fetch(path + feed_list_path).then(function(r) { return r.json(); }).then(function(feeds) {
                if (!Array.isArray(feeds)) return;
                self.feeds = feeds;
                var nodes = {};
                for (var i in feeds) {
                    var tag = feeds[i].tag || "";
                    if (nodes[tag] == undefined) nodes[tag] = [];
                    nodes[tag].push(feeds[i]);
                }
                self.nodes = nodes;
                // Default every feed parameter to the first feed
                if (feeds.length) {
                    for (var i in self.api) {
                        for (var p in self.api[i].parameters) {
                            if (self.api[i].parameters[p].type == "feed") self.api[i].values[p] = feeds[0].id;
                        }
                    }
                }
            }).catch(function() {});
        }

        // "/" focuses the filter box
        document.addEventListener("keydown", function(event) {
            if (event.key == "/" && document.activeElement.tagName != "INPUT" && document.activeElement.tagName != "TEXTAREA") {
                event.preventDefault();
                self.$refs.filterbox.focus();
            }
        });

        // Legacy #input / #feed links jump to that module's first group;
        // #ref-* group anchors are used directly
        var hash = window.location.hash.replace("#", "");
        if (hash == "input" || hash == "feed") {
            var target = "reference";
            for (var i in this.modules) {
                if (this.modules[i].name == hash && this.modules[i].groups.length) {
                    target = this.modules[i].groups[0].anchor;
                    break;
                }
            }
            hash = target;
        }
        if (hash) {
            setTimeout(function() {
                var element = document.getElementById(hash);
                if (element) element.scrollIntoView();
            }, 100);
        }
    }
}).mount('#api-reference');
</script>
