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
      <p><?php echo _('New password'); ?></p>
        <input class="span2" name="passwd1" type="password" style="width:94%">       
        <p><?php echo _('Repeat password'); ?></p>        
        <input class="span2" name="passwd2" type="password" style="width:94%">                  

      <div id="resetform">
       <div id="error" class="alert alert-error" style="display:none;"></div>
      <p>        

      <button id="resetpassword" class="btn btn-primary" type="button"><?php echo _('Set password'); ?></button>      
     </div>
    </div>
          
</div>

<script>

var path = "<?php echo $path; ?>";

$("#resetpassword").click(function(){
  var passwd1 = $("input[name='passwd1']").val();
  var passwd2 = $("input[name='passwd2']").val();

  if (passwd1 != passwd2) 
  {    
    $("#error").show();
    $("#error").html("Passwords must match");
  }
  else if ((passwd1 == '') || (passwd2 == ''))
  {
    $("#error").show();
    $("#error").html("Passwords cant be null");    
  }
  else
  {
    $("#error").hide();    

               
  }
});

</script>