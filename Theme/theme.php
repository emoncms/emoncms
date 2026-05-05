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

$v = 55;

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

    <?php

    // Consider replacing this with esbuild bundler (merge + minify) in the future

    // Main theme CSS
    load_css("Theme/css/emoncms-base.css");
    load_css("Theme/css/menu.css");
    load_css("Theme/css/card.css");
    load_css("Theme/css/form.css");
    load_css("Theme/css/modal.css");
    load_css("Theme/css/dropdown.css");
    load_css("Theme/css/datetimepicker.css");
    load_css("Theme/css/autocomplete.css");
    // Utility classes
    load_css("Theme/css/bootstrap4-utils.css");
    // Specific used icons
    load_css("Theme/css/svg-icons.css");



    // Menu Translations
    include 'Theme/menu/menu_langjs.php';

    // The main 3rd party JS libraries
    load_js("Lib/js/jquery-4.0.0.min.js");
    load_js("Lib/js/vue.global.prod-3.5.22.min.js");
    
    // Menu and translations
    load_js("Theme/menu/menu.js");
    load_js("Lib/js/gettext.js");
    ?>

    <script>
    var current_themecolor = "<?php echo $settings["interface"]["themecolor"]; ?>";
    var current_themesidebar = "dark";
    </script>
    <script src="<?php echo $path; ?>Lib/emoncms.js?v=<?php echo $v; ?>"></script>

</head>
<body class="fullwidth <?php if(isset($page_classes)) echo implode(' ', $page_classes) ?>">
    <div id="wrap">
        <div class="menu-top bg-menu-top">
            <div class="menu-l1"><ul></ul></div>
            <div class="menu-tr"><ul>
            <li id="nav-colormode-li">
                <button id="nav-colormode-btn" title="Toggle dark/light mode" onclick="navToggleColorMode()" aria-label="Toggle colour mode">
                    <span id="nav-colormode-icon" class="icon-sun"></span>
                </button>
            </li>
            <?php if ($session["read"]) { ?>
            <li class="<?php echo $session["gravatar"]?'':'no-'; ?>gravitar dropdown"><a id="user-dropdown" href="#" title="<?php echo $session["username"]." ".($session['admin']?'(Admin)':'')?>" class="grav-container img-circle d-flex dropdown-toggle" data-toggle="dropdown">
            <?php if (!$session["gravatar"]) { ?>
                <span class="icon-user" style="color:#fff"></span>
            <?php } else { ?>
                <img src="https://www.gravatar.com/avatar/<?php echo md5($session["gravatar"]); ?>?s=52&d=mp&r=g" class="grav img-circle">
            <?php } ?>
            </a>

                <ul class="dropdown-menu pull-right" style="font-size:1rem">
                    <?php if ($session["write"]) { ?>
                    <li><a href="<?php echo $path; ?>user/view" title="<?php echo ctx_tr("theme_messages","My Account"); ?>" style="line-height:30px"><span class="icon-user"></span> <?php echo ctx_tr("theme_messages","My Account"); ?></a></li>
                    <li class="divider"><a href="#"></a></li>  
                    <?php if (isset($_SESSION['adminuser'])) { ?>
                    <li><a href="<?php echo $path; ?>account/switch" title="<?php echo ctx_tr("theme_messages","Admin"); ?>" style="line-height:30px"><span class="icon-logout"></span> <?php echo ctx_tr("theme_messages","Admin"); ?></a></li>
                    <li class="divider"><a href="#"></a></li>
                    <?php } ?>
                    <?php } ?>
                    <li><a href="<?php echo $path; ?>user/logout" title="<?php echo ctx_tr("theme_messages","Logout"); ?>" style="line-height:30px"><span class="icon-logout"></span> <?php echo ctx_tr("theme_messages","Logout"); ?></a></li>
                </ul>
            </li>
            <?php } else { ?>
            <li>
              <a href="<?php echo $path; ?>" title="<?php echo ctx_tr("theme_messages","Login"); ?>">
                <div class="tr-login"><span class="icon-enter enter"></span></div>
              </a>
            </li>
            <?php } ?>
            </ul></div>
        </div>
        <div class="menu-l2"><div class="menu-l2-inner"><ul></ul></div><div id="menu-l2-controls" class="ctrl-hide"></div></div><div class="menu-l3"><ul></ul></div>
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
            var public_userid = <?php echo $session['public_userid']; ?>;
            var public_username = "<?php echo $session['public_username']; ?>";
            var session_write = <?php echo $session['write']; ?>;
            var session_read = <?php echo $session['read']; ?>;

            var q = "<?php echo preg_replace('/[^.\/_A-Za-z0-9-]/', '', $q); ?>"+location.search+location.hash;
            menu.init(<?php echo json_encode($menu); ?>,"<?php echo $session['public_username']; ?>");
            </script>
            <?php
            if (is_array($content) || is_object($content)) {
                echo json_encode($content);
            } else {
                echo $content;
            }
            ?>
        </main>
    </div><!-- eof #wrap -->
    <div id="footer">
        <?php echo ctx_tr('theme_messages','Powered by'); ?>&nbsp;<a href="https://openenergymonitor.org" target="_blank" rel="noopener">OpenEnergyMonitor.org</a>
        <span> | <a href="https://github.com/emoncms/emoncms/releases" target="_blank" rel="noopener"><?php echo $emoncms_version; ?></a></span>
    </div>

    <?php load_js("Theme/js/theme.js"); ?>

    <script>
    (function() {
        var mode = localStorage.getItem('colormode') || 'dark';
        var icon = document.getElementById('nav-colormode-icon');
        if (icon) {
            icon.classList.remove('icon-sun', 'icon-moon');
            icon.classList.add(mode === 'light' ? 'icon-moon' : 'icon-sun');
        }
    })();
    function navToggleColorMode() {
        var mode = localStorage.getItem('colormode') || 'dark';
        var next = mode === 'light' ? 'dark' : 'light';
        localStorage.setItem('colormode', next);
        var html = document.documentElement;
        if (next === 'light') {
            html.classList.add('color-mode-light');
        } else {
            html.classList.remove('color-mode-light');
        }
        var icon = document.getElementById('nav-colormode-icon');
        if (icon) {
            icon.classList.remove('icon-sun', 'icon-moon');
            icon.classList.add(next === 'light' ? 'icon-moon' : 'icon-sun');
        }
    }
    </script>
</body>
</html>

