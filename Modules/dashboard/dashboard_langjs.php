<?php
  // Loaded like JS File, so we need to specify domain for getText translation
  $domain = "messages";
  bindtextdomain($domain, "locale");
  bind_textdomain_codeset($domain, 'UTF-8');
  textdomain($domain);
?>

// Create a Javascript associative array who contain all sentences from module  
var LANG_JS = new Array();

// designer.js
LANG_JS["Changed, press to save"]       = '<?php echo addslashes(_("Changed, press to save")); ?>';

// Common Widgets
LANG_JS["Feed"]                         = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"]                   = '<?php echo addslashes(_("Feed value")); ?>';

LANG_JS["Value"]                        = '<?php echo addslashes(_("Value")); ?>';
LANG_JS["Value to show"]                = '<?php echo addslashes(_("Value to show")); ?>';

LANG_JS["Units"]                        = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"]                = '<?php echo addslashes(_("Units to show")); ?>';

LANG_JS["Type"]                         = '<?php echo addslashes(_("Type")); ?>';
LANG_JS["Type to show"]                 = '<?php echo addslashes(_("Type to show")); ?>';

LANG_JS["Max value"]                    = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"]            = '<?php echo addslashes(_("Max value to show")); ?>';

// button_render.js
LANG_JS["Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure."] 
                                        = '<?php echo addslashes(_("Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure.")); ?>';
LANG_JS["Starting value"]               = '<?php echo addslashes(_("Starting value")); ?>';

// cylinder_render.js
LANG_JS["Bottom"]                       = '<?php echo addslashes(_("Bottom")); ?>';
LANG_JS["Top"]                          = '<?php echo addslashes(_("Top")); ?>';
LANG_JS["Bottom feed value"]            = '<?php echo addslashes(_("Bottom feed value")); ?>';  
LANG_JS["Top feed value"]               = '<?php echo addslashes(_("Top feed value")); ?>';

// dial_render.js
LANG_JS["Scale"]                        = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Scale to show"]                = '<?php echo addslashes(_("Scale to show")); ?>';
            

// vis_render.js
LANG_JS["Fill"]                         = '<?php echo addslashes(_("Fill")); ?>';
LANG_JS["Fill value"]                   = '<?php echo addslashes(_("Fill value")); ?>';       
LANG_JS["Currency"]                     = '<?php echo addslashes(_("Currency")); ?>';
LANG_JS["Currency to show"]             = '<?php echo addslashes(_("Currency to show")); ?>';
LANG_JS["Kwh price"]                    = '<?php echo addslashes(_("Kwh price")); ?>';
LANG_JS["Set kwh price"]                = '<?php echo addslashes(_("Set kwh price")); ?>'; 
LANG_JS["kwhd"]                         = '<?php echo addslashes(_("kwhd")); ?>'; 
LANG_JS["kwhd source"]                  = '<?php echo addslashes(_("kwhd source")); ?>';
LANG_JS["Power"]                        = '<?php echo addslashes(_("Power")); ?>';
LANG_JS["Power to show"]                = '<?php echo addslashes(_("Power to show")); ?>';
LANG_JS["Threshold A"]                  = '<?php echo addslashes(_("Threshold A")); ?>';
LANG_JS["Threshold B"]                  = '<?php echo addslashes(_("Threshold B")); ?>';
LANG_JS["Threshold A used"]             = '<?php echo addslashes(_("Threshold A used")); ?>';
LANG_JS["Threshold B used"]             = '<?php echo addslashes(_("Threshold B used")); ?>';       
LANG_JS["Consumption"]                  = '<?php echo addslashes(_("Consumption")); ?>';
LANG_JS["Solar"]                        = '<?php echo addslashes(_("Solar")); ?>';
LANG_JS["Consumption feed value"]       = '<?php echo addslashes(_("Consumption feed value")); ?>';
LANG_JS["Solar feed value"]             = '<?php echo addslashes(_("Solar feed value")); ?>';
LANG_JS["Ufac"]                         = '<?php echo addslashes(_("Ufac")); ?>';
LANG_JS["Ufac value"]                   = '<?php echo addslashes(_("Ufac value")); ?>';    
LANG_JS["Mid"]                          = '<?php echo addslashes(_("Mid")); ?>';
LANG_JS["Mid value"]                    = '<?php echo addslashes(_("Mid value")); ?>';  