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
global $path; $v=2;
?>
<link href="<?php echo $path; ?>Modules/user/profile/profile.css?v=<?php echo $v; ?>" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/profile/md5.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/qrcode.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/clipboard.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<div id="app" v-cloak>
  <h3><?php echo _('My Account'); ?></h3>
  <table class="table table-hover">
    <tr>
      <td class="muted"><?php echo _('User ID'); ?></td>    
      <td>{{ user.id }}</td>
      <td></td>
      <td><button class="btn btn-small btn-danger" @click="delete_account()"><?php echo _('Delete account'); ?></button></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Username'); ?></td>
      <td>
        <span v-if="!edit.username">{{ user.username }}</span>
        <div v-else class="input-append">
          <input type="text" v-model="user.username"/>
          <button class="btn" @click="save_username(user.username)"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.username" @click="show_edit('username')"></i></td>
      <td></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Email'); ?></td>
      <td>
        <span v-if="!edit.email">{{ user.email }}</span>
        <div v-else class="input-append">
          <input type="text" v-model="user.email"/>
          <button class="btn" @click="save_email(user.email)"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.email" @click="show_edit('email')"></i></td>
      <td></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Write API Key'); ?></td>    
      <td><div class="apikey">{{ user.apikey_write }}</div></td>
      <td><i class="icon-share" @click="copy_text_to_clipboard(user.apikey_write,'<?php echo _("Write API Key copied to clipboard"); ?>')"></i></td>
      <td><button class="btn btn-small" @click="new_apikey('write')">Generate New</button></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Read API Key'); ?></td>    
      <td><div class="apikey">{{ user.apikey_read }}</div></td>
      <td><i class="icon-share" @click="copy_text_to_clipboard(user.apikey_read,'<?php echo _("Read API Key copied to clipboard"); ?>')"></i></td>
      <td><button class="btn btn-small" @click="new_apikey('read')">Generate New</button></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Password'); ?></td>    
      <td>
        <span v-if="!edit.password" class="muted">**********</span>
        <div v-else>
          <div class="account-item">
              <span class="muted"><?php echo _('Current password'); ?></span>
              <br><input type="password" v-model="password.current" />
          </div>
          <div class="account-item">
              <span class="muted"><?php echo _('New password'); ?></span>
              <br><input type="password" v-model="password.new" />
          </div>
          <div class="account-item">
              <span class="muted"><?php echo _('Repeat new password'); ?></span>
              <br><input type="password" v-model="password.repeat" />
          </div>
          <button class="btn btn-primary" @click="change_password()" /><?php echo _('Save'); ?></button>
          <button class="btn" @click="edit.password=false" /><?php echo _('Cancel'); ?></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.password" @click="show_edit('password')"></i></td>
      <td></td>
    </tr>
  </table>

  <h3><?php echo _('Profile'); ?></h3>

  <table class="table table-hover">
    <tr>
      <td class="muted"><?php echo _('Gravatar'); ?></td>
      <td>
        <img v-if="!edit.gravatar" style="border: 1px solid #ccc; padding:2px" :src="'https://www.gravatar.com/avatar/'+CryptoJS.MD5(user.gravatar)" />      
        <div v-else class="input-append">
          <input type="text" style="width:220px" v-model="user.gravatar"/>
          <button class="btn" @click="save('gravatar')"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.gravatar" @click="show_edit('gravatar')"></i></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Name'); ?></td>
      <td>
        <span v-if="!edit.name">{{ user.name }}</span>
        <div v-else class="input-append">
          <input type="text" v-model="user.name"/>
          <button class="btn" @click="save('name')"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.name" @click="show_edit('name')"></i></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Location'); ?></td>
      <td>
        <span v-if="!edit.location">{{ user.location }}</span>
        <div v-else class="input-append">
          <input type="text" v-model="user.location"/>
          <button class="btn" @click="save('location')"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.location" @click="show_edit('location')"></i></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Timezone'); ?></td>
      <td>
        <span v-if="!edit.timezone">{{ user.timezone }}</span>
        <div v-else class="input-append">
          <select v-model="user.timezone">
            <option v-for="tz in timezones" :value="tz.id">{{ tz.id }} {{ tz.gmt_offset_text }}</option>
          </select>
          <button class="btn" @click="save('timezone')"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.timezone" @click="show_edit('timezone')"></i></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Language'); ?></td>
      <td>
        <span v-if="!edit.language">{{ languages[user.language] }}</span>
        <div v-else class="input-append">
          <select v-model="user.language">
            <option v-for="(name,code) in languages" :value="code">{{ name }}</option>
          </select>
          <button class="btn" @click="save('language')"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.language" @click="show_edit('language')"></i></td>
    </tr>
    <tr>
      <td class="muted"><?php echo _('Starting page'); ?></td>
      <td>
        <span v-if="!edit.startingpage">{{ user.startingpage }}</span>
        <div v-else class="input-append">
          <input type="text" v-model="user.startingpage"/>
          <button class="btn" @click="save('startingpage')"><i class="icon-ok"></i></button>
        </div>
      </td>
      <td><i class="icon-pencil" v-if="!edit.startingpage" @click="show_edit('startingpage')"></i></td>
    </tr>    
  </table>
