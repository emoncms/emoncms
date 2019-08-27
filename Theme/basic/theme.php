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
global $route,$path,$emoncms_version,$theme,$themecolor,$favicon,$menu;

$v = 10;

if (!is_dir("Theme/".$theme)) {
    $theme = "basic";
}
if (!in_array($themecolor, ["blue", "sun", "standard"])) {
    $themecolor = "standard";
}
?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emoncms - <?php echo $route->controller.' '.$route->action.' '.$route->subaction; ?></title>
    <link rel="shortcut icon" href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/<?php echo $favicon; ?>" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-startup-image" href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/ios_load.png">
    <link rel="apple-touch-icon" href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/logo_normal.png">

    <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/emon-base.css?v=<?php echo $v; ?>" rel="stylesheet">
    <link href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/emon-<?php echo $themecolor; ?>.css?v=<?php echo $v; ?>" rel="stylesheet">
    <link href="<?php echo $path; ?>Lib/misc/sidebar.css?v=<?php echo $v; ?>" rel="stylesheet">

    <script type="text/javascript" src="<?php echo $path; ?>Lib/jquery-1.11.3.min.js"></script>

    <script type="text/javascript" src="<?php echo $path; ?>Lib/misc/sidebar.js?v=<?php echo $v; ?>"></script>
    <script>
        window.onerror = function(msg, source, lineno, colno, error) {
            // return false;
            if (msg.toLowerCase().indexOf("script error") > -1) {
                alert('Script Error: See Browser Console for Detail');
            }
            else {
                var messages = [
                    'EmonCMS Error',
                    '-------------',
                    'Message: ' + msg,
                    'Route: ' + source.replace('<?php echo $path; ?>',''),
                    'Line: ' + lineno,
                    'Column: ' + colno
                ];
                if (Object.keys(error).length > 0) {
                    messages.push('Error: ' + JSON.stringify(error));
                }
                alert(messages.join("\n"));
            }
            return true; // true == prevents the firing of the default event handler.
        }
        var path = "<?php echo $path ?>";
    </script>
</head>
<body class="fullwidth <?php if(isset($page_classes)) echo implode(' ', $page_classes) ?>">
    <div id="wrap">
        <div id="emoncms-navbar" class="navbar navbar-inverse navbar-fixed-top">
            <?php echo $mainmenu; ?>

        </div>
        <?php if (isset($submenu) && ($submenu)) { ?>

        <div id="submenu">
            <div class="container">
                <?php echo $submenu; ?>
            </div>
        </div>
        <br>
        <?php } ?>

        <div id="sidebar" class="sidebar d-flex flex-column">
            <?php if(isset($sidebar) && !empty($sidebar)) echo $sidebar; ?>
        </div>

        <?php
        $container[] = 'content-container';
        if ($route->controller=="dashboard") { 
            $container[] = '';
        } else { 
            $container[] = 'container-fluid';
        }?>
        <main class="<?php echo implode(' ',array_filter(array_unique($container))) ?>">
            <?php echo $content; ?>
        </main>
    </div><!-- eof #wrap -->
    <div id="footer">
        <?php echo _('Powered by '); ?><a href="http://openenergymonitor.org">OpenEnergyMonitor.org</a>
        <span> | <a href="https://github.com/emoncms/emoncms/releases"><?php echo $emoncms_version; ?></a></span>
    </div>

    <script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap/js/bootstrap.js"></script>

    <!-- ICONS ------------------------------------------------------------------------------------>
    <?php
        // THEME ICONS
        echo $svg_icons;
        
        // MODULE ICONS
        if(!empty($menu['includes']['icons'])) :
    ?>
    <svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <defs>
            <?php
            if(!empty($menu['includes']['icons'])) {
                foreach($menu['includes']['icons'] as $icon) {
                    echo $icon;
                }
            }
            ?>
    </svg>
    <?php
        // end of module icons
        endif;
    ?>
</body>
</html>
