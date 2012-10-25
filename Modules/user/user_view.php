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

?>

  <h2><?php echo _('User: '); ?><?php echo $user['username']; ?></h2>
  <?php  
  /*
  * Create combo with available languages
  */
  echo '<form class="well form-inline" action="setlang" method="get">';
  echo '<span class="help-block">'._("Select preferred language").'</span>';  
  echo '<select name="lang">';
  
  if ($user['lang']=='')
    echo '<option selected value="">'._("Browser language").'</option>';
  else 
    echo '<option value="">'._("Browser language").'</option>';
    
    
  foreach (get_available_languages() as $entry) 
  {
    if ($entry == $user['lang'])
      echo '<option selected value="'.$entry.'">'._($entry).'</option>';
    else
      echo '<option value="'.$entry.'">'._($entry).'</option>';
  }
               
  echo '</select>';   

    
  echo '<input type="submit" value="'._("Save").'" class="btn">';
  echo '</form>';
  
  ?>

  <form class="well" action="../time/set" method="get">
    <h3><?php echo _("Local time"); ?></h3>

    <label><?php echo _("Time offset in hours:"); ?></label>
    <input type="edit" name="offset" value="<?php echo $user['timeoffset']; ?>" />

    <input type="submit" class="btn btn-danger" value="<?php echo _('Set'); ?>" />
  </form>
               
  <form class="well" action="changedetails" method="post">
    <h3><?php echo _('Change details'); ?></h3>

    <label><?php echo _('Username:'); ?></label>
    <input type="username" name="username" value="<?php echo $user['username']; ?>" />

    <label><?php echo _('Email:'); ?></label>
    <input type="email" name="email" value="<?php echo $user['email']; ?>" />
    <br>
    <input type="submit" class="btn btn-danger" value="<?php echo _('Change'); ?>" />
  </form>

  <form class="well" action="changepass" method="post">
    <h3><?php echo _('Change password'); ?></h3>
    <label><?php echo _('Current password:'); ?></label>
    <input type="password" name="oldpass" />
        
    <label><?php echo _('New password:'); ?></label>
    <input type="password" name="newpass"/>
    <br>
    <input type="submit" class="btn btn-danger" value="<?php echo _('Change'); ?>" />
  </form>
  
  <h3><?php echo _('Installed modules'); ?></h3>
  <?php
    foreach (emoncms_modules::getInstance()->get_registered_modules() as $emoncms_module_instance) {
      echo get_class($emoncms_module_instance).' '.$emoncms_module_instance->description().'<br>';       
    };
    
  ?>
<?php

/*
 * Fake code to be detected by POedit to translate languages name
 * Do you know a better way to do that? If not here POedit will delete them in the mo file 
 * Compiler (php interpreter will ignore it)
 */
{
  _('en_EN');
  _('es_ES');
  _('nl_BE');
  _('nl_NL');     
	_('fr_FR');	
}
?>
