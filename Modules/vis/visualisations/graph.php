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
<?php
    load_language_files(dirname(__DIR__).'/locale',"vis_messages");
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.merged.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path;?>Modules/vis/visualisations/graph.js"></script>

<h3><?php echo dgettext('vis_messages','Data viewer'); ?></h3>

<div id="error" style="display:none"></div>

<div style="padding-bottom:5px;">
    <button class='btn graph_time' type='button' time='1'><?php echo dgettext('vis_messages','D'); ?></button>
    <button class='btn graph_time' type='button' time='7'><?php echo dgettext('vis_messages','W'); ?></button>
    <button class='btn graph_time' type='button' time='30'><?php echo dgettext('vis_messages','M'); ?></button>
    <button class='btn graph_time' type='button' time='365'><?php echo dgettext('vis_messages','Y'); ?></button>
    <button id='graph_zoomin' class='btn'>+</button>
    <button id='graph_zoomout' class='btn'>-</button>
    <button id='graph_left' class='btn'><</button>
    <button id='graph_right' class='btn'>></button>
</div>

<div id="placeholder_bound" style="width:100%; height:400px;">
    <div id="placeholder"></div>
</div>

<div id="info" style="padding:20px;background-color:rgb(245,245,245); font-style:italic; display:none">

    <p><b><?php echo dgettext('vis_messages','Stats'); ?></b></p>
    
    <table class="table">
        <tr><th></th><th><?php echo dgettext('vis_messages','Mean'); ?></th><th><?php echo dgettext('vis_messages','Min'); ?></th><th><?php echo dgettext('vis_messages','Max'); ?></th><th><?php echo dgettext('vis_messages','Diff'); ?></th><th><?php echo dgettext('vis_messages','Std Dev'); ?></th><th><?php echo dgettext('vis_messages','npoints'); ?></th></tr>
        <tr>
            <td></td>
            <td id="stats-mean"></td>
            <td id="stats-min"></td>
            <td id="stats-max"></td>
            <td id="stats-diff"></td>
            <td id="stats-stdev"></td>
            <td id="stats-npoints"></td>
        </tr>
    </table>
    
    <p><b><?php echo dgettext('vis_messages','Advanced'); ?></b></p>
    <div><?php echo dgettext('vis_messages','Apply smoothing (number of points):'); ?>
      <select id="smoothing" style="width:50px">
          <option>0</option>
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
      </select>
    </div>
    <br>

    <p><b><?php echo dgettext('vis_messages','API Request'); ?></b></p>
    
    <div class="input-prepend input-append">
        <span class="add-on" style="width:75px"><?php echo dgettext('vis_messages','Start'); ?></span>
        <input id="request-start" type="text" style="width:80px" />

        <span class="add-on" style="width:75px"><?php echo dgettext('vis_messages','End'); ?></span>
        <input id="request-end" type="text" style="width:80px" />

        <span class="add-on" style="width:75px"><?php echo dgettext('vis_messages','Interval'); ?></span>
        <input id="request-interval" type="text" style="width:60px" />

        <span class="add-on"><?php echo dgettext('vis_messages','Skip missing'); ?> <input id="request-skipmissing" type="checkbox" /></span>
        <span class="add-on"><?php echo dgettext('vis_messages','Limit interval'); ?> <input id="request-limitinterval" type="checkbox" /></span>
    
        <button id="resend" class="btn"><?php echo dgettext('vis_messages','Resend'); ?></button>
    </div>
    
    <div>GET <a id="request-url"></a></div>
    <br>
    
    <button class="btn" id="showcsv" ><?php echo dgettext('vis_messages','Show CSV Output'); ?></button>
    
    <textarea id="csv" style="width:95%; height:500px; display:none; margin-top:10px"></textarea>

</div>
<script src="<?php echo $path; ?>Lib/user_locale.js"></script>
<script src="<?php echo $path; ?>Lib/misc/gettext.js"></script>

<script>
    var _lang = <?php
        $lang['Show CSV Output'] = _('Show CSV Output');
        $lang['Hide CSV Output'] = _('Hide CSV Output');
        echo json_encode($lang) . ';';
        echo "\n";
    ?>
</script>
<script>
    app_graph.feedname = parseInt("<?php echo $feedid; ?>");
    app_graph.init();
    app_graph.show();
</script>

