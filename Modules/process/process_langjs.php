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

// Create a Javascript associative array who contain all sentences from module
?>
var LANG_JS = new Array();
function _Tr(key)
{
<?php // will return the default value if LANG_JS[key] is not defined. ?>
    return LANG_JS[key] || key;
}
<?php
//Please USE the "builder" every javascript modify at: /scripts/process_langjs_builder.php
// paste source code below
?>
//START
// process_ui.js
LANG_JS["Changed, press to save"] = '<?php echo addslashes(dgettext('process_messages','Changed, press to save')); ?>';
LANG_JS["Click here for additional information about this process."] = '<?php echo addslashes(dgettext('process_messages','Click here for additional information about this process.')); ?>';
LANG_JS["Delete"] = '<?php echo addslashes(dgettext('process_messages','Delete')); ?>';
LANG_JS["Does NOT modify value passed onto next process step."] = '<?php echo addslashes(dgettext('process_messages','Does NOT modify value passed onto next process step.')); ?>';
LANG_JS["Edit"] = '<?php echo addslashes(dgettext('process_messages','Edit')); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(dgettext('process_messages','Feed')); ?>';
LANG_JS["feed last value:"] = '<?php echo addslashes(dgettext('process_messages','feed last value:')); ?>';
LANG_JS["Input"] = '<?php echo addslashes(dgettext('process_messages','Input')); ?>';
LANG_JS["input last value:"] = '<?php echo addslashes(dgettext('process_messages','input last value:')); ?>';
LANG_JS["Modified value passed onto next process step."] = '<?php echo addslashes(dgettext('process_messages','Modified value passed onto next process step.')); ?>';
LANG_JS["Move down"] = '<?php echo addslashes(dgettext('process_messages','Move down')); ?>';
LANG_JS["Move up"] = '<?php echo addslashes(dgettext('process_messages','Move up')); ?>';
LANG_JS["Not modified"] = '<?php echo addslashes(dgettext('process_messages','Not modified')); ?>';
LANG_JS["Requires REDIS."] = '<?php echo addslashes(dgettext('process_messages','Requires REDIS.')); ?>';
LANG_JS["Saved"] = '<?php echo addslashes(dgettext('process_messages','Saved')); ?>';
LANG_JS["Text"] = '<?php echo addslashes(dgettext('process_messages','Text')); ?>';
LANG_JS["Value"] = '<?php echo addslashes(dgettext('process_messages','Value')); ?>';
//END 
