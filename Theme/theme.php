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
global $settings;
global $ltime,$path,$emoncms_version,$menu,$session;
load_language_files("Theme/locale", "theme_messages");

$q = ""; if (isset($_GET['q'])) $q = $_GET['q'];

$v = 31;

if (!in_array($settings["interface"]["themecolor"], ["blue","sun","yellow2","standard","copper","black","green"])) {
    $settings["interface"]["themecolor"] = "standard";
}
?>
<html class="theme-<?php echo $settings["interface"]["themecolor"]; ?> sidebar-dark">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1">
    <title>Emoncms - <?php echo $route->controller.' '.$route->action.' '.$route->subaction; ?></title>
    <link rel="shortcut icon" href="<?php echo $path; ?>Theme/<?php echo $settings["interface"]["favicon"]; ?>" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-startup-image" href="<?php echo $path; ?>Theme/ios_load.png">
    <link rel="apple-touch-icon" href="<?php echo $path; ?>Theme/logo_normal.png">

    <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="<?php echo $path; ?>Theme/emoncms-base.css?v=<?php echo $v; ?>" rel="stylesheet">
    <link href="<?php echo $path; ?>Lib/menu/menu.css?v=<?php echo $v; ?>" rel="stylesheet">

    <script type="text/javascript" src="<?php echo $path; ?>Lib/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Lib/menu/menu.js?v=<?php echo $v; ?>"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Lib/misc/gettext.js?v=<?php echo $v; ?>"></script>
    
    <script>
    var current_themecolor = "<?php echo $settings["interface"]["themecolor"]; ?>";
    var current_themesidebar = "dark";
    </script>
    <script src="<?php echo $path; ?>Lib/emoncms.js?v=<?php echo $v; ?>"></script>
    <?php echo $svg_icons; // THEME ICONS ?>
</head>
<body class="fullwidth <?php if(isset($page_classes)) echo implode(' ', $page_classes) ?>">
    <div id="wrap">
        <div class="menu-top bg-menu-top">
            <div class="menu-l1"><ul></ul></div>
            <div class="menu-tr"><ul>
            
            <?php if ($session["write"]) { ?>
            <li class="<?php echo $session["gravatar"]?'':'no-'; ?>gravitar dropdown"><a id="user-dropdown" href="#" title="<?php echo $session["username"]." ".($session['admin']?'(Admin)':'')?>" class="grav-container img-circle d-flex dropdown-toggle" data-toggle="dropdown">
            <?php if (!$session["gravatar"]) { ?>
                <svg class="icon user" style="color:#fff"><use xlink:href="#icon-user"></use></svg>
            <?php } else { ?>
                <img src="https://www.gravatar.com/avatar/<?php echo md5($session["gravatar"]); ?>?s=52&d=mp&r=g" class="grav img-circle">
            <?php } ?>
            </a>

                <ul class="dropdown-menu pull-right" style="font-size:1rem">
                    <li><a href="<?php echo $path; ?>user/view" title="<?php echo _("My Account"); ?>" style="line-height:30px"><svg class="icon"><use xlink:href="#icon-user"></use></svg> <?php echo _("My Account"); ?></a></li>
                    <li class="divider"><a href="#"></a></li>
                    <li><a href="<?php echo $path; ?>user/logout" title="<?php echo _("Logout"); ?>" style="line-height:30px"><svg class="icon"><use xlink:href="#icon-logout"></use></svg> <?php echo _("Logout"); ?></a></li>
                </ul>
            </li>
            <?php } else { ?>
            <li>
              <a href="<?php echo $path; ?>" title="<?php echo _("Login"); ?>">
                <div class="tr-login"><svg class="icon enter"><use xlink:href="#icon-enter"></use></svg></div>
              </a>
            </li>
            <?php } ?>
            </ul></div>
        </div>
        <div class="menu-l2"><div class="menu-l2-inner"><ul></ul></div><div id="menu-l2-controls"></div></div><div class="menu-l3"><ul></ul></div>
        <?php
        $contentContainerClasses[] = 'content-container';
        
        if ($route->controller=="dashboard") { 
            $contentContainerClasses[] = '';
        } else { 
            $contentContainerClasses[] = 'container-fluid';
        }?>
        <main class="<?php echo implode(' ',array_filter(array_unique($contentContainerClasses))) ?>">
            <script>
            // Draw menu just before drawing content but after defining content-container
            var path = "<?php echo $path; ?>";
            var q = "<?php echo preg_replace('/[^.\/_A-Za-z0-9-]/', '', $q); ?>"+location.search+location.hash;
            menu.init(<?php echo json_encode($menu); ?>);
            </script>
            <?php echo $content; ?>
        </main>
    </div><!-- eof #wrap -->
    <div id="footer">
        <?php echo dgettext('theme_messages','Powered by'); ?>&nbsp;<a href="http://openenergymonitor.org" target="_blank" rel="noopener">OpenEnergyMonitor.org</a>
        <span> | <a href="https://github.com/emoncms/emoncms/releases" target="_blank" rel="noopener"><?php echo $emoncms_version; ?></a></span>
    </div>

    <script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap/js/bootstrap.js?v=2"></script>

<!-- ICONS --------------------------------------------- -->


<?php
    // MODULE ICONS
    if(!empty($menu['includes']['icons'])) :
?>
<svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <defs>
        <?php
        foreach($menu['includes']['icons'] as $icon):
            echo $icon;
        endforeach;
        ?>
    </defs>
</svg>
<?php
    // end of module icons
    endif;
?>
</body>
</html>

