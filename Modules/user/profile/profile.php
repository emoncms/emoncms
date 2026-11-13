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

// view() only brings $path and the controller args into scope, and the gravatar
// hash below is rendered from the session user's own address
global $session;

load_css("Modules/user/profile/profile.css");
load_js("Lib/js/clipboard.js");
load_js("Lib/js/vue.global.prod-3.5.22.min.js");

// Translated text for HTML content and attributes
function profile_tr($text)
{
    return htmlspecialchars(tr($text), ENT_QUOTES, 'UTF-8');
}

// Translated strings used from profile.js
$js_strings = [
    'Save', 'Cancel', 'Edit',
    'Current password field empty', 'New password field empty', 'Repeat password field empty',
    'Passwords do not match', 'Request failed',
    'Write API Key copied to clipboard', 'Read API Key copied to clipboard'
];
?>
<div id="profile" class="panel-page profile-page" v-cloak>
    <div class="page-header">
        <h3><?php echo profile_tr('My Account'); ?></h3>
    </div>

    <div class="panel">
        <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo profile_tr('Account'); ?></span></div>
        <div class="panel-row">
            <div class="row-key"><?php echo profile_tr('User ID'); ?></div>
            <div class="row-value">{{ user.id }}</div>
        </div>
        <edit-row label="<?php echo profile_tr('Username'); ?>" :editing="editing=='username'" @edit="show_edit('username')" @save="save_username" @cancel="cancel_edit">
            {{ user.username }}
            <template #edit>
                <input class="form-control input-220" type="text" v-model="user.username" @keyup.enter="save_username"/>
            </template>
        </edit-row>
        <edit-row label="<?php echo profile_tr('Email'); ?>" :editing="editing=='email'" @edit="show_edit('email')" @save="save_email" @cancel="cancel_edit">
            {{ user.email }}
            <template #edit>
                <input class="form-control input-220" type="text" v-model="user.email"/>
                <input class="form-control input-220" type="password" v-model="password.current" placeholder="<?php echo profile_tr('Current password'); ?>" @keyup.enter="save_email"/>
            </template>
        </edit-row>
        <edit-row label="<?php echo profile_tr('Password'); ?>" :editing="editing=='password'" edit-class="profile-password" @edit="show_edit('password')" @save="change_password" @cancel="cancel_edit">
            <span class="text-muted">**********</span>
            <template #edit>
                <label class="form-label"><?php echo profile_tr('Current password'); ?></label>
                <input class="form-control input-220 mb-2" type="password" v-model="password.current"/>
                <label class="form-label"><?php echo profile_tr('New password'); ?></label>
                <input class="form-control input-220 mb-2" type="password" v-model="password.new"/>
                <label class="form-label"><?php echo profile_tr('Repeat new password'); ?></label>
                <input class="form-control input-220 mb-3" type="password" v-model="password.repeat" @keyup.enter="change_password"/>
            </template>
        </edit-row>
    </div>

    <div class="panel">
        <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo profile_tr('API keys'); ?></span></div>
        <div class="panel-row">
            <div class="row-key"><?php echo profile_tr('Read & Write API Key'); ?></div>
            <div class="row-value apikey">{{ user.apikey_write }}</div>
            <span class="row-action svg-icon-content_copy" title="<?php echo profile_tr('Copy'); ?>" @click="copy_apikey('write')"></span>
            <button class="btn btn-default btn-sm" @click="new_apikey('write')"><?php echo profile_tr('Generate New'); ?></button>
        </div>
        <div class="panel-row">
            <div class="row-key"><?php echo profile_tr('Read Only API Key'); ?></div>
            <div class="row-value apikey">{{ user.apikey_read }}</div>
            <span class="row-action svg-icon-content_copy" title="<?php echo profile_tr('Copy'); ?>" @click="copy_apikey('read')"></span>
            <button class="btn btn-default btn-sm" @click="new_apikey('read')"><?php echo profile_tr('Generate New'); ?></button>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo profile_tr('Profile'); ?></span></div>
        <edit-row label="<?php echo profile_tr('Gravatar'); ?>" :editing="editing=='gravatar'" @edit="show_edit('gravatar')" @save="save" @cancel="cancel_edit">
            <img v-if="gravatar_url" class="profile-gravatar" :src="gravatar_url"/>
            <span v-else>{{ user.gravatar }}</span>
            <template #edit>
                <input class="form-control input-220" type="text" v-model="user.gravatar" @keyup.enter="save"/>
            </template>
        </edit-row>
        <edit-row label="<?php echo profile_tr('Name'); ?>" :editing="editing=='name'" @edit="show_edit('name')" @save="save" @cancel="cancel_edit">
            {{ user.name }}
            <template #edit>
                <input class="form-control input-220" type="text" v-model="user.name" @keyup.enter="save"/>
            </template>
        </edit-row>
        <edit-row label="<?php echo profile_tr('Location'); ?>" :editing="editing=='location'" @edit="show_edit('location')" @save="save" @cancel="cancel_edit">
            {{ user.location }}
            <template #edit>
                <input class="form-control input-220" type="text" v-model="user.location" @keyup.enter="save"/>
            </template>
        </edit-row>
        <edit-row label="<?php echo profile_tr('Timezone'); ?>" :editing="editing=='timezone'" @edit="show_edit('timezone')" @save="save" @cancel="cancel_edit">
            {{ user.timezone }}
            <template #edit>
                <select class="form-select input-285" v-model="user.timezone">
                    <option v-for="tz in timezones" :value="tz.id">{{ tz.id }} {{ tz.gmt_offset_text }}</option>
                </select>
            </template>
        </edit-row>
        <edit-row label="<?php echo profile_tr('Language'); ?>" :editing="editing=='language'" @edit="show_edit('language')" @save="save" @cancel="cancel_edit">
            {{ languages[user.language] }}
            <span class="row-note" v-if="translation_status[user.language]"><?php echo profile_tr('Translation: '); ?>{{ translation_status[user.language].prc_complete }}% <?php echo profile_tr('complete'); ?></span>
            <template #edit>
                <select class="form-select input-285" v-model="user.language">
                    <!-- default en_GB at the top -->
                    <option value="en_GB">English (United Kingdom)</option>
                    <template v-for="(name, code) in languages"><option v-if="code!='en_GB'" :value="code">{{ name }}</option></template>
                </select>
            </template>
        </edit-row>
        <edit-row label="<?php echo profile_tr('Starting page'); ?>" :editing="editing=='startingpage'" @edit="show_edit('startingpage')" @save="save" @cancel="cancel_edit">
            {{ user.startingpage }}
            <template #edit>
                <input class="form-control input-220" type="text" v-model="user.startingpage" @keyup.enter="save"/>
            </template>
        </edit-row>
    </div>

    <div class="panel">
        <div class="panel-header panel-header-static"><span class="panel-accent"></span><span class="panel-name"><?php echo profile_tr('Appearance'); ?></span><span class="panel-badge"><?php echo profile_tr('this browser'); ?></span></div>
        <div class="panel-row">
            <div class="row-key"><?php echo profile_tr('Theme colour'); ?></div>
            <div class="row-value">
                <div v-for="name in themecolors" class="color-box themecolor" :class="['theme-'+name, {'color-box-active': name==themecolor}]" @click="set_themecolor(name)"></div>
            </div>
        </div>
        <div class="panel-row">
            <div class="row-key"><?php echo profile_tr('Sidebar colour'); ?></div>
            <div class="row-value">
                <div v-for="name in sidebarcolors" class="color-box sidebarcolor" :class="['sidebar-'+name, {'color-box-active': name==themesidebar}]" @click="set_themesidebar(name)"></div>
            </div>
        </div>
        <div class="panel-row">
            <div class="row-key"><?php echo profile_tr('Archived features'); ?></div>
            <div class="row-value">
                <label class="profile-check"><input type="checkbox" :checked="show_archived" @change="set_show_archived($event.target.checked)"> <?php echo profile_tr('Show archived menu items'); ?></label>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header panel-header-static"><span class="panel-accent panel-accent-danger"></span><span class="panel-name"><?php echo profile_tr('Delete account'); ?></span></div>
        <div class="panel-row">
            <div class="row-value"><?php echo profile_tr('Deletes the account with its inputs, feeds and data. This cannot be undone.'); ?></div>
            <button class="btn btn-danger btn-sm" @click="delete_account"><?php echo profile_tr('Delete account'); ?></button>
        </div>
    </div>

    <div ref="delete_modal" class="modal" tabindex="-1" aria-labelledby="delete-modal-label" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="delete-modal-label" class="modal-title"><?php echo profile_tr('WARNING deleting an account is permanent'); ?></h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p v-if="!deleted"><?php echo profile_tr('Are you sure you want to delete your account?'); ?></p>
                    <p v-else><b><?php echo profile_tr('Your account has been successfully deleted.'); ?></b></p>
                    <pre>{{ delete_output }}</pre>
                    <p v-if="!deleted"><?php echo profile_tr('Confirm password to delete:'); ?><br>
                        <input class="form-control input-220" type="password" v-model="delete_password" @keyup.enter="confirm_delete"/>
                    </p>
                </div>
                <div class="modal-footer">
                    <template v-if="!deleted">
                        <button class="btn btn-default" data-bs-dismiss="modal"><?php echo profile_tr('Cancel'); ?></button>
                        <button class="btn btn-danger" @click="confirm_delete"><?php echo profile_tr('Delete permanently'); ?></button>
                    </template>
                    <button v-else class="btn btn-primary" @click="logout"><?php echo profile_tr('Logout'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div ref="apikey_modal" class="modal" tabindex="-1" aria-labelledby="apikey-modal-label" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="apikey-modal-label" class="modal-title"><?php echo profile_tr('Generate a new API key'); ?> - {{ apikey_type }}</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo profile_tr('Are you sure you want to generate a new apikey?'); ?></p>
                    <p><?php echo profile_tr('All devices using the current key will need to be updated with the new key.'); ?></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-bs-dismiss="modal"><?php echo profile_tr('Cancel'); ?></button>
                    <button class="btn btn-primary" @click="confirm_new_apikey"><?php echo profile_tr('Generate'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Key and value row with inline edit. Default slot shows the value, edit slot holds the fields. -->
<script type="text/x-template" id="edit-row-template">
    <div class="panel-row" :class="{'is-editing': editing}">
        <div class="row-key">{{ label }}</div>
        <div class="row-value">
            <slot v-if="!editing"></slot>
            <div v-else :class="editClass">
                <slot name="edit"></slot>
                <div class="profile-edit-buttons">
                    <button class="btn btn-primary btn-sm" @click="$emit('save')">{{ strings['Save'] }}</button>
                    <button class="btn btn-default btn-sm" @click="$emit('cancel')">{{ strings['Cancel'] }}</button>
                </div>
            </div>
        </div>
        <span v-if="!editing" class="row-action svg-icon-pencil" :title="strings['Edit']" @click="$emit('edit')"></span>
    </div>
</script>

<script>
var profile_init = {
    user: <?php echo json_encode($account); ?>,
    gravatar_enabled: <?php echo json_encode(gravatar_enabled()); ?>,
    // sha256 of the stored address. The browser cannot compute it: user/set
    // normalises the address before storing it, so what was typed here and what
    // the account has can differ. Saving a new address reloads the page.
    gravatar_hash: <?php echo json_encode($session["gravatar"] ? hash('sha256', strtolower(trim($session["gravatar"]))) : ''); ?>,
    languages: <?php echo json_encode(get_available_languages_with_names()); ?>,
    translation_status: <?php echo json_encode(get_translation_status()); ?>,
    strings: <?php echo json_encode(array_combine($js_strings, array_map('tr', $js_strings))); ?>
};
</script>
<?php load_js("Modules/user/profile/profile.js"); ?>
