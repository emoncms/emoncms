<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */
global $path;
 ?>

<h2><?php echo _('Reset password'); ?></h2>
<div id="mainresetform" style="margin: 0px auto; max-width:392px; padding:10px;">

    <div id="resetpassw-form"  class="well" style="text-align:left">
      <p><?php echo _('Please, indicate your email address to send there the reset password instructions'); ?></p>

      <div id="resetform">
       <div id="error" class="alert alert-error" style="display:none;"></div>
      <p>        
        <div class="input-prepend">
          <span class="add-on">@</span>
          <input class="span2" name="email" type="text" placeholder="<?php echo _('Email address'); ?>" style="width:94%">
        </div>
      </p>    

      <button id="resetpassword" class="btn btn-primary" tabindex="4" type="button"><?php echo _('Reset password'); ?></button>
      <a href="<?php echo $path; ?>"><?php echo _('or return to login form'); ?></a>
     </div>
    </div>
          
</div>

<script>

var path = "<?php echo $path; ?>";

$("#resetpassword").click(function(){
  var email = $("input[name='email']").val();

  if (!validateEmail(email)) 
  {    
    $("#error").show();
    $("#error").html("Please, enter a valid email address");
  }
  else
  {
    $.ajax({ 
      url: path+"resetpassword/resetpassword.json",
      data: "email="+email,
      dataType: 'json',
      success: function(result){
        if (result == true)        
          $("#mainresetform").html("<div class='alert alert-success'><?php echo _('Instructions for reset password will be send to the indicated email address.'); ?>&nbsp;<a href='<?php echo $path; ?>'><?php echo _('Return to login form'); ?></a></div>");            
      }
    });        
  }
});

function validateEmail(email) { 
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
} 
    
</script>