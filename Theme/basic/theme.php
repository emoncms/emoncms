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
global $ltime,$path,$fullwidth,$emoncms_version,$theme,$themecolor,$favicon,$menu,$menucollapses;

$v = 2;

//compute dynamic @media properties depending on numbers and lengths of shortcuts
$maxwidth1=1200;
$maxwidth2=480;
$maxwidth3=340;
$sumlength1 = 0;
$sumlength2 = 0;
$sumlength3 = 0;
$sumlength4 = 0;
$sumlength5 = 0;
$nbshortcuts1 = 0;
$nbshortcuts2 = 0;
$nbshortcuts3 = 0;
$nbshortcuts4 = 0;
$nbshortcuts5 = 0;

foreach($menu['dashboard'] as $item){
    if(isset($item['name'])){$name = $item['name'];}
    if(isset($item['published'])){$published = $item['published'];} //only published dashboards
    if($name && $published){
        $sumlength1 += strlen($name);
        $nbshortcuts1 ++;
    }
}
foreach($menu['left'] as $item){
    if(isset($item['name'])) {$name = $item['name'];}
    $sumlength2 += strlen($name);
    $nbshortcuts2 ++;
}
if(count($menu['dropdown']) && $session['read']){
    $extra['name'] = 'Extra';
    $sumlength3 = strlen($extra['name']);
    $nbshortcuts3 ++;
}
if (count($menu['dropdownconfig'])){
    $setup['name'] = 'Setup';
    $sumlength4 = strlen($setup['name']);
    $nbshortcuts4 ++;
}
foreach ($menu['right'] as $item) {
    if (isset($item['name'])){$name = $item['name'];}
    $sumlength5 += strlen($name);
    $nbshortcuts5 ++;
}
$maxwidth1=intval((($sumlength1+$sumlength2+$sumlength3+$sumlength4+$sumlength5)+($nbshortcuts1+$nbshortcuts2+$nbshortcuts3+$nbshortcuts4+$nbshortcuts5+1)*6)*85/9);
$maxwidth2=intval(($nbshortcuts1+$nbshortcuts2+$nbshortcuts3+$nbshortcuts4+$nbshortcuts5+3)*6*75/9);
if($maxwidth2>$maxwidth1){$maxwidth2=$maxwidth1-1;}
if($maxwidth3>$maxwidth2){$maxwidth3=$maxwidth2-1;}

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
    <link href="<?php echo $path; ?>Theme/<?php echo $theme; ?>/emon-<?php echo $themecolor; ?>.css?v=<?php echo $v; ?>" rel="stylesheet">
    <link href="<?php echo $path; ?>Lib/misc/sidebar.css" rel="stylesheet">
