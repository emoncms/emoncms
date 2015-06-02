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
//Please USE the "builder" every javascript modify at: /script/dashboard_langjs_builder.php
// paste source code below
?>
//START
// designer.js
LANG_JS["Changed, press to save"] = '<?php echo addslashes(_("Changed, press to save")); ?>';

// bar_render.js
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Colour to draw bar in"] = '<?php echo addslashes(_("Colour to draw bar in")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Graduations"] = '<?php echo addslashes(_("Graduations")); ?>';
LANG_JS["How many graduation lines to draw (only relevant if graduations are on)"] = '<?php echo addslashes(_("How many graduation lines to draw (only relevant if graduations are on)")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Num Graduations"] = '<?php echo addslashes(_("Num Graduations")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(_("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Should the graduations be shown"] = '<?php echo addslashes(_("Should the graduations be shown")); ?>';
LANG_JS["Static offset. Subtracted from value before computing position (default 0)"] = '<?php echo addslashes(_("Static offset. Subtracted from value before computing position (default 0)")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Unit type to show after value. Ex: <br>\"{Reading}{unit-string}\""] = '<?php echo addslashes(_("Unit type to show after value. Ex: <br>\"{Reading}{unit-string}\"")); ?>';
LANG_JS["Value is multiplied by scale before display. Defaults to 1"] = '<?php echo addslashes(_("Value is multiplied by scale before display. Defaults to 1")); ?>';

// button_render.js
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure."] = '<?php echo addslashes(_("Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure.")); ?>';
LANG_JS["Starting value"] = '<?php echo addslashes(_("Starting value")); ?>';
LANG_JS["Value"] = '<?php echo addslashes(_("Value")); ?>';

// cylinder_render.js
LANG_JS["Bottom feed value"] = '<?php echo addslashes(_("Bottom feed value")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Top feed value"] = '<?php echo addslashes(_("Top feed value")); ?>';

// dial_render.js
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Graduations"] = '<?php echo addslashes(_("Graduations")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(_("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Should the graduation limits be shown"] = '<?php echo addslashes(_("Should the graduation limits be shown")); ?>';
LANG_JS["Static offset. Subtracted from value before computing needle position"] = '<?php echo addslashes(_("Static offset. Subtracted from value before computing needle position")); ?>';
LANG_JS["Type"] = '<?php echo addslashes(_("Type")); ?>';
LANG_JS["Type to show"] = '<?php echo addslashes(_("Type to show")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(_("Value is multiplied by scale before display")); ?>';

// feedvalue_render.js
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';

// jgauge_render.js
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';

// led_render.js
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';

// vis_render.js
LANG_JS["0 = before value, 1 = after value"] = '<?php echo addslashes(_("0 = before value, 1 = after value")); ?>';
LANG_JS["Bottom"] = '<?php echo addslashes(_("Bottom")); ?>';
LANG_JS["Bottom feed value"] = '<?php echo addslashes(_("Bottom feed value")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Consumption"] = '<?php echo addslashes(_("Consumption")); ?>';
LANG_JS["Consumption feed value"] = '<?php echo addslashes(_("Consumption feed value")); ?>';
LANG_JS["Currency"] = '<?php echo addslashes(_("Currency")); ?>';
LANG_JS["Currency position"] = '<?php echo addslashes(_("Currency position")); ?>';
LANG_JS["Currency to show"] = '<?php echo addslashes(_("Currency to show")); ?>';
LANG_JS["Decimal points"] = '<?php echo addslashes(_("Decimal points")); ?>';
LANG_JS["delta"] = '<?php echo addslashes(_("delta")); ?>';
LANG_JS["dp"] = '<?php echo addslashes(_("dp")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed source"] = '<?php echo addslashes(_("Feed source")); ?>';
LANG_JS["Fill"] = '<?php echo addslashes(_("Fill")); ?>';
LANG_JS["Fill value"] = '<?php echo addslashes(_("Fill value")); ?>';
LANG_JS["interval"] = '<?php echo addslashes(_("interval")); ?>';
LANG_JS["Interval (seconds)-you can set \"d\" for day, \"m\" for month, or \"y\" for year"] = '<?php echo addslashes(_("Interval (seconds)-you can set \"d\" for day, \"m\" for month, or \"y\" for year")); ?>';
LANG_JS["kwhd"] = '<?php echo addslashes(_("kwhd")); ?>';
LANG_JS["kwhd source"] = '<?php echo addslashes(_("kwhd source")); ?>';
LANG_JS["Kwh price"] = '<?php echo addslashes(_("Kwh price")); ?>';
LANG_JS["Line colour in hex. Blank is use default."] = '<?php echo addslashes(_("Line colour in hex. Blank is use default.")); ?>';
LANG_JS["Mid"] = '<?php echo addslashes(_("Mid")); ?>';
LANG_JS["Mid value"] = '<?php echo addslashes(_("Mid value")); ?>';
LANG_JS["Power"] = '<?php echo addslashes(_("Power")); ?>';
LANG_JS["Power to show"] = '<?php echo addslashes(_("Power to show")); ?>';
LANG_JS["scale"] = '<?php echo addslashes(_("scale")); ?>';
LANG_JS["Scale by"] = '<?php echo addslashes(_("Scale by")); ?>';
LANG_JS["Set kwh price"] = '<?php echo addslashes(_("Set kwh price")); ?>';
LANG_JS["Solar"] = '<?php echo addslashes(_("Solar")); ?>';
LANG_JS["Solar feed value"] = '<?php echo addslashes(_("Solar feed value")); ?>';
LANG_JS["St to \"1\" to show diff between each bar. It displays an ever-increasing Wh feed as a daily\/montly\/yeayly kWh feed (set interval to \"d\", or \"m\", or \"y\" )"] = '<?php echo addslashes(_("St to \"1\" to show diff between each bar. It displays an ever-increasing Wh feed as a daily\/montly\/yeayly kWh feed (set interval to \"d\", or \"m\", or \"y\" )")); ?>';
LANG_JS["Threshold A"] = '<?php echo addslashes(_("Threshold A")); ?>';
LANG_JS["Threshold A used"] = '<?php echo addslashes(_("Threshold A used")); ?>';
LANG_JS["Threshold B"] = '<?php echo addslashes(_("Threshold B")); ?>';
LANG_JS["Threshold B used"] = '<?php echo addslashes(_("Threshold B used")); ?>';
LANG_JS["Top"] = '<?php echo addslashes(_("Top")); ?>';
LANG_JS["Top feed value"] = '<?php echo addslashes(_("Top feed value")); ?>';
LANG_JS["Ufac"] = '<?php echo addslashes(_("Ufac")); ?>';
LANG_JS["Ufac value"] = '<?php echo addslashes(_("Ufac value")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["units"] = '<?php echo addslashes(_("units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';
//END 