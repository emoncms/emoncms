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
    global $path;
?>

<html>
    <head>
        <meta charset="utf-8" /> 
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>Emoncms embed</title>
        
        <script type="text/javascript" src="<?php echo $path; ?>Lib/jquery-1.9.1.min.js"></script>
        <link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="content">
            <?php print $content; ?>
        </div>
    </body>
</html>