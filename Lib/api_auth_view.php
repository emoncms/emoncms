<?php

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  ---------------------------------------------------------------------
  Shared "Your API keys" and "Three ways to authenticate" cards.
  Used by the site API docs page and the input/feed module API pages.
  Relies on the api_copy() helper defined by Lib/api_explorer_view.php,
  which is always included on the same pages.

  */

  defined('EMONCMS_EXEC') or die('Restricted access');
  global $path;

  // $apikeys is passed down from the controller via session_apikeys()
  $auth_logged_in = $apikeys['logged_in'];
  $auth_apikey_read = $auth_logged_in ? $apikeys['read'] : "";
  $auth_apikey_write = $auth_logged_in ? $apikeys['write'] : "";
?>
<style>
  .api-auth-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
  }

  .api-auth-card {
    background-color: #2e2e2e;
    border: 1px solid #3f3f3f;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .api-auth-card h3 { margin: 0; font-size: 20px; line-height: 28px; color: #fff; }

  .api-auth-card p { margin: 0; color: #ccc; font-size: 15px; line-height: 22px; }
  .api-auth-card p strong { color: #fff; }
  .api-auth-card a { color: #44b3e2; text-decoration: none; font-weight: bold; }
  .api-auth-card a:hover { color: #6ec6ea; }

  .api-auth-card .api-auth-muted { color: #999; font-size: 14px; line-height: 21px; }

  .api-auth-title { display: flex; align-items: center; gap: 12px; }

  .api-auth-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(68, 179, 226, 0.12);
    color: #44b3e2;
    flex-shrink: 0;
  }

  .api-auth-keyrow { display: flex; gap: 8px; align-items: center; }

  .api-auth-keyrow input[type=text] {
    flex-grow: 1;
    font-family: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace;
    font-size: 13px;
    color: #ddd;
    background-color: #1a1a1a;
    border: 1px solid #3f3f3f;
    border-radius: 6px;
    padding: 8px 12px;
    height: auto;
    margin: 0;
    box-shadow: none;
  }

  .api-auth-row { display: flex; align-items: center; gap: 12px; }

  .api-auth-mono {
    font-family: "JetBrains Mono", ui-monospace, "SF Mono", Menlo, monospace;
    font-size: 13px;
    color: #ccc;
  }
  .api-auth-mono .hl { color: #fff; }

  .api-auth-tag {
    font-size: 11px;
    font-weight: bold;
    color: #999;
    background-color: #2a2a2a;
    border: 1px solid #3f3f3f;
    padding: 3px 8px;
    border-radius: 4px;
    width: 100px;
    text-align: center;
    flex-shrink: 0;
  }

  .api-auth-tag.recommended {
    color: #44b3e2;
    background-color: rgba(68, 179, 226, 0.15);
    border-color: transparent;
  }

  @media (max-width: 900px) {
    .api-auth-grid { grid-template-columns: minmax(0, 1fr); }
  }
</style>

<div class="api-auth-grid">

  <div class="api-auth-card">
    <div class="api-auth-title">
      <span class="api-auth-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.78 7.78 5.5 5.5 0 0 1 7.78-7.78zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
      </span>
      <h3><?php echo tr('Your API keys'); ?></h3>
    </div>
    <?php if ($auth_logged_in) { ?>
    <div style="display:flex; flex-direction:column; gap:6px">
      <span class="api-auth-muted"><?php echo tr('Read only access'); ?></span>
      <div class="api-auth-keyrow">
        <input type="text" readonly value="<?php echo $auth_apikey_read; ?>" onclick="this.select()">
        <button class="copy-btn" onclick="api_copy('<?php echo $auth_apikey_read; ?>',this)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15V5a2 2 0 0 1 2-2h10"></path></svg></button>
      </div>
    </div>
    <div style="display:flex; flex-direction:column; gap:6px">
      <span class="api-auth-muted"><?php echo tr('Full Read &amp; Write access'); ?></span>
      <div class="api-auth-keyrow">
        <input type="text" readonly value="<?php echo $auth_apikey_write; ?>" onclick="this.select()">
        <button class="copy-btn" onclick="api_copy('<?php echo $auth_apikey_write; ?>',this)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15V5a2 2 0 0 1 2-2h10"></path></svg></button>
      </div>
    </div>
    <?php } else { ?>
    <p><?php echo tr('There are two API keys: a <strong>read only</strong> key, safe to use in dashboards and shared links, and a <strong>read &amp; write</strong> key that lets devices post data to your account.'); ?></p>
    <p><a href="<?php echo $path; ?>user/login"><?php echo tr('Log in or create an account'); ?></a> <?php echo tr('to get your keys.'); ?></p>
    <?php } ?>
  </div>

  <div class="api-auth-card">
    <div class="api-auth-title">
      <span class="api-auth-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
      </span>
      <h3><?php echo tr('Three ways to authenticate'); ?></h3>
    </div>
    <div style="display:flex; flex-direction:column; gap:10px">
      <div class="api-auth-row">
        <span class="api-auth-tag recommended"><?php echo tr('RECOMMENDED'); ?></span>
        <span class="api-auth-mono"><?php echo tr('POST body:'); ?> <span class="hl">apikey=APIKEY</span></span>
      </div>
      <div class="api-auth-row">
        <span class="api-auth-tag"><?php echo tr('HEADER'); ?></span>
        <span class="api-auth-mono">Authorization: Bearer APIKEY</span>
      </div>
      <div class="api-auth-row">
        <span class="api-auth-tag"><?php echo tr('URL'); ?></span>
        <span class="api-auth-mono">&amp;apikey=APIKEY</span>
      </div>
    </div>
    <p class="api-auth-muted"><?php echo tr('No HTTPS on your device? Emoncms also supports AES-128-CBC encrypted posting using your API key as a pre-shared key. See the reference below.'); ?></p>
  </div>
</div>
