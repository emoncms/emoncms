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

global $path, $settings;

?>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js?v=<?php echo $v ?>"></script>

<div id="login-app" v-cloak class="d-flex align-items-start justify-content-center min-vh-100 pb-3" style="padding-top: 10vh;">
    <section class="card w-100 mw-400" aria-label="<?php echo tr('Account login'); ?>">
        <div class="card-body p-4">
            <img class="d-block img-fluid mx-auto mb-3 mw-256" src="<?php echo $path; ?>Theme/logo_login.png" alt="Login" width="256" height="46" />

            <form autocomplete="on" @submit.prevent="submitPrimary">
                <header class="mb-3">
                    <h1 class="mt-0 mb-1">{{ modeTitle }}</h1>
                    <p v-if="mode === 'login'" class="mb-0 text-secondary"><?php echo tr('Sign in to continue'); ?></p>
                    <p v-else-if="mode === 'register'" class="mb-0 text-secondary"><?php echo tr('Create your account'); ?></p>
                    <p v-else class="mb-0 text-secondary"><?php echo tr('Recover your account'); ?></p>
                </header>

                <div v-if="mode === 'register'" class="mb-2">
                    <label for="email-input" class="d-block mb-1 text-secondary"><?php echo tr('Email'); ?></label>
                    <input
                        class="w-100"
                        id="email-input"
                        ref="emailInput"
                        v-model.trim="form.email"
                        type="email"
                        autocomplete="email"
                        required
                    />
                </div>

                <div class="mb-2">
                    <label for="username-input" class="d-block mb-1 text-secondary"><?php echo tr('Username'); ?></label>
                    <input
                        class="w-100"
                        id="username-input"
                        ref="usernameInput"
                        v-model.trim="form.username"
                        type="text"
                        autocomplete="username"
                        required
                    />
                </div>

                <div v-if="mode !== 'reset'" class="mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-1">
                        <label for="password-input" class="mb-0 text-secondary"><?php echo tr('Password'); ?></label>
                        <a
                            v-if="mode === 'login' && passwordResetEnabled"
                            href="#"
                            @click.prevent="switchMode('reset')"
                        ><?php echo tr('Forgot password?'); ?></a>
                    </div>
                    <input
                        class="w-100"
                        id="password-input"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        :autocomplete="mode === 'register' ? 'new-password' : 'current-password'"
                        required
                    />
                </div>

                <div v-if="mode === 'register'" class="mb-2">
                    <label for="confirm-password-input" class="d-block mb-1 text-secondary"><?php echo tr('Confirm password'); ?></label>
                    <input
                        class="w-100"
                        id="confirm-password-input"
                        ref="confirmPasswordInput"
                        v-model="form.confirmPassword"
                        type="password"
                        autocomplete="new-password"
                        required
                    />
                </div>

                <div v-if="mode === 'reset'" class="pt-2 mt-2 border-top">
                    <div class="mb-2">
                        <label for="reset-username-input" class="d-block mb-1 text-secondary"><?php echo tr('Existing account name'); ?></label>
                        <input
                            class="w-100"
                            id="reset-username-input"
                            ref="resetUsernameInput"
                            v-model.trim="reset.username"
                            type="text"
                            autocomplete="username"
                            required
                        />
                    </div>

                    <div class="mb-2">
                        <label for="reset-email-input" class="d-block mb-1 text-secondary"><?php echo tr('Account email address'); ?></label>
                        <input
                            class="w-100"
                            id="reset-email-input"
                            ref="resetEmailInput"
                            v-model.trim="reset.email"
                            type="email"
                            autocomplete="email"
                            required
                        />
                    </div>
                </div>

                <div v-if="loginMessage.text" class="alert" :class="loginMessage.success ? 'alert-success' : 'alert-error'">
                    <div>{{ loginMessage.text }}</div>
                    <div v-if="loginMessage.resend" class="d-flex align-items-center flex-wrap gap-2 mt-2">
                        <button class="btn" type="button" @click="resendVerify"><?php echo tr('Resend'); ?></button>
                        <span><?php echo tr('Click to resend verification email'); ?></span>
                    </div>
                </div>

                <div v-if="resetMessage.text" class="alert" :class="resetMessage.success ? 'alert-success' : 'alert-error'">
                    {{ resetMessage.text }}
                </div>

                <div v-if="mode === 'login' && rememberMeEnabled" class="mt-2">
                    <label class="d-flex align-items-center gap-1 mb-0 text-secondary">
                        <input v-model="form.rememberme" type="checkbox" autocomplete="off" />
                        <span><?php echo tr('Remember me'); ?></span>
                    </label>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2 mt-3">
                    <button v-if="mode === 'login'" class="btn btn-primary" type="submit"><?php echo tr('Login'); ?></button>
                    <button v-if="mode === 'register'" class="btn btn-primary" type="submit"><?php echo tr('Register'); ?></button>
                    <button v-if="mode === 'reset'" class="btn btn-primary" type="submit"><?php echo tr('Recover'); ?></button>

                    <a v-if="mode === 'login' && allowRegister" href="#" @click.prevent="switchMode('register')"><?php echo tr('register'); ?></a>
                    <a v-if="mode === 'register'" href="#" @click.prevent="switchMode('login')"><?php echo tr('login'); ?></a>
                    <a v-if="mode === 'reset'" href="#" @click.prevent="switchMode('login')"><?php echo tr('login'); ?></a>
                </div>

                <p class="mt-2 mb-0"><small class="muted"><?php echo $message ?></small></p>
            </form>
        </div>
    </section>
</div>

