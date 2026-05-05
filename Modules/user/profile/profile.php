<?php
/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/
// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');
load_js("Modules/user/profile/md5.js");
load_js("Lib/misc/clipboard.js");
load_js("Modules/user/user.js");

?>

<div id="app" v-cloak>

    <div class="page-header">
      <h2><?php echo tr('My Account'); ?></h2>
    </div>

    <!-- ── Account card ───────────────────────────────────── -->
    <div class="card">
      <div class="card-header">
        <span class="card-accent"></span>
        <span class="card-name"><?php echo tr('Account'); ?></span>
      </div>
      <table>
        <colgroup>
          <col class="col-w-label">
          <col>
          <col class="col-w-action">
          <col class="col-w-btn">
        </colgroup>
        <tbody>
          <tr>
            <td class="col-secondary"><?php echo tr('User ID'); ?></td>
            <td class="col-primary">{{ user.id }}</td>
            <td></td>
            <td><button class="app-btn app-btn-sm app-btn-danger" @click="delete_account()"><?php echo tr('Delete account'); ?></button></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Username'); ?></td>
            <td>
              <span v-if="!edit.username">{{ user.username }}</span>
              <div v-else class="inline-edit">
                <input type="text" v-model="user.username"/>
                <button class="app-btn app-btn-sm" @click="save_username(user.username)"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.username" @click="show_edit('username')"></i></td>
            <td></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Email'); ?></td>
            <td>
              <span v-if="!edit.email">{{ user.email }}</span>
              <div v-else class="inline-edit">
                <input type="text" v-model="user.email"/>
                <button class="app-btn app-btn-sm" @click="save_email(user.email)"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.email" @click="show_edit('email')"></i></td>
            <td></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Read & Write API Key'); ?></td>
            <td><div class="apikey">{{ user.apikey_write }}</div></td>
            <td class="col-action"><i class="icon-white icon-share row-action" @click="copy_text_to_clipboard(user.apikey_write,'<?php echo addslashes(tr("Write API Key copied to clipboard")); ?>')"></i></td>
            <td><button class="app-btn app-btn-sm" @click="new_apikey('write')"><?php echo tr('Generate New'); ?></button></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Read Only API Key'); ?></td>
            <td><div class="apikey">{{ user.apikey_read }}</div></td>
            <td class="col-action"><i class="icon-white icon-share row-action" @click="copy_text_to_clipboard(user.apikey_read,'<?php echo addslashes(tr("Read API Key copied to clipboard")); ?>')"></i></td>
            <td><button class="app-btn app-btn-sm" @click="new_apikey('read')"><?php echo tr('Generate New'); ?></button></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Password'); ?></td>
            <td>
              <span v-if="!edit.password" class="col-secondary">**********</span>
              <div v-else class="password-form">
                <div class="pw-field">
                  <span class="pw-label"><?php echo tr('Current password'); ?></span>
                  <input type="password" v-model="password.current"/>
                </div>
                <div class="pw-field">
                  <span class="pw-label"><?php echo tr('New password'); ?></span>
                  <input type="password" v-model="password.new"/>
                </div>
                <div class="pw-field">
                  <span class="pw-label"><?php echo tr('Repeat new password'); ?></span>
                  <input type="password" v-model="password.repeat"/>
                </div>
                <div class="pw-buttons">
                  <button class="app-btn app-btn-sm" @click="change_password()"><?php echo tr('Save'); ?></button>
                  <button class="app-btn app-btn-sm" @click="edit.password=false"><?php echo tr('Cancel'); ?></button>
                </div>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.password" @click="show_edit('password')"></i></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── Profile card ───────────────────────────────────── -->
    <div class="card">
      <div class="card-header">
        <span class="card-accent"></span>
        <span class="card-name"><?php echo tr('Profile'); ?></span>
      </div>
      <table>
        <colgroup>
          <col class="col-w-label">
          <col>
          <col class="col-w-action">
        </colgroup>
        <tbody>
          <tr>
            <td class="col-secondary"><?php echo tr('Gravatar'); ?></td>
            <td>
              <img v-if="!edit.gravatar" style="border: 1px solid #444; border-radius: 3px; padding:2px" :src="'https://www.gravatar.com/avatar/'+gravatar_hash(user.gravatar)" />
              <div v-else class="inline-edit">
                <input type="text" style="width:220px !important" v-model="user.gravatar"/>
                <button class="app-btn app-btn-sm" @click="save('gravatar')"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.gravatar" @click="show_edit('gravatar')"></i></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Name'); ?></td>
            <td>
              <span v-if="!edit.name">{{ user.name }}</span>
              <div v-else class="inline-edit">
                <input type="text" v-model="user.name"/>
                <button class="app-btn app-btn-sm" @click="save('name')"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.name" @click="show_edit('name')"></i></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Location'); ?></td>
            <td>
              <span v-if="!edit.location">{{ user.location }}</span>
              <div v-else class="inline-edit">
                <input type="text" v-model="user.location"/>
                <button class="app-btn app-btn-sm" @click="save('location')"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.location" @click="show_edit('location')"></i></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Timezone'); ?></td>
            <td>
              <span v-if="!edit.timezone">{{ user.timezone }}</span>
              <div v-else class="inline-edit">
                <select v-model="user.timezone">
                  <option v-for="tz in timezones" :value="tz.id">{{ tz.id }} {{ tz.gmt_offset_text }}</option>
                </select>
                <button class="app-btn app-btn-sm" @click="save('timezone')"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.timezone" @click="show_edit('timezone')"></i></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Language'); ?></td>
            <td>
              <span v-if="!edit.language">{{ languages[user.language] }}</span>
              <span class="col-secondary" style="margin-left:12px; font-size:11px" v-if="!edit.language && translation_status[user.language]!=undefined"><?php echo tr("Translation: "); ?>{{ translation_status[user.language].prc_complete }}% <?php echo tr("complete"); ?></span>
              <div v-if="edit.language" class="inline-edit">
                <select v-model="user.language">
                  <option value="en_GB" selected>English (United Kingdom)</option>
                  <option v-for="(name,code) in languages" :value="code" v-if="code!='en_GB'">{{ name }}</option>
                </select>
                <button class="app-btn app-btn-sm" @click="save('language')"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.language" @click="show_edit('language')"></i></td>
          </tr>
          <tr>
            <td class="col-secondary"><?php echo tr('Starting page'); ?></td>
            <td>
              <span v-if="!edit.startingpage">{{ user.startingpage }}</span>
              <div v-else class="inline-edit">
                <input type="text" v-model="user.startingpage"/>
                <button class="app-btn app-btn-sm" @click="save('startingpage')"><i class="icon-white icon-ok"></i></button>
              </div>
            </td>
            <td class="col-action"><i class="icon-white icon-pencil row-action" v-if="!edit.startingpage" @click="show_edit('startingpage')"></i></td>
          </tr>
        </tbody>
      </table>
    </div>

