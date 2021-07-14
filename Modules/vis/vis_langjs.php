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
var LANG_JS_VIS = new Array();
function _Tr_Vis(key)
{
<?php // will return the default value if LANG_JS[key] is not defined. ?>
    return LANG_JS_VIS[key] || key;
}
<?php
//Please USE the "builder" every javascript modify at: /scripts/vis_langjs_builder.php
// paste source code below
?>
//START
// multigraph_edit.js
LANG_JS_VIS["Add"] = '<?php echo addslashes(dgettext('vis_messages',"Add")); ?>';
LANG_JS_VIS["Advanced"] = '<?php echo addslashes(dgettext('vis_messages',"Advanced")); ?>';
LANG_JS_VIS["Are you sure you want to delete?"] = '<?php echo addslashes(dgettext('vis_messages',"Are you sure you want to delete?")); ?>';
LANG_JS_VIS["Auto refresh (secs)"] = '<?php echo addslashes(dgettext('vis_messages',"Auto refresh (secs)")); ?>';
LANG_JS_VIS["Axis"] = '<?php echo addslashes(dgettext('vis_messages',"Axis")); ?>';
LANG_JS_VIS["Background colour"] = '<?php echo addslashes(dgettext('vis_messages',"Background colour")); ?>';
LANG_JS_VIS["Bars"] = '<?php echo addslashes(dgettext('vis_messages',"Bars")); ?>';
LANG_JS_VIS["Bar Width (%)"] = '<?php echo addslashes(dgettext('vis_messages',"Bar Width (%)")); ?>';
LANG_JS_VIS["Basic"] = '<?php echo addslashes(dgettext('vis_messages',"Basic")); ?>';
LANG_JS_VIS["Cancel"] = '<?php echo addslashes(dgettext('vis_messages',"Cancel")); ?>';
LANG_JS_VIS["Changed, press to save"] = '<?php echo addslashes(dgettext('vis_messages',"Changed, press to save")); ?>';
LANG_JS_VIS["Colour"] = '<?php echo addslashes(dgettext('vis_messages',"Colour")); ?>';
LANG_JS_VIS["Delete"] = '<?php echo addslashes(dgettext('vis_messages',"Delete")); ?>';
LANG_JS_VIS["Delete Multigraph"] = '<?php echo addslashes(dgettext('vis_messages',"Delete Multigraph")); ?>';
LANG_JS_VIS["Delete permanently"] = '<?php echo addslashes(dgettext('vis_messages',"Delete permanently")); ?>';
LANG_JS_VIS["Deleting a multigraph is permanent."] = '<?php echo addslashes(dgettext('vis_messages',"Deleting a multigraph is permanent.")); ?>';
LANG_JS_VIS["Feeds"] = '<?php echo addslashes(dgettext('vis_messages',"Feeds")); ?>';
LANG_JS_VIS["Fill"] = '<?php echo addslashes(dgettext('vis_messages',"Fill")); ?>';
LANG_JS_VIS["Floating time"] = '<?php echo addslashes(dgettext('vis_messages',"Floating time")); ?>';
LANG_JS_VIS["Graph Type"] = '<?php echo addslashes(dgettext('vis_messages',"Graph Type")); ?>';
LANG_JS_VIS["Left"] = '<?php echo addslashes(dgettext('vis_messages',"Left")); ?>';
LANG_JS_VIS["Lines"] = '<?php echo addslashes(dgettext('vis_messages',"Lines")); ?>';
LANG_JS_VIS["Lines with Steps"] = '<?php echo addslashes(dgettext('vis_messages',"Lines with Steps")); ?>';
LANG_JS_VIS["Make sure no Dashboard continue to use the deleted multigraph"] = '<?php echo addslashes(dgettext('vis_messages',"Make sure no Dashboard continue to use the deleted multigraph")); ?>';
LANG_JS_VIS["Name"] = '<?php echo addslashes(dgettext('vis_messages',"Name")); ?>';
LANG_JS_VIS["New multigraph"] = '<?php echo addslashes(dgettext('vis_messages',"New multigraph")); ?>';
LANG_JS_VIS["No multigraphs created yet, click new to create one:"] = '<?php echo addslashes(dgettext('vis_messages',"No multigraphs created yet, click new to create one:")); ?>';
LANG_JS_VIS["Not modified"] = '<?php echo addslashes(dgettext('vis_messages',"Not modified")); ?>';
LANG_JS_VIS["Options :"] = '<?php echo addslashes(dgettext('vis_messages',"Options :")); ?>';
LANG_JS_VIS["Remove feed"] = '<?php echo addslashes(dgettext('vis_messages',"Remove feed")); ?>';
LANG_JS_VIS["Right"] = '<?php echo addslashes(dgettext('vis_messages',"Right")); ?>';
LANG_JS_VIS["Saved"] = '<?php echo addslashes(dgettext('vis_messages',"Saved")); ?>';
LANG_JS_VIS["Select Display Type:"] = '<?php echo addslashes(dgettext('vis_messages',"Select Display Type:")); ?>';
LANG_JS_VIS["Select multigraph:"] = '<?php echo addslashes(dgettext('vis_messages',"Select multigraph:")); ?>';
LANG_JS_VIS["Show Legend"] = '<?php echo addslashes(dgettext('vis_messages',"Show Legend")); ?>';
LANG_JS_VIS["Show tag name"] = '<?php echo addslashes(dgettext('vis_messages',"Show tag name")); ?>';
LANG_JS_VIS["Skip missing data"] = '<?php echo addslashes(dgettext('vis_messages',"Skip missing data")); ?>';
LANG_JS_VIS["Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public."] = '<?php echo addslashes(dgettext('vis_messages',"Some of the feeds selected are not public, to embed a visualisation publicly first make the feeds that you want to use public.")); ?>';
LANG_JS_VIS["Stack"] = '<?php echo addslashes(dgettext('vis_messages',"Stack")); ?>';
LANG_JS_VIS["To embed privately:"] = '<?php echo addslashes(dgettext('vis_messages',"To embed privately:")); ?>';
LANG_JS_VIS["Y axes limits"] = '<?php echo addslashes(dgettext('vis_messages',"Y axes limits")); ?>';

