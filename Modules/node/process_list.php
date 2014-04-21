<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

global $path, $session;

$nodeid = $_GET['node'];
$variableid = $_GET['variable'];

?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/node/node.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/node/processlist.js"></script>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/process_info.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<br>

<div style="font-size:30px; padding-bottom:20px; padding-top:18px"><b>Node <span id="nodeid"></span>:<span id="variableid"></span></b> config</div>
<p><?php echo _('Input processes are executed sequentially with the result being passed back for further processing by the next processor in the input processing list.'); ?></p>

<div id="processlist-ui">
    <table class="table">

        <tr>
            <th style='width:5%;'></th>
            <th style='width:5%;'><?php echo _('Order'); ?></th>
            <th><?php echo _('Process'); ?></th>
            <th><?php echo _('Arg'); ?></th>
            <th></th>
            <th><?php echo _('Actions'); ?></th>
        </tr>

        <tbody id="variableprocesslist"></tbody>

    </table>

    <table class="table">
    <tr><th>Add process:</th><tr>
    <tr>
        <td>
            <div class="input-prepend input-append">
                <select id="process-select"></select>

                <span id="type-value">
                    <input type="text" id="value-input" style="width:125px" />
                </span>

                <span id="type-input">
                    <select id="input-select" style="width:140px;"></select>
                </span>

                <span id="type-feed">        
                    <select id="feed-select" style="width:140px;"></select>
                    
                    <input type="text" id="feed-name" style="width:150px;" placeholder="Feed name..." />

                    <span class="add-on feed-engine-label">Feed engine: </span>
                    <select id="feed-engine">

                    <optgroup label="Recommended">
                    <option value=6 selected>Fixed Interval With Averaging (PHPFIWA)</option>
                    <option value=5 >Fixed Interval No Averaging (PHPFINA)</option>
                    <option value=2 >Variable Interval No Averaging (PHPTIMESERIES)</option>
                    </optgroup>

                    <optgroup label="Other">
                    <option value=4 >PHPTIMESTORE (Port of timestore to PHP)</option>  
                    <option value=1 >TIMESTORE (Requires installation of timestore)</option>
                    <option value=3 >GRAPHITE (Requires installation of graphite)</option>
                    <option value=0 >MYSQL (Slow when there is a lot of data)</option>
                    </optgroup>

                    </select>


                    <select id="feed-interval" style="width:130px">
                        <option value="">Select interval</option>
                        <option value=5>5s</option>
                        <option value=10>10s</option>
                        <option value=15>15s</option>
                        <option value=20>20s</option>
                        <option value=30>30s</option>
                        <option value=60>60s</option>
                        <option value=120>2 mins</option>
                        <option value=300>5 mins</option>
                        <option value=600>10 mins</option>
                        <option value=1200>20 mins</option>
                        <option value=1800>30 mins</option>
                        <option value=3600>1 hour</option>
                    </select>
                    
                </span>
                <button id="process-add" class="btn btn-info"><?php echo _('Add'); ?></button>
            </div>
        </td>
    </tr>
    <tr>
      <td id="description"></td>
    </tr>
    </table>
</div>

<hr/>



<script type="text/javascript">

var path = "<?php echo $path; ?>";

processlist_ui.nodeid = <?php echo $nodeid; ?>;
processlist_ui.variableid = <?php echo $variableid; ?>;

processlist_ui.nodes = node.getall();
processlist_ui.feedlist = feed.list_assoc();
processlist_ui.inputlist = input.list_assoc();
processlist_ui.processlist = input.getallprocesses();

processlist_ui.init();

$(document).ready(function() {
  processlist_ui.draw();
  processlist_ui.events();
});

$("#nodeid").html(processlist_ui.nodeid);
if (processlist_ui.nodes[processlist_ui.nodeid].decoder.variables[processlist_ui.variableid].name!=undefined) {
    $("#variableid").html(processlist_ui.nodes[processlist_ui.nodeid].decoder.variables[processlist_ui.variableid].name);
} else {
    $("#variableid").html(processlist_ui.variableid);
}


</script>