</div> <!-- end of vue.js section -->

<table class="table table-hover">
  <tr>
    <td class="muted"><?php echo _('Theme colour'); ?></td>
    <td>
      <div class="color-box themecolor" name="blue" style="background-color:#44b3e2"></div>
      <div class="color-box themecolor" name="black" style="background-color:#555"></div>
      <div class="color-box themecolor" name="sun" style="background-color:#ffbe14"></div>
      <div class="color-box themecolor" name="yellow2" style="background-color:#dfc72d"></div>
      <div class="color-box themecolor" name="copper" style="background-color:#e28743"></div> 
      <div class="color-box themecolor" name="green" style="background-color:#4eaa05"></div> 
    </td>
  </tr>
  <tr>
    <td class="muted"><?php echo _('Sidebar colour'); ?></td>
    <td>
      <div class="color-box sidebarcolor" name="dark" style="background-color:#333"></div>
      <div class="color-box sidebarcolor" name="light" style="background-color:#eee"></div>
    </td>
  </tr>
</table>

<div style="background-color:#f0f0f0; padding:20px; max-width:360px">

  <div style="width:49.9%; float:left">
    <div style="margin-right:20px">
    <h3 style="margin:0px"><?php echo _('Mobile app'); ?></h3>
    <p><?php echo _('Scan QR code from the iOS or Android app to connect.');?></p>
    <p style="padding-top:10px"><?php echo _('Or scan to view MyElectric web app.');?></p> 
    </div>
  </div>
  <div style="width:49.9%; float:left">
    <div id="qr_apikey"></div>
  </div>
  <div style="clear:both"></div>

  <div style="text-align:center; margin-top:15px">
  <a href="https://play.google.com/store/apps/details?id=org.emoncms.myapps"><img alt="Get it on Google Play" src="<?php echo $path; ?>Modules/user/images/en-play-badge.png" /></a>
  <a href="https://itunes.apple.com/us/app/emoncms/id1169483587?ls=1&mt=8"><img alt="Download on the App Store" src="<?php echo $path; ?>Modules/user/images/appstore.png" /></a>
  </div>
</div>
</div>


<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('WARNING deleting an account is permanent'); ?></h3>
    </div>
    <div class="modal-body">
        <div class="delete-account-s1">
        <p><?php echo _('Are you sure you want to delete your account?'); ?></p>
        </div>

        <div class="delete-account-s2" style="display:none">
        <p><b><?php echo _('Your account has been successfully deleted.'); ?></b></p>
        </div>
        
        <pre id="deleteall-output"></pre>
        
        <div class="delete-account-s1">
            <p><?php echo _('Confirm password to delete:'); ?><br>
            <input id="delete-account-password" type="password" /></p>
        </div>
    </div>
    <div class="modal-footer">
        <button id="canceldelete" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
        <button id="logoutdelete" class="btn btn-primary" style="display:none"><?php echo _('Logout'); ?></button>
    </div>
</div>

<div id="modalNewApikey" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="modalNewApikeyLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="modalNewApikeyLabel">Generate a new <span id="apikey_type"></span> API key</h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Are you sure you want to generate a new apikey?'); ?></p>
        <p><?php echo _("All devices using the current key will need to be updated with the new key."); ?></p>
    </div>
    <div class="modal-footer">
        <button id="cancel_generate_apikey" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirm_generate_apikey" class="btn btn-primary"><?php echo _('Generate'); ?></button>
    </div>
</div>

<script>
var languages = <?php echo json_encode(get_available_languages_with_names()); ?>;
var str_passwords_do_not_match = "<?php echo _('Passwords do not match'); ?>";
</script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/profile/profile.js?v=<?php echo $v; ?>"></script>
