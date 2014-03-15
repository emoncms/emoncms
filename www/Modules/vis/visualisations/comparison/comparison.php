<html>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
 <!--
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  -->
<?php

    global $path, $embed;

    $power = get('power');
    $kwhd = get('kwhd');
    $apikey = get('apikey');
    $currency = get('currency')?get('currency'):'&euro;';
    $pricekwh = get('pricekwh')?get('pricekwh'):0.12;
?>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/lib/d3.js/d3.v2.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/comparison/comparison.js"></script>

    <style type='text/css'>
        .chart {
            margin-left: 42px;
            font: 10px sans-serif;
            shape-rendering: crispEdges;
        }

        .chart div {
            background-color: #0096ff;
            text-align: right;
            padding: 3px;
            margin: 1px;
            color: white;
        }

        .chart rect {
            stroke: #0095ff;
            fill: #0095ff;
            stroke-width: 2;
            fill-opacity: 0.4;
            stroke-linejoin : round;
        }

        .chart text.bar {
            fill: white;
        }

        body {
            font: 10px sans-serif;
        }

        .rule line {
            stroke: #eee;
            shape-rendering: crispEdges;
        }

        .rule line.axis {
            stroke: #000;
        }

        .slider line {
            stroke: white;
            stroke-width: 10;
            cursor: pointer;
        }

        .comparison {
            border-bottom: 1px solid #DFDFDF;
            border-top: 1px solid #DFDFDF;
            text-align: center;
        }

    </style>

<?php if (!$embed) { ?>
<h2>kWh/d Comparison</h2>
<?php } ?>

    <div id="widget"></div>

    <script type="text/javascript">
        var kwhd = <?php echo $kwhd; ?>;
        var power = <?php echo $power; ?>;
        var path = "<?php echo $path; ?>";
        var apikey = "<?php echo $apikey; ?>";
        var price = <?php echo $pricekwh ?>;
        var currency = "<?php echo $currency ?>";

        var today = new Date();
        var month = today.getMonth();
        var year = today.getFullYear();

        var kwhd1 = 0,
            kwhd2 = 0;

        d3.select("#widget")
            .append("div")
            .attr("id", "container");

        var container = d3.select("#container")
                        .append("div")
                        .attr("id", "charts")
                        .attr("style", "float : left; width : 615px; border-right: 1px solid #DFDFDF");

        d3.select("#container")
            .append("div")
            .attr("id", "placeholder")
            .attr("style", "float : left; height : 526px; width : 300px;");

        var container1 = container
            .append("div")
            .attr("id", "#container1")
            .attr("style", "width : 600px; height : 264px;");

        var container2 = container
            .append("div")
            .attr("id", "#container2")
            .attr("style", "width : 600px; height : 264px;");

        d3.select("#placeholder")
            .append("div")
            .attr("id", "day1")
            .attr("style", "width : 100%; height : 176px;");

        d3.select("#placeholder")
            .append("div")
            .attr("id", "comparisonbox")
            .attr("style", "width : 100%; height : 176px;");

        d3.select("#placeholder")
            .append("div")
            .attr("id", "day2")
            .attr("style", "width : 100%; height : 176px;");

        plotChart(container1, 1, month-1);
        plotChart(container2, 2, month);


    </script>
</html>
