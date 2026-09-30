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

// view() only brings $path into scope, and the gravatar hash below is rendered
// from the session user's own address
global $session;

load_css("Modules/user/profile/profile.css");
load_js("Lib/js/clipboard.js");
load_js("Lib/js/qrcode.js");
load_js("Modules/user/user.js");
load_js("Lib/js/vue.global.prod-3.5.22.min.js");
?>

<?php
// Key and value row with inline edit: value, then field with Save and Cancel
function profile_edit_row($key, $label, $save, $field) {
    echo '<div class="panel-row" :class="{\'is-editing\': edit.'.$key.'}">
      <div class="row-key">'.$label.'</div>
      <div class="row-value">
        <span v-if="!edit.'.$key.'">{{ user.'.$key.' }}</span>
        <div v-else class="profile-edit">
          '.$field.'
          <button class="btn btn-primary btn-sm" @click="'.$save.'">'.tr('Save').'</button>
          <button class="btn btn-default btn-sm" @click="cancel_edit(\''.$key.'\')">'.tr('Cancel').'</button>
        </div>
      </div>
      <span v-if="!edit.'.$key.'" class="row-action svg-icon-pencil" title="'.tr('Edit').'" @click="show_edit(\''.$key.'\')"></span>
    </div>';
}
function profile_text_field($key, $save, $width = "input-220") {
    return '<input class="form-control '.$width.'" type="text" v-model="user.'.$key.'" @keyup.enter="'.$save.'"/>';
}
?>
<div class="panel-page profile-page">
<div id="app" v-cloak>
  <div class="page-header">
    <h3><?php echo tr('My Account'); ?></h3>
  </div>

  <div class="panel">
    <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo tr('Account'); ?></span></div>
    <div class="panel-row">
      <div class="row-key"><?php echo tr('User ID'); ?></div>
      <div class="row-value">{{ user.id }}</div>
    </div>
    <?php profile_edit_row('username', tr('Username'), 'save_username(user.username)', profile_text_field('username', 'save_username(user.username)')); ?>
    <div class="panel-row" :class="{'is-editing': edit.email}">
      <div class="row-key"><?php echo tr('Email'); ?></div>
      <div class="row-value">
        <span v-if="!edit.email">{{ user.email }}</span>
        <div v-else class="profile-edit">
          <input class="form-control input-220" type="text" v-model="user.email"/>
          <input class="form-control input-220" type="password" v-model="email_password" placeholder="<?php echo htmlspecialchars(tr('Current password'), ENT_QUOTES, 'UTF-8'); ?>" @keyup.enter="save_email(user.email)"/>
          <button class="btn btn-primary btn-sm" @click="save_email(user.email)"><?php echo tr('Save'); ?></button>
          <button class="btn btn-default btn-sm" @click="cancel_edit('email')"><?php echo tr('Cancel'); ?></button>
        </div>
      </div>
      <span v-if="!edit.email" class="row-action svg-icon-pencil" title="<?php echo tr('Edit'); ?>" @click="show_edit('email')"></span>
    </div>
    <div class="panel-row" :class="{'is-editing': edit.password}">
      <div class="row-key"><?php echo tr('Password'); ?></div>
      <div class="row-value">
        <span v-if="!edit.password" class="text-muted">**********</span>
        <div v-else class="profile-password">
          <label class="form-label"><?php echo tr('Current password'); ?></label>
          <input class="form-control input-220 mb-2" type="password" v-model="password.current" />
          <label class="form-label"><?php echo tr('New password'); ?></label>
          <input class="form-control input-220 mb-2" type="password" v-model="password.new" />
          <label class="form-label"><?php echo tr('Repeat new password'); ?></label>
          <input class="form-control input-220 mb-3" type="password" v-model="password.repeat" @keyup.enter="change_password()" />
          <div>
            <button class="btn btn-primary btn-sm" @click="change_password()"><?php echo tr('Save'); ?></button>
            <button class="btn btn-default btn-sm" @click="cancel_edit('password')"><?php echo tr('Cancel'); ?></button>
          </div>
        </div>
      </div>
      <span v-if="!edit.password" class="row-action svg-icon-pencil" title="<?php echo tr('Edit'); ?>" @click="show_edit('password')"></span>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo tr('API keys'); ?></span></div>
    <div class="panel-row">
      <div class="row-key"><?php echo tr('Read & Write API Key'); ?></div>
      <div class="row-value apikey">{{ user.apikey_write }}</div>
      <span class="row-action svg-icon-content_copy" title="<?php echo tr('Copy'); ?>" @click="copy_text_to_clipboard(user.apikey_write,'<?php echo addslashes(tr("Write API Key copied to clipboard")); ?>')"></span>
      <button class="btn btn-default btn-sm" @click="new_apikey('write')"><?php echo tr('Generate New'); ?></button>
    </div>
    <div class="panel-row">
      <div class="row-key"><?php echo tr('Read Only API Key'); ?></div>
      <div class="row-value apikey">{{ user.apikey_read }}</div>
      <span class="row-action svg-icon-content_copy" title="<?php echo tr('Copy'); ?>" @click="copy_text_to_clipboard(user.apikey_read,'<?php echo addslashes(tr("Read API Key copied to clipboard")); ?>')"></span>
      <button class="btn btn-default btn-sm" @click="new_apikey('read')"><?php echo tr('Generate New'); ?></button>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo tr('Profile'); ?></span></div>
    <div class="panel-row" :class="{'is-editing': edit.gravatar}">
      <div class="row-key"><?php echo tr('Gravatar'); ?></div>
      <div class="row-value">
        <template v-if="!edit.gravatar">
          <img v-if="gravatarUrl" class="profile-gravatar" :src="gravatarUrl" />
          <span v-else>{{ user.gravatar }}</span>
        </template>
        <div v-else class="profile-edit">
          <?php echo profile_text_field('gravatar', "save('gravatar')"); ?>
          <button class="btn btn-primary btn-sm" @click="save('gravatar')"><?php echo tr('Save'); ?></button>
          <button class="btn btn-default btn-sm" @click="cancel_edit('gravatar')"><?php echo tr('Cancel'); ?></button>
        </div>
      </div>
      <span v-if="!edit.gravatar" class="row-action svg-icon-pencil" title="<?php echo tr('Edit'); ?>" @click="show_edit('gravatar')"></span>
    </div>
    <?php profile_edit_row('name', tr('Name'), "save('name')", profile_text_field('name', "save('name')")); ?>
    <?php profile_edit_row('location', tr('Location'), "save('location')", profile_text_field('location', "save('location')")); ?>
    <?php profile_edit_row('timezone', tr('Timezone'), "save('timezone')", '<select class="form-select input-285" v-model="user.timezone"><option v-for="tz in timezones" :value="tz.id">{{ tz.id }} {{ tz.gmt_offset_text }}</option></select>'); ?>
    <div class="panel-row" :class="{'is-editing': edit.language}">
      <div class="row-key"><?php echo tr('Language'); ?></div>
      <div class="row-value">
        <template v-if="!edit.language">
          {{ languages[user.language] }}
          <span class="row-note" v-if="translation_status[user.language]!=undefined"><?php echo tr("Translation: "); ?>{{ translation_status[user.language].prc_complete }}% <?php echo tr("complete"); ?></span>
        </template>
        <div v-else class="profile-edit">
          <select class="form-select input-285" v-model="user.language">
            <!-- default en_GB at the top -->
            <option value="en_GB">English (United Kingdom)</option>
            <template v-for="(name,code) in languages"><option v-if="code!='en_GB'" :value="code">{{ name }}</option></template>
          </select>
          <button class="btn btn-primary btn-sm" @click="save('language')"><?php echo tr('Save'); ?></button>
          <button class="btn btn-default btn-sm" @click="cancel_edit('language')"><?php echo tr('Cancel'); ?></button>
        </div>
      </div>
      <span v-if="!edit.language" class="row-action svg-icon-pencil" title="<?php echo tr('Edit'); ?>" @click="show_edit('language')"></span>
    </div>
    <?php profile_edit_row('startingpage', tr('Starting page'), "save('startingpage')", profile_text_field('startingpage', "save('startingpage')")); ?>
  </div>
