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
<?php
load_css("Modules/user/login_block.css");
load_js("Modules/user/user.js");
?>

<div class="login-page" data-bs-theme="light">
    <div class="card login-card">
        <div class="login-head">
            <div class="login-brand">
                <img src="<?php echo $path; ?>Theme/emoncms-logo.svg" alt="" width="50" height="43">
                <span><b>emon</b>cms</span>
            </div>
            <p>Open-source energy visualisation</p>
        </div>

        <div class="card-body">
            <form id="login-form" autocomplete="on" onsubmit="return false;">
                <div id="loginblock" class="collapse show">
                    <h4 class="login-title register-item hide"><?php echo tr('Create account'); ?></h4>

                    <div class="login-field register-item hide">
                        <label class="form-label" for="login-email"><?php echo tr('Email'); ?></label>
                        <input id="login-email" class="form-control" type="text" placeholder="<?php echo tr('Enter your email'); ?>" name="email" tabindex="1" autocomplete="email"/>
                    </div>

                    <div class="login-field">
                        <label class="form-label" for="login-username"><?php echo tr('Username'); ?></label>
                        <input id="login-username" class="form-control" type="text" placeholder="<?php echo tr('Enter your username'); ?>" tabindex="2" autocomplete="username" name="username"/>
                    </div>

                    <div class="login-field">
                        <label class="form-label" for="login-password"><?php echo tr('Password'); ?></label>
                        <input id="login-password" class="form-control" type="password" placeholder="<?php echo tr('Enter your password'); ?>" tabindex="3" autocomplete="current-password" name="password"/>
                    </div>

                    <div class="login-field register-item hide">
                        <label class="form-label" for="confirm-password"><?php echo tr('Confirm password'); ?></label>
                        <input class="form-control" id="confirm-password" type="password" placeholder="<?php echo tr('Enter your password again'); ?>" name="confirm-password" tabindex="4" autocomplete="new-password"/>
                    </div>

                    <div id="loginmessage"></div>

                    <div class="login-item">
                        <div class="login-options">
                            <?php if ($settings["interface"]["enable_rememberme"]) { ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" tabindex="5" id="rememberme" value="1" name="rememberme" autocomplete="off">
                                <label class="form-check-label" for="rememberme"><?php echo tr('Remember me'); ?></label>
                            </div>
                            <?php } ?>
                            <a id="passwordreset-link" href="#"><?php echo tr('Forgot password?'); ?></a>
                        </div>
                        <button id="login" class="btn btn-primary login-btn" tabindex="6" type="submit"><?php echo tr('Login'); ?></button>
                        <?php if ($allowusersregister) { ?>
                        <p class="login-switch"><?php echo tr('No account?'); ?> <a id="register-link" href="#"><?php echo tr('Register'); ?></a></p>
                        <?php } ?>
                    </div>

                    <div class="register-item hide">
                        <button id="register" class="btn btn-primary login-btn" type="button"><?php echo tr('Register'); ?></button>
                        <p class="login-switch"><?php echo tr('Have an account?'); ?> <a id="cancel-link" href="#"><?php echo tr('Log in'); ?></a></p>
                    </div>
                </div>

                <div id="passwordresetblock" class="collapse">
                    <h4 class="login-title"><?php echo tr('Reset password'); ?></h4>
                    <div class="login-field">
                        <label class="form-label" for="passwordreset-username"><?php echo tr('Existing account name'); ?></label>
                        <input class="form-control" id="passwordreset-username" type="text" autocomplete="username"/>
                    </div>
                    <div class="login-field">
                        <label class="form-label" for="passwordreset-email"><?php echo tr('Account email address'); ?></label>
                        <input class="form-control" id="passwordreset-email" type="text" autocomplete="email"/>
                    </div>
                    <button id="passwordreset-submit" class="btn btn-primary login-btn" type="button"><?php echo tr('Recover'); ?></button>
                    <p class="login-switch"><a id="passwordreset-link-cancel" href="#"><?php echo tr('Back to log in'); ?></a></p>
                </div>
                <div id="passwordresetmessage"></div>
                <p id="message" class="login-note"><?php echo $message ?></p>
                <input name="referrer" type="hidden" value="<?php echo $referrer ?>">
            </form>
        </div>
    </div>
</div>

<script>
"use strict";

menu.disable();

var verify = <?php echo json_encode($verify); ?>;
var register_open = false;

if (verify.success!=undefined) {
    if (verify.success) {
        $("#loginmessage").html("<div class='alert alert-success'> "+verify.message+"</div>");
    } else {
        $("#loginmessage").html("<div class='alert alert-danger'> "+verify.message+"</div>");
    }
}

