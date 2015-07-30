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
  global $ltime,$path,$fullwidth,$emoncms_version;
?>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Emoncms - <?php echo $route->controller.' '.$route->action.' '.$route->subaction; ?></title>
        <link rel="shortcut icon" href="<?php echo $path; ?>Theme/favicon.png" />
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <link rel="apple-touch-startup-image" href="<?php echo $path; ?>Theme/ios_load.png">
        <link rel="apple-touch-icon" href="<?php echo $path; ?>Theme/logo_normal.png">
        <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
        <link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
        <link href="<?php echo $path; ?>Theme/emon.css" rel="stylesheet">
        <script type="text/javascript" src="<?php echo $path; ?>Lib/jquery-1.11.3.min.js"></script>
    </head>
    <body>
        <div id="wrap">
        <div class="navbar navbar-inverse navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                    <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                        <img src="<?php echo $path; ?>Theme/favicon.png" style="width:28px;"/>
                    </button>
                    <div class="nav-collapse collapse">
                      <?php if (!isset($runmenu)) $runmenu = '';
                            echo $mainmenu.$runmenu;
                      ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="topspacer"></div>

        <?php if (isset($submenu) && ($submenu)) { ?>
          <div id="submenu">
              <div class="container">
                  <?php echo $submenu; ?>
              </div>
          </div><br>
        <?php } ?>

        <?php
          if (!isset($fullwidth)) $fullwidth = false;
          if ($fullwidth && $route->controller=="dashboard") {
        ?>
        <div>
            <?php echo $content; ?>
        </div>
        <?php } else if ($fullwidth) { ?>
        <div class = "container-fluid"><div class="row-fluid"><div class="span12">
            <?php echo $content; ?>
        </div></div></div>
        <?php } else { ?>
        <div class="container">
            <?php echo $content; ?>
        </div>
        <?php } ?>

        <div style="clear:both; height:60px;"></div>
        </div>

        <div id="footer">
            <?php echo _('Powered by '); ?>
            <a href="http://openenergymonitor.org">openenergymonitor.org</a>
            <span> | <?php echo $emoncms_version; ?></span>
        </div>
        <script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap/js/bootstrap.js"></script>
        <script type="text/javascript">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create','UA-227875-4','auto');ga('require', 'linkid', 'linkid.js');ga(function(e){ga('set','screenName','<?php echo $route->controller.'/'.$route->action.'/'.$route->subaction;?>');ga('set','dimension1',e.get('clientId'));ga('set','dimension2','<?php echo $session['lang'];?>');ga('set','&uid',window.location.host+'|<?php echo $session['userid'].'|'.$session['username'];?>');ga('set','appVersion','<?php echo $emoncms_version;?>');ga('send','pageview');ga('send', 'timing', 'main', 'pageview', '<?php echo microtime(true)-$ltime;?>', 'LoadTime', {'page': '<?php echo $route->controller.'/'.$route->action.'/'.$route->subaction;?>'});});</script>
    </body>
</html>
