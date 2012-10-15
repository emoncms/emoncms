<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */
?>
  <h2><?php echo _("Notify settings"); ?></h2>
  <form class="well" action="../notify/setrecipients" method="get">   
	  <label class="control-label" for="inputIcon"><?php echo _("Notification recipiants (To): "); ?></label>
	  <div class="controls">
  	  <div class="input-prepend">
    	  <span class="add-on"><i class="icon-envelope"></i></span><input class="span3" id="inputIcon" type="text" name="recipients" value="<?php echo $recipients; ?>">
      </div>
      <input type="submit" value="<?php echo _("Save"); ?>" class="btn"/>
    </div>   
  </form>