<script>
"use strict";

menu.disable();
document.body.classList.add("body-login");

(function () {
    var appConfig = {
        verify: <?php echo json_encode($verify); ?>,
        allowRegister: <?php echo json_encode((bool) $allowusersregister); ?>,
        passwordResetEnabled: <?php echo json_encode((bool) $settings['interface']['enable_password_reset']); ?>,
        rememberMeEnabled: <?php echo json_encode((bool) $settings['interface']['enable_rememberme']); ?>,
        referrer: <?php echo json_encode($referrer); ?>,
        i18n: {
            login: <?php echo json_encode(tr('Login')); ?>,
            register: <?php echo json_encode(tr('Register')); ?>,
            recover: <?php echo json_encode(tr('Recover')); ?>,
            passwordsNoMatch: <?php echo json_encode(tr('Passwords do not match')); ?>,
            usernameEmailRequired: <?php echo json_encode(tr('Please enter username and email address')); ?>
        }
    };

    var app = Vue.createApp({
        data: function () {
            return {
                mode: "login",
                allowRegister: appConfig.allowRegister,
                passwordResetEnabled: appConfig.passwordResetEnabled,
                rememberMeEnabled: appConfig.rememberMeEnabled,
                referrer: appConfig.referrer,
                i18n: appConfig.i18n,
                form: {
                    email: "",
                    username: "",
                    password: "",
                    confirmPassword: "",
                    rememberme: false
                },
                reset: {
                    username: "",
                    email: ""
                },
                loginMessage: {
                    text: "",
                    success: false,
                    resend: false
                },
                resetMessage: {
                    text: "",
                    success: false
                }
            };
        },
        computed: {
            modeTitle: function () {
                if (this.mode === "register") return this.i18n.register;
                if (this.mode === "reset") return this.i18n.recover;
                return this.i18n.login;
            }
        },
        mounted: function () {
            if (appConfig.verify && appConfig.verify.success !== undefined) {
                this.setLoginMessage(!!appConfig.verify.success, appConfig.verify.message || "", false);
            }
            this.focusPrimaryInput();
        },
        methods: {
            switchMode: function (mode) {
                this.mode = mode;
                this.loginMessage.text = "";
                this.loginMessage.resend = false;
                this.resetMessage.text = "";

                if (mode === "reset") {
                    this.reset.username = this.form.username;
                    this.reset.email = this.form.email;
                }

                this.focusPrimaryInput();
            },
            setLoginMessage: function (success, message, resend) {
                this.loginMessage.success = success;
                this.loginMessage.text = message || "";
                this.loginMessage.resend = !!resend;
            },
            setResetMessage: function (success, message) {
                this.resetMessage.success = success;
                this.resetMessage.text = message || "";
            },
            submitPrimary: function () {
                if (this.mode === "register") {
                    this.register();
                    return;
                }
                if (this.mode === "reset") {
                    this.passwordReset();
                    return;
                }
                this.login();
            },
            login: function () {
                var result = user.login(this.form.username, this.form.password, this.form.rememberme ? 1 : 0, this.referrer);

                if (result.success === undefined) {
                    this.setLoginMessage(false, String(result), false);
                    return;
                }

                if (result.success) {
                    var href = Object.prototype.hasOwnProperty.call(result, "startingpage") ? path + result.startingpage : path;
                    window.location.href = href;
                    return;
                }

                if (result.message === "Please verify email address") {
                    this.setLoginMessage(false, result.message, true);
                } else {
                    this.setLoginMessage(false, result.message || "", false);
                }
            },
            register: function () {
                if (this.form.password !== this.form.confirmPassword) {
                    this.setLoginMessage(false, this.i18n.passwordsNoMatch, false);
                    return;
                }

                var timezone = "UTC";
                if (typeof Intl !== "undefined" && Intl.DateTimeFormat) {
                    timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || timezone;
                }

                var result = user.register(this.form.username, this.form.password, this.form.email, timezone);

                if (result.success === undefined) {
                    this.setLoginMessage(false, String(result), false);
                    return;
                }

                if (result.success) {
                    if (result.verifyemail) {
                        this.switchMode("login");
                        this.setLoginMessage(true, result.message || "", false);
                    } else {
                        this.login();
                    }
                    return;
                }

                this.setLoginMessage(false, result.message || "", false);
            },
            passwordReset: function () {
                if (this.reset.username === "" || this.reset.email === "") {
                    this.setResetMessage(false, this.i18n.usernameEmailRequired);
                    return;
                }

                var result = user.passwordreset(this.reset.username, this.reset.email);
                if (result.success === true) {
                    this.setResetMessage(true, result.message || "");
                    return;
                }
                this.setResetMessage(false, (result && result.message) ? result.message : "");
            },
            resendVerify: function () {
                var vm = this;
                $.ajax({
                    url: path + "user/resend-verify.json",
                    data: "&username=" + encodeURIComponent(vm.form.username),
                    dataType: "json",
                    success: function (result) {
                        vm.setLoginMessage(!!result.success, result.message || "", false);
                    }
                });
            },
            focusPrimaryInput: function () {
                var vm = this;
                this.$nextTick(function () {
                    var target;
                    if (vm.mode === "register") {
                        target = vm.$refs.emailInput || vm.$refs.usernameInput;
                    } else if (vm.mode === "reset") {
                        target = vm.$refs.resetUsernameInput;
                    } else {
                        target = vm.$refs.usernameInput;
                    }
                    if (target && target.focus) target.focus();
                });
            }
        }
    });

    app.mount("#login-app");
})();
</script>
