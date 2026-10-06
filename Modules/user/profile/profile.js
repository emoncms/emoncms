// My Account page. Initial data and translated strings come from profile_init, see profile.php.

var profile_strings = profile_init.strings;

// Request to a user API action. Params are sent as a form body for POST, or as a query string.
function profile_request(action, params, post) {
    var body = Object.keys(params || {}).map(function(key) {
        return key + "=" + encodeURIComponent(params[key]);
    }).join("&");

    var url = path + "user/" + action;
    var options = {};
    if (post) {
        options = { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body };
    } else if (body) {
        url += "?" + body;
    }
    return fetch(url, options).then(function(response) {
        if (!response.ok) throw new Error(response.status + " " + response.statusText);
        return response.text();
    }).then(function(text) {
        // deleteall and logout return plain text
        try { return JSON.parse(text); } catch (e) { return text; }
    }).catch(function(error) {
        alert(profile_strings['Request failed'] + ": " + error.message);
        throw error;
    });
}

function local_get(key) {
    try { return localStorage.getItem(key); } catch (e) { return null; }
}
function local_set(key, value) {
    try { localStorage.setItem(key, value); } catch (e) {}
}

var edit_row = {
    template: "#edit-row-template",
    props: {
        label: String,
        editing: Boolean,
        editClass: { type: String, default: "profile-edit" }
    },
    emits: ["edit", "save", "cancel"],
    data: function() { return { strings: profile_strings }; }
};

