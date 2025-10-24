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
global $path,$settings,$session;
$v = 10;
if (!in_array($settings["interface"]["themecolor"], ["blue","sun","standard","copper","black"])) {
    $settings["interface"]["themecolor"] = "standard";
}
?>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Emoncms embed - <?php echo $route->controller.' '.$route->action.' '.$route->subaction; ?></title>
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <link rel="apple-touch-startup-image" href="<?php echo $path; ?>Theme/ios_load.png">
        <link rel="apple-touch-icon" href="<?php echo $path; ?>Theme/logo_normal.png">

        <!-- Open Graph meta tags for social media link preview -->
        <meta property="og:title" content="Emoncms - open source energy visualisation">
        <meta property="og:description" content="Emoncms is an open-source web application for processing, logging and visualising energy, temperature and other environmental data.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo $_SERVER['REQUEST_SCHEME'] ?? 'http'; ?>://<?php echo $_SERVER['HTTP_HOST'] ?? $settings['domain']; ?><?php echo $_SERVER['REQUEST_URI'] ?? ''; ?>">
        <meta property="og:image" content="<?php echo $path; ?>emoncms_graphic.png">
        <meta property="og:site_name" content="Emoncms">

        <!-- Twitter Card meta tags for social media link preview -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Emoncms - open source energy visualisation">
        <meta name="twitter:description" content="Emoncms is an open-source web application for processing, logging and visualising energy, temperature and other environmental data.">
        <meta name="twitter:image" content="<?php echo $path; ?>emoncms_graphic.png">

        <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $path; ?>Theme/emoncms-base.css?v=<?php echo $v; ?>" rel="stylesheet">
        
        <script type="text/javascript" src="<?php echo $path; ?>Lib/jquery-3.6.0.min.js"></script>       
        <script type="text/javascript" src="<?php echo $path; ?>Lib/misc/gettext.js?v=<?php echo $v; ?>"></script>
        <script>
        var current_themecolor = "<?php echo $settings["interface"]["themecolor"]; ?>";
        var current_themesidebar = "dark";
    
        var path = "<?php echo $path; ?>";
        var public_userid = <?php echo $session['public_userid']; ?>;
        var public_username = "<?php echo $session['public_username']; ?>";
        var session_write = <?php echo $session['write']; ?>;
        var session_read = <?php echo $session['read']; ?>;    
        
        </script>
        <script src="<?php echo $path; ?>Lib/emoncms.js?v=<?php echo $v; ?>"></script>
    </head>
    <body>
        <div>
            <?php print $content; ?>
        </div>
    </body>
</html>
