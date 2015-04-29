<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    */

    global $path, $embed;
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/graph.js"></script>

<h3>Data viewer</h2>

<div id="error" style="display:none"></div>

<div style="padding-bottom:5px;">
    <button class='btn graph_time' type='button' time='1'>D</button>
    <button class='btn graph_time' type='button' time='7'>W</button>
    <button class='btn graph_time' type='button' time='30'>M</button>
    <button class='btn graph_time' type='button' time='365'>Y</button>
    <button id='graph_zoomin' class='btn' >+</button>
    <button id='graph_zoomout' class='btn' >-</button>
    <button id='graph_left' class='btn' ><</button>
    <button id='graph_right' class='btn' >></button>
</div>

<div id="placeholder_bound" style="width:100%; height:400px;">
    <div id="placeholder"></div>
</div>

<div id="info" style="padding:20px;background-color:rgb(245,245,245); font-style:italic; display:none">

    <p><b>Stats</b></p>
    
    <table class="table">
        <tr><th></th><th>Mean</th><th>Min</th><th>Max</th><th>Std Dev</th><th>npoints</th></tr>
        <tr>
            <td></td>
            <td id="stats-mean"></td>
            <td id="stats-min"></td>
            <td id="stats-max"></td>
            <td id="stats-stdev"></td>
            <td id="stats-npoints"></td>
        </tr>
    </table>

    <p><b>API Request</b></p>
    
    <div class="input-prepend input-append">
        <span class="add-on" style="width:75px">Start</span>
        <input id="request-start" type="text" style="width:80px" />

        <span class="add-on" style="width:75px">End</span>
        <input id="request-end" type="text" style="width:80px" />

        <span class="add-on" style="width:75px">Interval</span>
        <input id="request-interval" type="text" style="width:60px" />

        <span class="add-on">Skip missing <input id="request-skipmissing" type="checkbox" /></span>
        <span class="add-on">Limit interval <input id="request-limitinterval" type="checkbox" /></span>
    
        <button id="resend" class="btn">Resend</button>
    </div>
    
    <div>GET <a id="request-url"></a></div>
    <br>
    
    <button class="btn" id="showcsv" >Show CSV Output</button>
    
    <textarea id="csv" style="width:95%; height:500px; display:none; margin-top:10px"></textarea>

</div>
<script>
    app_graph.feedname = parseInt("<?php echo $feedid; ?>");
    var path = "<?php echo $path; ?>";
    app_graph.init();
    app_graph.show();
</script>

