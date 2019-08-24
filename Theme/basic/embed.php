<!doctype html>
<?php
/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/
global $path,$settings;
$v = 7;
if (!is_dir("Theme/".$settings["interface"]["theme"])) {
    $settings["interface"]["theme"] = "basic";
}
if (!in_array($settings["interface"]["themecolor"], ["blue", "sun", "standard"])) {
    $settings["interface"]["themecolor"] = "standard";
}
?>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Emoncms embed - <?php echo $route->controller.' '.$route->action.' '.$route->subaction; ?></title>
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <link rel="apple-touch-startup-image" href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/ios_load.png">
        <link rel="apple-touch-icon" href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/logo_normal.png">

        <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $path; ?>Theme/basic/emoncms-base.css?v=<?php echo $v; ?>" rel="stylesheet">
        
        <link href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/emon-<?php echo $themecolor; ?>.css?v=<?php echo $v; ?>" rel="stylesheet">
        <link href="<?php echo $path; ?>Lib/misc/sidebar.css?v=<?php echo $v; ?>" rel="stylesheet">
        
        <script type="text/javascript" src="<?php echo $path; ?>Lib/jquery-1.11.3.min.js"></script>

        <script>
            var path = "<?php echo $path ?>";
        </script>
    </head>
    <body>
        <div>
            <?php print $content; ?>
        </div>
    </body>
</html>
