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
//Please USE the "builder" every javascript modify at: /scripts/vis_langjs_builder.php
// paste source code below
?>
//START
// multigraph_edit.js
LANG_JS["Add"] = '<?php echo addslashes(_("Add")); ?>';
LANG_JS["Advanced"] = '<?php echo addslashes(_("Advanced")); ?>';
LANG_JS["Are you sure you want to delete?"] = '<?php echo addslashes(_("Are you sure you want to delete?")); ?>';
LANG_JS["Auto refresh (secs)"] = '<?php echo addslashes(_("Auto refresh (secs)")); ?>';
LANG_JS["Axis"] = '<?php echo addslashes(_("Axis")); ?>';
LANG_JS["Bars"] = '<?php echo addslashes(_("Bars")); ?>';
LANG_JS["Bar Width (%)"] = '<?php echo addslashes(_("Bar Width (%)")); ?>';
LANG_JS["Basic"] = '<?php echo addslashes(_("Basic")); ?>';
LANG_JS["Cancel"] = '<?php echo addslashes(_("Cancel")); ?>';
LANG_JS["Changed, press to save"] = '<?php echo addslashes(_("Changed, press to save")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Delete"] = '<?php echo addslashes(_("Delete")); ?>';
LANG_JS["Delete Multigraph"] = '<?php echo addslashes(_("Delete Multigraph")); ?>';
LANG_JS["Delete permanently"] = '<?php echo addslashes(_("Delete permanently")); ?>';
LANG_JS["Deleting a multigraph is permanent."] = '<?php echo addslashes(_("Deleting a multigraph is permanent.")); ?>';
LANG_JS["Feeds"] = '<?php echo addslashes(_("Feeds")); ?>';
LANG_JS["Fill"] = '<?php echo addslashes(_("Fill")); ?>';
LANG_JS["Floating time"] = '<?php echo addslashes(_("Floating time")); ?>';
LANG_JS["Graph Type"] = '<?php echo addslashes(_("Graph Type")); ?>';
LANG_JS["Left"] = '<?php echo addslashes(_("Left")); ?>';
LANG_JS["Lines"] = '<?php echo addslashes(_("Lines")); ?>';
LANG_JS["Lines with Steps"] = '<?php echo addslashes(_("Lines with Steps")); ?>';
LANG_JS["Make sure no Dashboard continue to use the deleted multigraph"] = '<?php echo addslashes(_("Make sure no Dashboard continue to use the deleted multigraph")); ?>';
LANG_JS["Name"] = '<?php echo addslashes(_("Name")); ?>';
LANG_JS["New multigraph"] = '<?php echo addslashes(_("New multigraph")); ?>';
LANG_JS["No multigraphs created yet, click new to create one:"] = '<?php echo addslashes(_("No multigraphs created yet, click new to create one:")); ?>';
LANG_JS["Not modified"] = '<?php echo addslashes(_("Not modified")); ?>';
LANG_JS["Options :"] = '<?php echo addslashes(_("Options :")); ?>';
LANG_JS["Remove feed"] = '<?php echo addslashes(_("Remove feed")); ?>';
LANG_JS["Right"] = '<?php echo addslashes(_("Right")); ?>';
LANG_JS["Saved"] = '<?php echo addslashes(_("Saved")); ?>';
LANG_JS["Select Display Type:"] = '<?php echo addslashes(_("Select Display Type:")); ?>';
LANG_JS["Select multigraph:"] = '<?php echo addslashes(_("Select multigraph:")); ?>';
LANG_JS["Show Legend"] = '<?php echo addslashes(_("Show Legend")); ?>';
LANG_JS["Show tag name"] = '<?php echo addslashes(_("Show tag name")); ?>';
LANG_JS["Skip missing data"] = '<?php echo addslashes(_("Skip missing data")); ?>';
LANG_JS["Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public."] = '<?php echo addslashes(_("Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public.")); ?>';
LANG_JS["Stack"] = '<?php echo addslashes(_("Stack")); ?>';
LANG_JS["To embed privately:"] = '<?php echo addslashes(_("To embed privately:")); ?>';
LANG_JS["Y axes limits"] = '<?php echo addslashes(_("Y axes limits")); ?>';
//END