<?php /*
<?php if ($menucollapses) { ?>
    <style>
        @media (max-width: 979px){
            .menu-description {
                display: inherit !important ;
            }
        }
        @media (min-width: 980px) and (max-width: <?php if($maxwidth1<981){$maxwidth1=981;} echo $maxwidth1; ?>px){
            .menu-text {
                display: none !important;
            }
        }
    </style>
<?php } else { ?>
    <style>
        @media (max-width: <?php echo $maxwidth1; ?>px){
            .menu-text {
                display: none !important;
            }
        }
        @media (max-width: <?php echo $maxwidth2; ?>px){
            .menu-dashboard {
                display: none !important;
            }
        }
        @media (max-width: <?php echo $maxwidth3; ?>px){
            .menu-extra {
                display: none !important;
            }
        }
    </style>
<?php } ?>
<?php */ ?>

    <script type="text/javascript" src="<?php echo $path; ?>Lib/jquery-1.11.3.min.js"></script>
    <script>
        window.onerror = function(msg, source, lineno, colno, error) {
            if (msg.toLowerCase().indexOf("script error") > -1) {
                alert('Script Error: See Browser Console for Detail');
            }
            else {
                var messages = [
                    'EmonCMS Error',
                    '-------------',
                    'Message: ' + msg,
                    'URL: ' + source,
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
    </script>
</head>
<body>
    <div id="wrap">
    <div id="emoncms-navbar" class="navbar navbar-inverse navbar-fixed-top">
        <div class="navbar-inner">
        <?php if ($menucollapses) { ?>
            <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <img src="<?php echo $path; ?>Theme/<?php echo $theme; ?>/favicon.png" style="width:28px;"/>
            </button>
            <div class="nav-collapse collapse">
        <?php } ?>

            <?php echo $mainmenu; ?>

        <?php if ($menucollapses) { ?>
            </div>
        <?php } ?>

        </div>
    </div>

    <!-- <div id="topspacer"></div> -->
    <?php if (isset($submenu) && ($submenu)) { ?>
        <div id="submenu">
            <div class="container">
                <?php echo $submenu; ?>
            </div>
        </div>
        <br>
    <?php } ?>

    <div id="sidebar" style="left: 0" class="sidenav bg-dark text-light">
        <div class="sidebar-content d-flex flex-column flex-fill">
            <?php echo $sidebar; ?>
        </div>
    </div>
    <a title="<?php echo _("Toggle Sidebar") ?>" data-toggle="slide-collapse" data-target="#sidebar" href="#" class="sidebar-switch h-100 p-0 d-flex justify-content-center flex-column"></a>

    <span class="menu-overlay" data-toggle="slide-collapse" data-target="#sidebar"></span>

    <?php if ($fullwidth && $route->controller=="dashboard") { ?>
        <div id="dashboard-container">
            <?php echo $content; ?>
        </div>
    <?php } else if ($fullwidth) { ?>
        <div class="container-fluid">
            <div class="row-fluid">
                <div class="span12">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="container">
            <?php echo $content; ?>
        </div>
    <?php } ?>

    </div><!-- eof #wrap -->

    <div id="footer">
        <?php echo _('Powered by '); ?><a href="http://openenergymonitor.org">OpenEnergyMonitor.org</a>
        <span> | <a href="https://github.com/emoncms/emoncms/releases"><?php echo $emoncms_version; ?></a></span>
    </div>

    <script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap/js/bootstrap.js"></script>
<?php if (isset($ui_version_2) && $ui_version_2) { ?>
    <script type="text/javascript" src="<?php echo $path; ?>Lib/hammer.min.js"></script>
    <script>
        // only use hammerjs on the relevent pages
        // CSV list of pages in the navigation
        var pages = ['feed/list','input/view'],
        // strip off the domain/ip and just get the path
        currentPage = (""+window.location).replace(path,''),
        // find where in the list the current page is
        currentIndex = pages.indexOf(currentPage)

        if (currentIndex > -1) {
            // uses hammerjs to detect mobile gestures. navigates between input and feed view
            
            // allow text on page to be highlighted. 
            delete Hammer.defaults.cssProps.userSelect

            // SETUP VARIABLES:
            var container = document.getElementById('wrap'),
                // get the path as reported by server
                path = "<?php echo $path; ?>",
                // create a new instance of the hammerjs api
                mc = new Hammer.Manager(container, {
                    inputClass: Hammer.TouchInput
                }),
                // make swipes require more velocity
                swipe = new Hammer.Swipe({ velocity: 1.1, direction: Hammer.DIRECTION_HORIZONTAL }) // default velocity 0.3
            
            // enable the altered swipe gesture
            mc.add([swipe]);

            // CREATE EVENT LIST:
            // add a callback function on the swipe gestures
            mc.on("swipeleft swiperight", function(event) {              
                    // increase or decrease the currentIndex
                    index = event.type=='swipeleft' ? currentIndex+1 : currentIndex-1;
                    // wrap back to start if beyond end
                    index = index > pages.length-1 ? 0 : index
                    // wrap forward to end if beyond start
                    index = index < 0 ? pages.length-1 : index
                    // get the page to load
                    url = path+pages[index]
                    // load the page
                    window.location.href = url
            });
        }
    </script>
<?php } ?>

<svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <defs>
        <symbol id="icon-dashboard" viewBox="0 0 32 32">
            <!-- <title>dashboard</title> -->
            <path d="M17.313 4h10.688v8h-10.688v-8zM17.313 28v-13.313h10.688v13.313h-10.688zM4 28v-8h10.688v8h-10.688zM4 17.313v-13.313h10.688v13.313h-10.688z"></path>
        </symbol>
        <symbol id="icon-format_list_bulleted" viewBox="0 0 32 32">
            <!-- <title>format_list_bulleted</title> -->
            <path d="M9.313 6.688h18.688v2.625h-18.688v-2.625zM9.313 17.313v-2.625h18.688v2.625h-18.688zM9.313 25.313v-2.625h18.688v2.625h-18.688zM5.313 22c1.125 0 2 0.938 2 2s-0.938 2-2 2-2-0.938-2-2 0.875-2 2-2zM5.313 6c1.125 0 2 0.875 2 2s-0.875 2-2 2-2-0.875-2-2 0.875-2 2-2zM5.313 14c1.125 0 2 0.875 2 2s-0.875 2-2 2-2-0.875-2-2 0.875-2 2-2z"></path>
        </symbol>
        <symbol id="icon-home" viewBox="0 0 32 32">
            <!-- <title>home</title> -->
            <path d="M13.313 26.688h-6.625v-10.688h-4l13.313-12 13.313 12h-4v10.688h-6.625v-8h-5.375v8z"></path>
        </symbol>
        <symbol id="icon-input" viewBox="0 0 32 32">
            <!-- <title>input</title> -->
            <path d="M14.688 21.313v-4h-13.375v-2.625h13.375v-4l5.313 5.313zM28 4c1.438 0 2.688 1.188 2.688 2.688v18.688c0 1.438-1.25 2.625-2.688 2.625h-24c-1.438 0-2.688-1.188-2.688-2.625v-5.375h2.688v5.375h24v-18.75h-24v5.375h-2.688v-5.313c0-1.438 1.25-2.688 2.688-2.688h24z"></path>
        </symbol>
        <symbol id="icon-show_chart" viewBox="0 0 32 32">
            <!-- <title>show_chart</title> -->
            <path d="M4.688 24.625l-2-2 10-10 5.313 5.375 9.438-10.625 1.875 1.875-11.313 12.75-5.313-5.375z"></path>
        </symbol>
        <symbol id="icon-bullhorn" viewBox="0 0 32 32">
            <!-- <title>bullhorn</title> -->
            <path d="M32 13.414c0-6.279-1.837-11.373-4.109-11.413 0.009-0 0.018-0.001 0.027-0.001h-2.592c0 0-6.088 4.573-14.851 6.367-0.268 1.415-0.438 3.102-0.438 5.047s0.171 3.631 0.438 5.047c8.763 1.794 14.851 6.367 14.851 6.367h2.592c-0.009 0-0.018-0.001-0.027-0.001 2.272-0.040 4.109-5.134 4.109-11.413zM27.026 23.102c-0.293 0-0.61-0.304-0.773-0.486-0.395-0.439-0.775-1.124-1.1-1.979-0.727-1.913-1.127-4.478-1.127-7.223s0.4-5.309 1.127-7.223c0.325-0.855 0.705-1.54 1.1-1.979 0.163-0.182 0.48-0.486 0.773-0.486s0.61 0.304 0.773 0.486c0.395 0.439 0.775 1.124 1.1 1.979 0.727 1.913 1.127 4.479 1.127 7.223s-0.4 5.309-1.127 7.223c-0.325 0.855-0.705 1.54-1.1 1.979-0.163 0.181-0.48 0.486-0.773 0.486zM7.869 13.414c0-1.623 0.119-3.201 0.345-4.659-1.48 0.205-2.779 0.323-4.386 0.323-2.096 0-2.096 0-2.096 0l-1.733 2.959v2.755l1.733 2.959c0 0 0 0 2.096 0 1.606 0 2.905 0.118 4.386 0.323-0.226-1.458-0.345-3.036-0.345-4.659zM11.505 20.068l-4-0.766 2.558 10.048c0.132 0.52 0.648 0.782 1.146 0.583l3.705-1.483c0.498-0.199 0.698-0.749 0.444-1.221l-3.853-7.161zM27.026 17.148c-0.113 0-0.235-0.117-0.298-0.187-0.152-0.169-0.299-0.433-0.424-0.763-0.28-0.738-0.434-1.726-0.434-2.784s0.154-2.046 0.434-2.784c0.125-0.33 0.272-0.593 0.424-0.763 0.063-0.070 0.185-0.187 0.298-0.187s0.235 0.117 0.298 0.187c0.152 0.169 0.299 0.433 0.424 0.763 0.28 0.737 0.434 1.726 0.434 2.784s-0.154 2.046-0.434 2.784c-0.125 0.33-0.272 0.593-0.424 0.763-0.063 0.070-0.185 0.187-0.298 0.187z"></path>
        </symbol>
        <symbol id="icon-user-check" viewBox="0 0 32 32">
            <!-- <title>user-check</title> -->
            <path d="M30 19l-9 9-3-3-2 2 5 5 11-11z"></path>
            <path d="M14 24h10v-3.598c-2.101-1.225-4.885-2.066-8-2.321v-1.649c2.203-1.242 4-4.337 4-7.432 0-4.971 0-9-6-9s-6 4.029-6 9c0 3.096 1.797 6.191 4 7.432v1.649c-6.784 0.555-12 3.888-12 7.918h14v-2z"></path>
        </symbol>
        <symbol id="icon-wrench" viewBox="0 0 32 32">
            <!-- <title>wrench</title> -->
            <path d="M31.342 25.559l-14.392-12.336c0.67-1.259 1.051-2.696 1.051-4.222 0-4.971-4.029-9-9-9-0.909 0-1.787 0.135-2.614 0.386l5.2 5.2c0.778 0.778 0.778 2.051 0 2.828l-3.172 3.172c-0.778 0.778-2.051 0.778-2.828 0l-5.2-5.2c-0.251 0.827-0.386 1.705-0.386 2.614 0 4.971 4.029 9 9 9 1.526 0 2.963-0.38 4.222-1.051l12.336 14.392c0.716 0.835 1.938 0.882 2.716 0.104l3.172-3.172c0.778-0.778 0.731-2-0.104-2.716z"></path>
        </symbol>
        <symbol id="icon-leaf" viewBox="0 0 32 32">
            <!-- <title>leaf</title> -->
            <path d="M31.604 4.203c-3.461-2.623-8.787-4.189-14.247-4.189-6.754 0-12.257 2.358-15.099 6.469-1.335 1.931-2.073 4.217-2.194 6.796-0.108 2.296 0.278 4.835 1.146 7.567 2.965-8.887 11.244-15.847 20.79-15.847 0 0-8.932 2.351-14.548 9.631-0.003 0.004-0.078 0.097-0.207 0.272-1.128 1.509-2.111 3.224-2.846 5.166-1.246 2.963-2.4 7.030-2.4 11.931h4c0 0-0.607-3.819 0.449-8.212 1.747 0.236 3.308 0.353 4.714 0.353 3.677 0 6.293-0.796 8.231-2.504 1.736-1.531 2.694-3.587 3.707-5.764 1.548-3.325 3.302-7.094 8.395-10.005 0.292-0.167 0.48-0.468 0.502-0.804s-0.126-0.659-0.394-0.862z"></path>
        </symbol>
        <symbol id="icon-phonelink_setup" viewBox="0 0 32 32">
            <!-- <title>phonelink_setup</title> -->
            <path d="M25.313 1.313c1.438 0 2.688 1.25 2.688 2.688v24c0 1.438-1.25 2.688-2.688 2.688h-13.313c-1.438 0-2.688-1.25-2.688-2.688v-4h2.688v2.688h13.313v-21.375h-13.313v2.688h-2.688v-4c0-1.438 1.25-2.688 2.688-2.688h13.313zM10.688 18.688c1.438 0 2.625-1.25 2.625-2.688s-1.188-2.688-2.625-2.688-2.688 1.25-2.688 2.688 1.25 2.688 2.688 2.688zM15.75 16.688l1.438 1.188c0.125 0.125 0.25 0.25 0.125 0.375l-1.313 2.313c-0.125 0.125-0.25 0.125-0.375 0.125l-1.75-0.688c-0.375 0.25-0.813 0.563-1.188 0.688l-0.313 1.688c-0.125 0.125-0.25 0.313-0.375 0.313h-2.688c-0.125 0-0.375-0.188-0.25-0.313l-0.25-1.688c-0.375-0.125-0.813-0.438-1.188-0.688l-1.875 0.563c-0.125 0.125-0.313-0.063-0.438-0.188l-1.313-2.25c0-0.125 0-0.25 0.125-0.5l1.5-1.063v-1.375l-1.5-1.063c-0.125-0.125-0.25-0.25-0.125-0.375l1.313-2.313c0.125-0.125 0.313-0.125 0.438-0.125l1.688 0.688c0.375-0.25 0.875-0.563 1.25-0.688l0.25-1.688c0.125-0.125 0.25-0.313 0.375-0.313h2.688c0.25 0 0.375 0.188 0.375 0.313l0.313 1.688c0.375 0.125 0.813 0.438 1.188 0.688l1.75-0.563c0.125-0.125 0.25 0.063 0.375 0.188l1.313 2.25c0 0.125 0 0.25-0.125 0.375l-1.438 1.063v1.375z"></path>
        </symbol>
        <symbol id="icon-plus" viewBox="0 0 32 32">
            <!-- <title>plus</title> -->
            <path d="M31 12h-11v-11c0-0.552-0.448-1-1-1h-6c-0.552 0-1 0.448-1 1v11h-11c-0.552 0-1 0.448-1 1v6c0 0.552 0.448 1 1 1h11v11c0 0.552 0.448 1 1 1h6c0.552 0 1-0.448 1-1v-11h11c0.552 0 1-0.448 1-1v-6c0-0.552-0.448-1-1-1z"></path>
        </symbol>
        <symbol id="icon-user" viewBox="0 0 32 32">
            <!-- <title>person</title> -->
            <path d="M16 18.688c3.563 0 10.688 1.75 10.688 5.313v2.688h-21.375v-2.688c0-3.563 7.125-5.313 10.688-5.313zM16 16c-2.938 0-5.313-2.375-5.313-5.313s2.375-5.375 5.313-5.375 5.313 2.438 5.313 5.375-2.375 5.313-5.313 5.313z"></path>
        </symbol>
        <symbol id="icon-device" viewBox="0 0 32 32">
            <!-- <title>device</title> -->
            <path d="M 18.060541,2.0461144 1.9645265,12.44571 2.0034027,13.48277 16.817103,19.713445 17.248421,19.665439 32.215116,7.6225947 32.23142,6.8258092 31.625754,6.2292635 19.164948,2.0479158 c -0.127529,-1.775e-4 -0.657029,-8.874e-4 -1.104414,-0.00266 z m 14.023267,6.7084964 -14.847629,11.9899932 -0.398512,0.02742 -14.7394919,-6.378638 0.023076,5.97283 c 0.074472,0.08969 0.455743,0.529648 0.526693,0.612962 l 13.5117559,6.121658 0.825578,-0.03088 14.729774,-12.28646 0.359026,-0.63118 z M 3.0422101,15.333109 6.3919049,16.701161 v 0.919128 l -3.3496948,-1.368061 -0.00981,-0.277542 2.8137021,1.179093 0.00981,-0.526284 -2.8162961,-1.199846 z m 4.893323,1.880778 0.7561734,0.289213 v 1.258893 l 2.5935155,1.187593 v 0.983632 L 8.6917065,19.719501 v 1.406664 l 2.5935155,1.213105 v 0.04375 L 7.9355331,20.876439 Z m 4.6096209,1.920265 0.756176,0.289222 v 1.259506 l 2.594122,1.187584 v 0.983019 L 13.30133,21.639775 v 1.406664 l 2.594122,1.213709 v 0.04375 L 12.545154,22.79732 Z"></path>
        </symbol>
        <symbol id="icon-menu" viewBox="0 0 32 32">
            <!-- <title>menu</title> -->
            <path d="m 2,6 h 28 v 4.571429 H 2 Z m 0,8 h 28 v 4.571429 H 2 Z m 0,8 h 28 v 4.571429 H 2 Z"></path>
        </symbol>
        <symbol id="icon-apps" viewBox="0 0 32 32">
            <!-- <title>apps</title> -->
            <path d="m 5.1256556,0.32091057 c -1.5502497,0 -2.8314932,1.34817863 -2.8314932,2.89950673 V 29.111936 c 0,1.551328 1.2801643,2.900061 2.8314932,2.900061 H 19.554617 c 1.550249,0 2.832052,-1.348733 2.832052,-2.900061 V 3.2204173 c 0,-1.5513281 -1.280725,-2.89950673 -2.832052,-2.89950673 z m 0,4.31497453 H 19.554617 v 9.1634669 l -0.857976,-0.857421 -6.144658,6.917339 -3.4592268,-3.499366 -3.9671006,3.9671 z M 19.554617,14.571476 V 27.695353 H 5.1256556 v -4.76431 l 3.9671006,-3.967102 3.4592268,3.499366 z"></path>
        </symbol>
        <symbol id="icon-tasks" viewBox="0 0 32 32">
            <!-- <title>tasks</title> -->
            <path d="M18.286 25.143h11.429v-2.286h-11.429v2.286zM11.429 16h18.286v-2.286h-18.286v2.286zM22.857 6.857h6.857v-2.286h-6.857v2.286zM32 21.714v4.571c0 0.625-0.518 1.143-1.143 1.143h-29.714c-0.625 0-1.143-0.518-1.143-1.143v-4.571c0-0.625 0.518-1.143 1.143-1.143h29.714c0.625 0 1.143 0.518 1.143 1.143zM32 12.571v4.571c0 0.625-0.518 1.143-1.143 1.143h-29.714c-0.625 0-1.143-0.518-1.143-1.143v-4.571c0-0.625 0.518-1.143 1.143-1.143h29.714c0.625 0 1.143 0.518 1.143 1.143zM32 3.429v4.571c0 0.625-0.518 1.143-1.143 1.143h-29.714c-0.625 0-1.143-0.518-1.143-1.143v-4.571c0-0.625 0.518-1.143 1.143-1.143h29.714c0.625 0 1.143 0.518 1.143 1.143z"></path>
        </symbol>
        <symbol id="icon-logout" viewBox="0 0 32 32">
            <!-- <title>logout</title> -->
            <path d="M23.75 6.875c2.563 2.188 4.25 5.5 4.25 9.125 0 6.625-5.375 12-12 12s-12-5.375-12-12c0-3.625 1.688-6.938 4.25-9.125l1.875 1.875c-2.063 1.688-3.438 4.313-3.438 7.25 0 5.188 4.125 9.313 9.313 9.313s9.313-4.125 9.313-9.313c0-2.938-1.313-5.5-3.438-7.188zM17.313 4v13.313h-2.625v-13.313h2.625z"></path>
        </symbol>
        <symbol id="icon-expand" viewBox="0 0 32 32">
            <!-- <title>expand</title> -->
            <path d="M32 0v13l-5-5-6 6-3-3 6-6-5-5zM14 21l-6 6 5 5h-13v-13l5 5 6-6z"></path>
        </symbol>
        <symbol id="icon-contract" viewBox="0 0 32 32">
            <!-- <title>contract</title> -->
            <path d="M14 18v13l-5-5-6 6-3-3 6-6-5-5zM32 3l-6 6 5 5h-13v-13l5 5 6-6z"></path>
        </symbol>
    </defs>
</svg>

<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/sidebar.js"></script>
</body>
</html>