var passwordreset = "<?php echo $settings['interface']['enable_password_reset']; ?>";
$(document).ready(function() {
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
        $("#passwordresetmessage").html("<div class='alert alert-danger'>Please enter username and email address</div>");
    } else {
        var result = user.passwordreset(username,email);
        if (result.success===true) {
            $("#passwordresetmessage").html("<div class='alert alert-success'>"+result.message+"</div>");
            $("#passwordresetblock").hide();
        } else {
            $("#passwordresetmessage").html("<div class='alert alert-danger'>"+result.message+"</div>");
        }
    }
});

$("#register-link").click(function(){
    $(".login-item").hide();
    $(".register-item").show();
    $("#loginmessage").html("");
    register_open = true;
    $(this).trigger('registration:shown')
    if (passwordreset) $("#passwordreset-link").hide();
    return false;
});

$("#cancel-link").click(function(){
    $(".login-item").show();
    $(".register-item").hide();
    $("#loginmessage").html("");
    register_open = false;
    $(this).trigger('registration:hidden')
    if (passwordreset) $("#passwordreset-link").show();
    return false;
});

$('input').on('keypress', function(e) {
    //login or register when pressing enter
    if (e.which == 13) {
        e.preventDefault();
        if ( register_open ) {
            register();
        } else {
            login();
        }
    }
});

$('#login').click(function() { login(); });
$('#register').click(function() { register(); });

$("#loginmessage").on("click", ".resend-verify", function(){ resend_verify(); });

function login(){
    var username = $("input[name='username']").val();
    var password = $("input[name='password']").val();
    var referrer = $("input[name='referrer']").val();
    var rememberme = 0; if ($("#rememberme").is(":checked")) rememberme = 1;

    var result = user.login(username,password,rememberme,referrer);

    if (result.success==undefined) {
        $("#loginmessage").html("<div class='alert alert-danger'>"+result+"</div>");
        return false;
    
    } else {
        if (result.success)
        {
            var href = result.hasOwnProperty('startingpage') ? path+result.startingpage: path; 
            window.location.href = href;
            return true;
        }
        else
        {
            if (result.message=="Please verify email address") {
                $("#loginmessage").html("<div class='alert alert-danger'>"+result.message+"<div class='mt-2'><button class='btn btn-default btn-sm resend-verify' type='button'>Resend verification email</button></div></div>");
            } else {
                $("#loginmessage").html("<div class='alert alert-danger'>"+result.message+"</div>");
            }
            return false;
        }
    }
}

function register(){
    var username = $("input[name='username']").val();
    var password = $("input[name='password']").val();
    var confirmpassword = $("input[name='confirm-password']").val();
    var email = $("input[name='email']").val();

    if (password != confirmpassword)
    {
        $("#loginmessage").html("<div class='alert alert-danger'>Passwords do not match</div>");
    }
    else
    {
        // Set user timezone automatically using current browser timezone
        var user_timezone = 'UTC';
        if (Intl!=undefined) {
            user_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            console.log(user_timezone);
        }
            
        var result = user.register(username,password,email,user_timezone);

        if (result.success==undefined) {
            $("#loginmessage").html("<div class='alert alert-danger'>"+result+"</div>");
            return false;
        
        } else {
            if (result.success) {
                if (result.verifyemail) {
                    $(".login-item").show();
                    $(".register-item").hide();
                    $("#loginmessage").html("");
                    register_open = false;
                    $("#loginmessage").html("<div class='alert alert-success'>"+result.message+"</div>");
                } else {
                    login();
                }
                
            } else {
                $("#loginmessage").html("<div class='alert alert-danger'>"+result.message+"</div>");
            }
        }
    }
}

function resend_verify()
{
    var username = $("input[name='username']").val();
    
    $.ajax({
      url: path+"user/resend-verify.json",
      data: "&username="+encodeURIComponent(username),
      dataType: "json",
      success: function(result) {
         if (result.success) {
             $("#loginmessage").html("<div class='alert alert-success'>"+result.message+"</div>");
         } else {
             $("#loginmessage").html("<div class='alert alert-danger'>"+result.message+"</div>");
         }
      } 
    });
}

$(function() {
    focusFirst()
    $(document).on('registration:shown registration:hidden',focusFirst)
    $("#passwordresetblock").on('hidden',focusFirst)
    $("#passwordresetblock").on('shown', function(event){
        focusFirst(event, '#passwordreset-username')
    })
})
/**
 * set focus on first input element
 * @param {TouchEvent|MouseEvent|jQuery.Event} event
 * @param {string} selector
 * @return void
 */
function focusFirst(event,selector) {
    var elem
    if(!event) event = {type:'none'}
    if(!selector) {
        elem = $(':text:visible').first()
    } else {
        elem = $(selector).first()
    }
    if(!elem || !elem.hasOwnProperty('length') || elem.length === 0) return
    elem.focus()
}
</script>
