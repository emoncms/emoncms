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
<style>
  .main {
    max-width: 320px;
    padding: 10px;
  }
  
  .content-container { max-width:340px; }
  
  .container-fluid { padding:0px !important; }

  #login-form { margin:0;}
  
</style>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js?v=<?php echo $v ?>"></script>
<br>




<div class="main">
  <div class="well">
    <img src="<?php echo $path; ?>Theme/logo_login.png" alt="Login" width="256" height="46" />
        
    <div class="login-container">
        <form id="login-form" autocomplete="on" onsubmit="return false;">
            <div id="loginblock">
                <div class="form-group register-item" style="display:none">
                    <label><?php echo tr('Email'); ?>
                        <input type="text" name="email" tabindex="1" autocomplete="email"/>
                    </label>
                </div>

                <div class="form-group">
                    <label><?php echo tr('Username'); ?>
                        <input type="text" tabindex="2" autocomplete="username" name="username"  />
                    </label>
                </div>

                <div class="form-group">
                    <a id="passwordreset-link" class="pull-right" href="#">Forgot password?</a>
                    <label><?php echo tr('Password'); ?>
                        <input type="password" tabindex="3" autocomplete="current-password" name="password" />
                    </label>
                </div>

                <div class="form-group register-item" style="display:none">
                    <label><?php echo tr('Confirm password'); ?>
                        <input id="confirm-password" type="password" name="confirm-password" tabindex="4" autocomplete="new-password"/>
                    </label>
                </div>

                <div id="loginmessage"></div>

                <div class="form-group login-item">
                    <?php if ($settings["interface"]["enable_rememberme"]) { ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" tabindex="5" id="rememberme" value="1" name="rememberme" autocomplete="off"><?php echo '&nbsp;'.tr('Remember me'); ?>
                            </label>
                        </div>
                    <?php } ?>
                    <button id="login" class="btn btn-primary" tabindex="6" type="submit"><?php echo tr('Login'); ?></button>
                    <?php if ($allowusersregister) { echo '&nbsp;'.tr('or').'&nbsp;' ?>
                        <a id="register-link" href="#"><?php echo tr('register'); ?></a>
                    <?php } ?>
                </div>

                <div class="form-group register-item" style="display:none">
                    <button id="register" class="btn btn-primary" type="button"><?php echo tr('Register'); ?></button>
                    <?php echo '&nbsp;'.tr('or').'&nbsp;' ?>
                    <a id="cancel-link" href="#"><?php echo tr('login'); ?></a>
                </div>

            </div>

            <div id="passwordresetblock" class="collapse">
                <div class="form-group">
                    <label>Existing account name
                        <input id="passwordreset-username" type="text" autocomplete="username"/>
                    </label>
                </div>
                <div class="form-group">
                    <label>Account email address
                        <input id="passwordreset-email" type="text" autocomplete="email"/>
                    </label>
                </div>
                <button id="passwordreset-submit" class="btn btn-primary" type="button">Recover</button>
                <?php echo '&nbsp;'.tr('or').'&nbsp;' ?>
                <a id="passwordreset-link-cancel" href="#"><?php echo tr('login'); ?></a>
            </div>
            <div id="passwordresetmessage"></div>
            <p class="pt-1 mb-0"><small id="message" class="muted"><?php echo $message ?></small></p>
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
$("body").addClass("body-login");

if (verify.success!=undefined) {
    if (verify.success) {
        $("#loginmessage").html("<div class='alert alert-success'> "+verify.message+"</div>");
    } else {
        $("#loginmessage").html("<div class='alert alert-error'> "+verify.message+"</div>");
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

    user.login(username,password,rememberme,referrer, function(result){
        if (!result || result.success===undefined) {
            $("#loginmessage").html("<div class='alert alert-error'>"+result+"</div>");
            return;
        }
        
        if (result.success)
        {
            var href = result.hasOwnProperty('startingpage') ? path+result.startingpage: path; 
            window.location.href = href;
            return;
        }
        
        if (result.message=="Please verify email address") {
            $("#loginmessage").html("<div class='alert alert-error'>"+result.message+"<br><br><button class='btn resend-verify' style='float:right'>Resend</button>Click to resend<br>verification email:</div>");
        } else {
            $("#loginmessage").html("<div class='alert alert-error'>"+result.message+"</div>");
        }
    });
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
        // Set user timezone automatically using current browser timezone
        var user_timezone = 'UTC';
        if (Intl!=undefined) {
            user_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            console.log(user_timezone);
        }
            
        var result = user.register(username,password,email,user_timezone);

        if (result.success==undefined) {
            $("#loginmessage").html("<div class='alert alert-error'>"+result+"</div>");
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
                $("#loginmessage").html("<div class='alert alert-error'>"+result.message+"</div>");
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
             $("#loginmessage").html("<div class='alert alert-error'>"+result.message+"</div>");
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