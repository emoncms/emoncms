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

global $path, $enable_rememberme, $enable_password_reset;

?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>

<div style="margin: 0px auto; max-width:392px; padding:10px;">
    <div style="max-width:392px; margin-right:20px; padding-top:45px; padding-bottom:15px; color: #888;">
        <img style="margin:12px;" src="<?php echo $path; ?>Theme/emoncms_logo.png" alt="Emoncms" width="256" height="46" />
    </div>

    <div class="login-container">

        <div id="login-form"  class="well" style="text-align:left">
            <p>
                <label><?php echo _('Username:'); ?>
                    <input type="text" tabindex="1" name="username"  />
                </label>
            </p>

            <p class="register-item" style="display:none">
                <label><?php echo _('Email:'); ?>
                    <input type="text" name="email" style="width:94%" tabindex="2"/>
                </label>
            </p>

            <p>
                <label><?php echo _('Password:'); ?>
                    <input type="password" tabindex="3" name="password" />
                </label>
            </p>

            <p class="register-item" style="display:none">
                <label><?php echo _('Confirm password:'); ?>
                    <input id="confirm-password" type="password" name="confirm-password" tabindex="4"/>
                </label>
            </p>

            <div id="error" class="alert alert-error" style="display:none;"></div>

            <p class="login-item">
                <?php if ($enable_rememberme) { ?><label class="checkbox"><input type="checkbox" tabindex="5" id="rememberme" value="1" name="rememberme"><?php echo '&nbsp;'._('Remember me'); ?></label><br /><?php } ?>
                <button id="login" class="btn btn-primary" tabindex="6" type="button"><?php echo _('Login'); ?></button>
                <?php if ($allowusersregister) { echo '&nbsp;'._('or').'&nbsp;' ?><a id="register-link"  href="#"><?php echo _('register'); ?></a><?php } ?>
            </p>

            <p class="register-item" style="display:none">
                <button id="register" class="btn btn-primary" type="button"><?php echo _('Register'); ?></button> <?php echo '&nbsp;'._('or').'&nbsp;' ?>
                <a id="cancel-link" href="#"><?php echo _('cancel'); ?></a>
            </p>

            <p>
                <a id="passwordreset-link" href="#">Forgotten password</a>
            </p>
            
            <div id="passwordreset-block" style="display:none">
                <hr>
                <div id="passwordreset-message"></div>

                <div id="passwordreset-input">
                <p>
                    <label>Enter account name:
                        <input id="passwordreset-username" type="text" />
                    </label>
                <p>
                <p>
                    <label>Enter account email address:
                        <input id="passwordreset-email" type="text" />
                    </label>
                </p>
                <div id="error-passwordreset" class="alert alert-error" style="display:none;"></div>
                <button id="passwordreset-submit" class="btn">Submit</button>
                </div>

            </div>
        </div>

    </div>
</div>

<script>

/*global user:false */

"use strict";

var path = "<?php echo $path; ?>";

$(document).ready(function() {

    var register_open = false;
    var passwordreset = "<?php echo $enable_password_reset; ?>";

    if (!passwordreset) $("#passwordreset-link").hide();

    $("#passwordreset-link").click(function(){
        $("#passwordreset-block").show();
        $("#passwordreset-input").show();
        $("#passwordreset-message").html("");
    });

    $("#passwordreset-submit").click(function(){
        var username = $("#passwordreset-username").val();
        var email = $("#passwordreset-email").val();
        
        if (email==="" || username==="") {
            $("#error-passwordreset").text("Please enter username and email address").show();
        } else {
            var result = user.passwordreset(username,email);
            if (result.success===true) {
                $("#passwordreset-message").html("<div class='alert alert-success'>"+result.message+"</div>");
                $("#passwordreset-input").hide();
            } else {
                $("#passwordreset-message").html("<div class='alert alert-error'>"+result.message+"</div>");
            }
        }
    });

    $("#register-link").click(function(){
        $(".login-item").hide();
        $(".register-item").show();
        $("#error").hide();
        register_open = true;
        return false;
    });

    $("#cancel-link").click(function(){
        $(".login-item").show();
        $(".register-item").hide();
        $("#error").hide();
        register_open = false;
        return false;
    });

    $("input").keypress(function(event) {
        //login or register when pressing enter
        if (event.which == 13) {
            event.preventDefault();
            if ( register_open ) {
                register();
            } else {
                login();
            }
        }
    });

    function login(){
        var username = $("input[name='username']").val();
        var password = $("input[name='password']").val();
        var rememberme = 0; if ($("#rememberme").is(":checked")) rememberme = 1;

        var result = user.login(username,password,rememberme);

        if (result.success)
        {
            window.location.href = path+"user/view";
        }
        else
        {
            $("#error").html(result.message).show();
        }
    }

    function register(){
        var username = $("input[name='username']").val();
        var password = $("input[name='password']").val();
        var confirmpassword = $("input[name='confirm-password']").val();
        var email = $("input[name='email']").val();

        if (password != confirmpassword)
        {
            $("#error").html("Passwords do not match").show();
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
                $("#error").html(result.message).show();
            }
        }
    }

    $("#login").click(login);
    $("#register").click(register);

});
</script>

