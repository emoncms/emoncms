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

global $path, $allowusersregister;

?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>

<div style="margin: 0px auto; max-width:392px; padding:10px;">
  <div style="max-width:392px; margin-right:20px; padding-top:45px; padding-bottom:15px; color: #888;">
    <img style="margin:12px;" src="<?php echo $path; ?>Theme/emoncms_logo.png" />
  </div>

  <div class="login-container">

    <div id="login-form"  class="well" style="text-align:left">
      <p>
        <?php echo _('Username:'); ?><br/>
        <input type="text" name="username" style="width:94%"/>
      </p>

      <p class="register-item" style="display:none">
        <?php echo _('Email:'); ?>   
        <input type="text" name="email" style="width:94%"/>
      </p>

      <p>
        <?php echo _('Password:'); ?><br/>
        <input type="password" name="password" style="width:94%"/>
      </p>

      <p class="register-item" style="display:none">
        <?php echo _('Confirm password:'); ?><br/>
        <input type="password" name="confirm-password" style="width:94%"/>
      </p>

      <div id="error" class="alert alert-error" style="display:none;"></div>

      <p class="login-item">
        <input type="checkbox" id="rememberme" value="1" name="rememberme"> Remember me<br><br>
        <button id="login" class="btn btn-primary" type="button">Login</button> or 
        <?php if ($allowusersregister) { ?><a id="register-link"><?php echo _('register'); ?></a><?php } ?>
      </p>

      <p class="register-item" style="display:none">
        <button id="register" class="btn btn-primary" type="button"><?php echo _('Register'); ?></button> or 
        <a id="cancel-link"><?php echo _('cancel'); ?></a>
      </p>

    </div>

  </div>
</div>

<script>

var path = "<?php echo $path; ?>";

$("#register-link").click(function(){
  $(".login-item").hide();
  $(".register-item").show();
  $("#error").hide();
});

$("#cancel-link").click(function(){
  $(".login-item").show();
  $(".register-item").hide();
  $("#error").hide();
});

$("#login").click(function(){
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
		$("#error").show();
		$("#error").html(result.message);
	}

});

$("#register").click(function(){
  var username = $("input[name='username']").val();
  var password = $("input[name='password']").val();
  var confirmpassword = $("input[name='confirm-password']").val();
  var email = $("input[name='email']").val();

  if (password != confirmpassword) 
  {
    $("#error").show();
    $("#error").html("Passwords do not match");
  }
  else
  {
    var result = user.register(username,password,email);
	 
    if (result.success) 
    {
      var result = user.login(username,password);
      if (result.success) 
      {
        window.location.href = path+"user/view";
      }
    }
    else
    {
      $("#error").show();
      $("#error").html(result.message);
    }
  }
});

    
</script>