</div><!-- end Vue #app -->

<!-- ── Appearance card (non-Vue) ──────────────────────── -->
  <div class="card">
    <div class="card-header">
      <span class="card-accent"></span>
      <span class="card-name"><?php echo tr('Appearance'); ?></span>
    </div>
    <table>
      <colgroup>
        <col class="col-w-label">
        <col>
      </colgroup>
      <tbody>
        <tr>
          <td class="col-secondary"><?php echo tr('Theme colour'); ?></td>
          <td>
            <div class="color-box themecolor" name="blue"    style="background-color:#44b3e2"></div>
            <div class="color-box themecolor" name="black"   style="background-color:#555"></div>
            <div class="color-box themecolor" name="sun"     style="background-color:#ffbe14"></div>
            <div class="color-box themecolor" name="yellow2" style="background-color:#dfc72d"></div>
            <div class="color-box themecolor" name="copper"  style="background-color:#e28743"></div>
            <div class="color-box themecolor" name="green"   style="background-color:#4eaa05"></div>
          </td>
        </tr>
        <tr>
          <td class="col-secondary"><?php echo tr('Sidebar colour'); ?></td>
          <td>
            <div class="color-box sidebarcolor" name="dark"  style="background-color:#333"></div>
            <div class="color-box sidebarcolor" name="light" style="background-color:#eee"></div>
          </td>
        </tr>
        <tr>
          <td class="col-secondary"><?php echo tr('Colour scheme'); ?></td>
          <td>
            <div class="mode-toggle">
              <button class="mode-toggle-btn" id="mode-btn-dark"><?php echo tr('Dark'); ?></button>
              <button class="mode-toggle-btn" id="mode-btn-light"><?php echo tr('Light'); ?></button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>


<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo tr('WARNING deleting an account is permanent'); ?></h3>
    </div>
    <div class="modal-body">
        <div class="delete-account-s1">
        <p><?php echo tr('Are you sure you want to delete your account?'); ?></p>
        </div>

        <div class="delete-account-s2" style="display:none">
        <p><b><?php echo tr('Your account has been successfully deleted.'); ?></b></p>
        </div>
        
        <pre id="deleteall-output"></pre>
        
        <div class="delete-account-s1">
            <p><?php echo tr('Confirm password to delete:'); ?><br>
            <input id="delete-account-password" type="password" /></p>
        </div>
    </div>
    <div class="modal-footer">
        <button id="canceldelete" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo tr('Delete permanently'); ?></button>
        <button id="logoutdelete" class="btn btn-primary" style="display:none"><?php echo tr('Logout'); ?></button>
    </div>
</div>

<div id="modalNewApikey" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="modalNewApikeyLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="modalNewApikeyLabel"><?php echo tr('Generate a new API key'); ?> - <span id="apikey_type"></span></h3>
    </div>
    <div class="modal-body">
        <p><?php echo tr('Are you sure you want to generate a new apikey?'); ?></p>
        <p><?php echo tr("All devices using the current key will need to be updated with the new key."); ?></p>
    </div>
    <div class="modal-footer">
        <button id="cancel_generate_apikey" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
        <button id="confirm_generate_apikey" class="btn btn-primary"><?php echo tr('Generate'); ?></button>
    </div>
</div>

<script>
var languages = <?php echo json_encode(get_available_languages_with_names()); ?>;
var translation_status = <?php echo json_encode(get_translation_status()); ?>;
var str_passwords_do_not_match = "<?php echo tr('Passwords do not match'); ?>";
</script>
<?php load_js("Modules/user/profile/profile.js"); ?>