</div> <!-- end of vue.js section -->

<div class="panel">
  <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo tr('Appearance'); ?></span><span class="panel-badge"><?php echo tr('this browser'); ?></span></div>
  <div class="panel-row">
    <div class="row-key"><?php echo tr('Theme colour'); ?></div>
    <div class="row-value">
      <div class="color-box themecolor theme-blue" name="blue"></div>
      <div class="color-box themecolor theme-black" name="black"></div>
      <div class="color-box themecolor theme-sun" name="sun"></div>
      <div class="color-box themecolor theme-yellow2" name="yellow2"></div>
      <div class="color-box themecolor theme-copper" name="copper"></div>
      <div class="color-box themecolor theme-green" name="green"></div>
    </div>
  </div>
  <div class="panel-row">
    <div class="row-key"><?php echo tr('Sidebar colour'); ?></div>
    <div class="row-value">
      <div class="color-box sidebarcolor sidebar-dark" name="dark"></div>
      <div class="color-box sidebarcolor sidebar-light" name="light"></div>
    </div>
  </div>
  <div class="panel-row">
    <div class="row-key"><?php echo tr('Archived features'); ?></div>
    <div class="row-value">
      <label class="profile-check"><input type="checkbox" id="show-archived"> <?php echo tr('Show archived menu items'); ?></label>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo tr('Mobile app'); ?></span></div>
  <div class="panel-body profile-mobile">
    <div id="qr_apikey"></div>
    <div>
      <p><?php echo tr('Scan QR code from the iOS or Android app to connect.');?></p>
      <p class="text-muted"><?php echo tr('Or scan to view MyElectric web app.');?></p>
      <a href="https://play.google.com/store/apps/details?id=org.emoncms.myapps"><img class="store-badge" alt="Get it on Google Play" src="<?php echo $path; ?>Modules/user/images/en-play-badge.png" /></a>
      <a href="https://itunes.apple.com/us/app/emoncms/id1169483587?ls=1&mt=8"><img class="store-badge" alt="Download on the App Store" src="<?php echo $path; ?>Modules/user/images/appstore.png" /></a>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-header panel-header-static"><span class="panel-accent panel-accent-danger"></span><span class="panel-name"><?php echo tr('Delete account'); ?></span></div>
  <div class="panel-row">
    <div class="row-value"><?php echo tr('Deletes the account with its inputs, feeds and data. This cannot be undone.'); ?></div>
    <button id="delete-account" class="btn btn-danger btn-sm"><?php echo tr('Delete account'); ?></button>
  </div>