var profile = Vue.createApp({
    data: function() {
        if (profile_init.user.success === false) alert(profile_init.user.message);
        return {
            user: Object.assign({}, profile_init.user),
            // Values last confirmed by the server
            stored: Object.assign({}, profile_init.user),
            timezones: [],
            languages: profile_init.languages,
            translation_status: profile_init.translation_status,
            gravatar_hash: profile_init.gravatar_hash,
            // Key of the row being edited, one at a time
            editing: null,
            password: { current: "", new: "", repeat: "" },
            apikey_type: "",
            delete_password: "",
            delete_output: "",
            deleted: false,
            themecolors: ["blue", "black", "sun", "yellow2", "copper", "green"],
            sidebarcolors: ["dark", "light"],
            themecolor: current_themecolor,
            themesidebar: current_themesidebar,
            show_archived: local_get("show_archived") === "true"
        };
    },
    computed: {
        gravatar_url: function() {
            // Avatars are served via the local proxy rather than gravatar.com directly,
            // and the proxy is only available where its cache directory exists
            if (!profile_init.gravatar_enabled || !this.gravatar_hash) return "";
            return path + "user/gravatar?hash=" + this.gravatar_hash + "&s=80";
        },
        qr_text: function() {
            return path + "app?readkey=" + this.user.apikey_read + "#myelectric";
        }
    },
    watch: {
        qr_text: function(text) {
            this.qrcode.makeCode(text);
        }
    },
    mounted: function() {
        this.qrcode = new QRCode(this.$refs.qr, {
            text: this.qr_text,
            width: 160,
            height: 160,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        profile_request("gettimezones.json").then((result) => {
            this.timezones = result;
        });
    },
    methods: {
        show_edit: function(key) {
            if (this.editing) this.cancel_edit();
            this.editing = key;
        },
        cancel_edit: function() {
            if (this.editing && this.editing != "password") {
                this.user[this.editing] = this.stored[this.editing];
            }
            this.password = { current: "", new: "", repeat: "" };
            this.editing = null;
        },
        close_edit: function() {
            this.password = { current: "", new: "", repeat: "" };
            this.editing = null;
        },
        // Profile fields: user/set replaces all of them, so the whole user object is sent
        save: function() {
            profile_request("set.json", { data: JSON.stringify(this.user) }, true).then((result) => {
                if (!result.success) return alert(result.message);
                // Reload after a language change so the new translation applies, and after
                // a gravatar change because the avatar hash is rendered server side
                if (this.user.language != this.stored.language || this.user.gravatar != this.stored.gravatar) {
                    window.location.href = path + "user/view";
                    return;
                }
                // Read back the stored values, user/set removes characters it does not allow
                profile_request("get.json").then((user) => {
                    this.user = Object.assign({}, user);
                    this.stored = Object.assign({}, user);
                    this.close_edit();
                });
            });
        },
        save_username: function() {
            var username = this.user.username;
            if (username == this.stored.username) return this.close_edit();
            profile_request("changeusername.json", { username: username }, true).then((result) => {
                if (!result.success) return alert(result.message);
                this.stored.username = username;
                this.close_edit();
            });
        },
        save_email: function() {
            var email = this.user.email;
            if (email == this.stored.email) return this.close_edit();
            // Current password is required, see change_email
            if (this.password.current == "") return alert(profile_strings['Current password field empty']);
            profile_request("changeemail.json", { email: email, password: this.password.current }, true).then((result) => {
                if (!result.success) return alert(result.message);
                this.stored.email = email;
                this.close_edit();
            });
        },
        change_password: function() {
            if (this.password.current == "") return alert(profile_strings['Current password field empty']);
            if (this.password.new == "") return alert(profile_strings['New password field empty']);
            if (this.password.repeat == "") return alert(profile_strings['Repeat password field empty']);
            if (this.password.new != this.password.repeat) return alert(profile_strings['Passwords do not match']);
            profile_request("changepassword.json", { old: this.password.current, new: this.password.new }, true).then((result) => {
                if (result.success) this.close_edit();
                alert(result.message);
            });
        },
        copy_apikey: function(type) {
            var message = type == "write" ? profile_strings['Write API Key copied to clipboard'] : profile_strings['Read API Key copied to clipboard'];
            copy_text_to_clipboard(this.user["apikey_" + type], message);
        },
        new_apikey: function(type) {
            this.apikey_type = type;
            bootstrap.Modal.getOrCreateInstance(this.$refs.apikey_modal).show();
        },
        confirm_new_apikey: function() {
            var type = this.apikey_type;
            profile_request("newapikey" + type + ".json", {}, true).then((result) => {
                if (!result.success) return;
                this.user["apikey_" + type] = result[type + "_apikey"];
                this.stored["apikey_" + type] = result[type + "_apikey"];
                bootstrap.Modal.getOrCreateInstance(this.$refs.apikey_modal).hide();
            });
        },
        delete_account: function() {
            this.deleted = false;
            this.delete_password = "";
            this.delete_output = "";
            bootstrap.Modal.getOrCreateInstance(this.$refs.delete_modal).show();
            profile_request("deleteall.json", { mode: "dryrun" }, true).then((result) => {
                this.delete_output = result;
            });
        },
        confirm_delete: function() {
            profile_request("deleteall.json", { mode: "permanentdelete", password: this.delete_password }, true).then((result) => {
                this.delete_output = result;
                if (result.startsWith("PERMANENT DELETE")) this.deleted = true;
            });
        },
        logout: function() {
            profile_request("logout.json").finally(function() {
                window.location = path;
            });
        },
        // Theme selection, used in conjunction with code in Theme/js/emoncms.js
        set_themecolor: function(name) {
            document.documentElement.classList.remove("theme-" + this.themecolor);
            document.documentElement.classList.add("theme-" + name);
            local_set("themecolor", name);
            current_themecolor = this.themecolor = name;
        },
        set_themesidebar: function(name) {
            document.documentElement.classList.remove("sidebar-" + this.themesidebar);
            document.documentElement.classList.add("sidebar-" + name);
            local_set("themesidebar", name);
            current_themesidebar = this.themesidebar = name;
        },
        // Archived features toggle, used in conjunction with code in Theme/menu/menu.js
        set_show_archived: function(show) {
            local_set("show_archived", show ? "true" : "false");
            window.location.reload();
        }
    }
});
profile.component("edit-row", edit_row);
profile.mount("#profile");