// vis_render.js
LANG_JS_VIS["0 = before value, 1 = after value"] = '<?php echo addslashes(dgettext('vis_messages',"0 = before value, 1 = after value")); ?>';
LANG_JS_VIS["Background"] = '<?php echo addslashes(dgettext('vis_messages',"Background")); ?>';
LANG_JS_VIS["Background colour in hex. Blank is use default."] = '<?php echo addslashes(dgettext('vis_messages',"Background colour in hex. Blank is use default.")); ?>';
LANG_JS_VIS["Bottom"] = '<?php echo addslashes(dgettext('vis_messages',"Bottom")); ?>';
LANG_JS_VIS["Bottom colour"] = '<?php echo addslashes(dgettext('vis_messages',"Bottom colour")); ?>';
LANG_JS_VIS["Bottom feed value"] = '<?php echo addslashes(dgettext('vis_messages',"Bottom feed value")); ?>';
LANG_JS_VIS["calulate habs"] = '<?php echo addslashes(dgettext('vis_messages',"calulate habs")); ?>';
LANG_JS_VIS["Colour"] = '<?php echo addslashes(dgettext('vis_messages',"Colour")); ?>';
LANG_JS_VIS["Consumption"] = '<?php echo addslashes(dgettext('vis_messages',"Consumption")); ?>';
LANG_JS_VIS["Consumption feed value"] = '<?php echo addslashes(dgettext('vis_messages',"Consumption feed value")); ?>';
LANG_JS_VIS["Currency"] = '<?php echo addslashes(dgettext('vis_messages',"Currency")); ?>';
LANG_JS_VIS["Currency position"] = '<?php echo addslashes(dgettext('vis_messages',"Currency position")); ?>';
LANG_JS_VIS["Currency to show"] = '<?php echo addslashes(dgettext('vis_messages',"Currency to show")); ?>';
LANG_JS_VIS["Data points"] = '<?php echo addslashes(dgettext('vis_messages',"Data points")); ?>';
LANG_JS_VIS["Day"] = '<?php echo addslashes(dgettext('vis_messages',"Day")); ?>';
LANG_JS_VIS["Decimal points"] = '<?php echo addslashes(dgettext('vis_messages',"Decimal points")); ?>';
LANG_JS_VIS["Default: 800"] = '<?php echo addslashes(dgettext('vis_messages',"Default: 800")); ?>';
LANG_JS_VIS["Default visible window interval"] = '<?php echo addslashes(dgettext('vis_messages',"Default visible window interval")); ?>';
LANG_JS_VIS["delta"] = '<?php echo addslashes(dgettext('vis_messages',"delta")); ?>';
LANG_JS_VIS["Depth"] = '<?php echo addslashes(dgettext('vis_messages',"Depth")); ?>';
LANG_JS_VIS["Display power as kW"] = '<?php echo addslashes(dgettext('vis_messages',"Display power as kW")); ?>';
LANG_JS_VIS["dp"] = '<?php echo addslashes(dgettext('vis_messages',"dp")); ?>';
LANG_JS_VIS["Feed"] = '<?php echo addslashes(dgettext('vis_messages',"Feed")); ?>';
LANG_JS_VIS["Feed source"] = '<?php echo addslashes(dgettext('vis_messages',"Feed source")); ?>';
LANG_JS_VIS["Fill"] = '<?php echo addslashes(dgettext('vis_messages',"Fill")); ?>';
LANG_JS_VIS["Fill under line"] = '<?php echo addslashes(dgettext('vis_messages',"Fill under line")); ?>';
LANG_JS_VIS["Fill value"] = '<?php echo addslashes(dgettext('vis_messages',"Fill value")); ?>';
LANG_JS_VIS["hour"] = '<?php echo addslashes(dgettext('vis_messages',"hour")); ?>';
LANG_JS_VIS["Hours"] = '<?php echo addslashes(dgettext('vis_messages',"Hours")); ?>';
LANG_JS_VIS["Interval"] = '<?php echo addslashes(dgettext('vis_messages',"Interval")); ?>';
LANG_JS_VIS["Interval (seconds)-you can set \"d\" for day, \"m\" for month, or \"y\" for year"] = '<?php echo addslashes(dgettext('vis_messages',"Interval (seconds)-you can set \"d\" for day, \"m\" for month, or \"y\" for year")); ?>';
LANG_JS_VIS["kwhd"] = '<?php echo addslashes(dgettext('vis_messages',"kwhd")); ?>';
LANG_JS_VIS["kwhd source"] = '<?php echo addslashes(dgettext('vis_messages',"kwhd source")); ?>';
LANG_JS_VIS["Kwh price"] = '<?php echo addslashes(dgettext('vis_messages',"Kwh price")); ?>';
LANG_JS_VIS["Line colour in hex. Blank is use default."] = '<?php echo addslashes(dgettext('vis_messages',"Line colour in hex. Blank is use default.")); ?>';
LANG_JS_VIS["Managed on Visualization module"] = '<?php echo addslashes(dgettext('vis_messages',"Managed on Visualization module")); ?>';
LANG_JS_VIS["minute"] = '<?php echo addslashes(dgettext('vis_messages',"minute")); ?>';
LANG_JS_VIS["minutes"] = '<?php echo addslashes(dgettext('vis_messages',"minutes")); ?>';
LANG_JS_VIS["mode"] = '<?php echo addslashes(dgettext('vis_messages',"mode")); ?>';
LANG_JS_VIS["Mode set to 'daily' can be used instead of interval for timezone based daily data"] = '<?php echo addslashes(dgettext('vis_messages',"Mode set to 'daily' can be used instead of interval for timezone based daily data")); ?>';
LANG_JS_VIS["Month"] = '<?php echo addslashes(dgettext('vis_messages',"Month")); ?>';
LANG_JS_VIS["Multigraph"] = '<?php echo addslashes(dgettext('vis_messages',"Multigraph")); ?>';
LANG_JS_VIS["Number of lines"] = '<?php echo addslashes(dgettext('vis_messages',"Number of lines")); ?>';
LANG_JS_VIS["Power"] = '<?php echo addslashes(dgettext('vis_messages',"Power")); ?>';
LANG_JS_VIS["Power to show"] = '<?php echo addslashes(dgettext('vis_messages',"Power to show")); ?>';
LANG_JS_VIS["scale"] = '<?php echo addslashes(dgettext('vis_messages',"scale")); ?>';
LANG_JS_VIS["Scale by"] = '<?php echo addslashes(dgettext('vis_messages',"Scale by")); ?>';
LANG_JS_VIS["Set kwh price"] = '<?php echo addslashes(dgettext('vis_messages',"Set kwh price")); ?>';
LANG_JS_VIS["Show difference between each bar"] = '<?php echo addslashes(dgettext('vis_messages',"Show difference between each bar")); ?>';
LANG_JS_VIS["Solar"] = '<?php echo addslashes(dgettext('vis_messages',"Solar")); ?>';
LANG_JS_VIS["Solar feed value"] = '<?php echo addslashes(dgettext('vis_messages',"Solar feed value")); ?>';
LANG_JS_VIS["Threshold A"] = '<?php echo addslashes(dgettext('vis_messages',"Threshold A")); ?>';
LANG_JS_VIS["Threshold A used"] = '<?php echo addslashes(dgettext('vis_messages',"Threshold A used")); ?>';
LANG_JS_VIS["Threshold B"] = '<?php echo addslashes(dgettext('vis_messages',"Threshold B")); ?>';
LANG_JS_VIS["Threshold B used"] = '<?php echo addslashes(dgettext('vis_messages',"Threshold B used")); ?>';
LANG_JS_VIS["Top"] = '<?php echo addslashes(dgettext('vis_messages',"Top")); ?>';
LANG_JS_VIS["Top colour"] = '<?php echo addslashes(dgettext('vis_messages',"Top colour")); ?>';
LANG_JS_VIS["Top feed value"] = '<?php echo addslashes(dgettext('vis_messages',"Top feed value")); ?>';
LANG_JS_VIS["Ufac"] = '<?php echo addslashes(dgettext('vis_messages',"Ufac")); ?>';
LANG_JS_VIS["Ufac value"] = '<?php echo addslashes(dgettext('vis_messages',"Ufac value")); ?>';
LANG_JS_VIS["units"] = '<?php echo addslashes(dgettext('vis_messages',"units")); ?>';
LANG_JS_VIS["Units"] = '<?php echo addslashes(dgettext('vis_messages',"Units")); ?>';
LANG_JS_VIS["Units to show"] = '<?php echo addslashes(dgettext('vis_messages',"Units to show")); ?>';
LANG_JS_VIS["view givoni graph"] = '<?php echo addslashes(dgettext('vis_messages',"view givoni graph")); ?>';
LANG_JS_VIS["Week"] = '<?php echo addslashes(dgettext('vis_messages',"Week")); ?>';
LANG_JS_VIS["Year"] = '<?php echo addslashes(dgettext('vis_messages',"Year")); ?>';
LANG_JS_VIS["Zoom"] = '<?php echo addslashes(dgettext('vis_messages',"Zoom")); ?>';
//END 