</div>
</div> <!-- end of profile-page -->


<div id="myModal" class="modal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="myModalLabel" class="modal-title"><?php echo tr('WARNING deleting an account is permanent'); ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <input class="form-control input-220" id="delete-account-password" type="password" /></p>
                </div>
            </div>
            <div class="modal-footer">
                <button id="canceldelete" class="btn btn-default" data-bs-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
                <button id="confirmdelete" class="btn btn-danger"><?php echo tr('Delete permanently'); ?></button>
                <button id="logoutdelete" class="btn btn-primary" style="display:none"><?php echo tr('Logout'); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="modalNewApikey" class="modal" tabindex="-1" aria-labelledby="modalNewApikeyLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalNewApikeyLabel" class="modal-title"><?php echo tr('Generate a new API key'); ?> - <span id="apikey_type"></span></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php echo tr('Are you sure you want to generate a new apikey?'); ?></p>
                <p><?php echo tr("All devices using the current key will need to be updated with the new key."); ?></p>
            </div>
            <div class="modal-footer">
                <button id="cancel_generate_apikey" class="btn btn-default" data-bs-dismiss="modal" aria-hidden="true"><?php echo tr('Cancel'); ?></button>
                <button id="confirm_generate_apikey" class="btn btn-primary"><?php echo tr('Generate'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var gravatar_enabled = <?php echo json_encode(gravatar_enabled()); ?>;
// sha256 of the stored address, and the only hash the profile page uses. The
// browser cannot compute it: user/set normalises the address before storing it,
// so what was typed here and what the account actually has can differ. Saving a
// new address reloads the page, see save() in profile.js.
var gravatar_hash = <?php echo json_encode($session["gravatar"] ? hash('sha256', strtolower(trim($session["gravatar"]))) : ''); ?>;
var languages = <?php echo json_encode(get_available_languages_with_names()); ?>;
var translation_status = <?php echo json_encode(get_translation_status()); ?>;
var str_passwords_do_not_match = "<?php echo tr('Passwords do not match'); ?>";
</script>
<?php load_js("Modules/user/profile/profile.js"); ?>
