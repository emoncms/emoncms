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

global $path, $enable_rememberme, $enable_password_reset, $theme;

?>
<style>
  .main {
    max-width: 320px;
    margin: 0 auto;
    padding: 10px;
  }
</style>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>

<div class="main">
  <div class="well">
    <img src="<?php echo $path; ?>Theme/<?php echo $theme; ?>/emoncms_logo.png" alt="Emoncms" width="256" height="46" />
    <div class="login-container">
        <form id="login-form" method="post">
            <div id="loginblock">
                <div class="form-group register-item" style="display:none">
                    <label><?php echo _('Email'); ?>
                        <input type="text" name="email" tabindex="1"/>
                    </label>
                </div>

                <div class="form-group">
                    <label><?php echo _('Username'); ?>
                        <input type="text" tabindex="2" autocomplete="on" name="username"  />
                    </label>
                </div>

                <div class="form-group">
                    <a id="passwordreset-link" class="pull-right" href="#">Forgot password?</a>
                    <label><?php echo _('Password'); ?>
                        <input type="password" tabindex="3" autocomplete="on" name="password" />
                    </label>
                </div>

                <div class="form-group register-item" style="display:none">
                    <label><?php echo _('Confirm password'); ?>
                        <input id="confirm-password" type="password" name="confirm-password" tabindex="4"/>
                    </label>
                </div>

                <div id="loginmessage"></div>

                <div class="form-group login-item">
                    <?php if ($enable_rememberme) { ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" tabindex="5" id="rememberme" value="1" name="rememberme"><?php echo '&nbsp;'._('Remember me'); ?>
                            </label>
                        </div>
                    <?php } ?>
                    <button id="login" class="btn btn-primary" tabindex="6" type="submit"><?php echo _('Login'); ?></button>
                    <?php if ($allowusersregister) { echo '&nbsp;'._('or').'&nbsp;' ?>
                        <a id="register-link" href="#"><?php echo _('register'); ?></a>
                    <?php } ?>
                </div>

                <div class="form-group register-item" style="display:none">
                    <button id="register" class="btn btn-primary" type="button"><?php echo _('Register'); ?></button>
                    <?php echo '&nbsp;'._('or').'&nbsp;' ?>
                    <a id="cancel-link" href="#"><?php echo _('login'); ?></a>
                </div>

            </div>

            <div id="passwordresetblock" class="collapse">
                <div class="form-group">
                    <label>Existing account name
                        <input id="passwordreset-username" type="text" />
                    </label>
                </div>
                <div class="form-group">
                    <label>Account email address
                        <input id="passwordreset-email" type="text" />
                    </label>
                </div>
                <button id="passwordreset-submit" class="btn btn-primary" type="button">Recover</button>
                <?php echo '&nbsp;'._('or').'&nbsp;' ?>
                <a id="passwordreset-link-cancel" href="#"><?php echo _('login'); ?></a>
            </div>
            <div id="passwordresetmessage"></div>
        </form>
    </div>
  </div>
</div>

<script>
"use strict";

var path = "<?php echo $path; ?>";
var register_open = false;

$(document).ready(function() {
    var passwordreset = "<?php echo $enable_password_reset; ?>";
    if (!passwordreset) $("#passwordreset-link").hide();
});

$("#passwordreset-link").on("click", function(){
        $("#passwordresetblock").collapse('show');
        $("#loginblock").collapse('hide');
        $("#passwordresetmessage").html("");
});

$("#passwordreset-link-cancel").on("click", function(){
        $("#passwordresetblock").collapse('hide');
        $("#loginblock").collapse('show');
        $("#loginmessage").html("");
});

$("#passwordreset-submit").click(function(){
    var username = $("#passwordreset-username").val();
    var email = $("#passwordreset-email").val();

    if (email==="" || username==="") {
        $("#passwordresetmessage").html("<div>&nbsp;</div><div class='alert alert-error'>Please enter username and email address</div>");
    } else {
        var result = user.passwordreset(username,email);
        if (result.success===true) {
            $("#passwordresetmessage").html("<div>&nbsp;</div><div class='alert alert-success'>"+result.message+"</div>");
            $("#passwordresetblock").hide();
        } else {
            $("#passwordresetmessage").html("<div>&nbsp;</div><div class='alert alert-error'>"+result.message+"</div>");
        }
    }
});

$("#register-link").click(function(){
    $(".login-item").hide();
    $(".register-item").show();
    $("#loginmessage").html("");
    register_open = true;
    return false;
});

$("#cancel-link").click(function(){
    $(".login-item").show();
    $(".register-item").hide();
    $("#loginmessage").html("");
    register_open = false;
    return false;
});

$('input[type=text]').on('keypress', function(e) {
    //login or register when pressing enter
    if (e.which == 13) {
        e.preventDefault();
        if ( register_open ) {
            register();
        } else {
            return login();
        }
    }
});

$('#login').click(function() {
  return login();
});

function login(){
    var username = $("input[name='username']").val();
    var password = $("input[name='password']").val();
    var rememberme = 0; if ($("#rememberme").is(":checked")) rememberme = 1;

    var result = user.login(username,password,rememberme);

    if (result.success)
    {
        $('#login-form').submit();
        return true;
    }
    else
    {
        $("#loginmessage").html("<div class='alert alert-error'>"+result.message+"</div>");
        return false;
    }
}

function register(){
    var username = $("input[name='username']").val();
    var password = $("input[name='password']").val();
    var confirmpassword = $("input[name='confirm-password']").val();
    var email = $("input[name='email']").val();

    if (password != confirmpassword)
    {
        $("#loginmessage").html("<div class='alert alert-error'>Passwords do not match</div>");
    }
    else
    {
        var result = user.register(username,password,email);

        if (result.success)
        {
            result = user.login(username,password);
            if (result.success)
            {
                window.location.href = path+"user/view";
            }
        }
        else
        {
            $("#loginmessage").html("<div class='alert alert-error'>"+result.message+"</div>");
        }
    }
}

$("#register").click(register);
</script>
