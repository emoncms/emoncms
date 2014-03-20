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

?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/processlist.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/process_info.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<br>

<div style="font-size:30px; padding-bottom:20px; padding-top:18px"><b><span id="inputname"></span></b> config</div>
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
                <button id="process-add" class="btn btn-info"/><?php echo _('Add'); ?></button>
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

processlist_ui.inputid = <?php echo $inputid; ?>;

console.log(processlist_ui.inputid);

processlist_ui.feedlist = feed.list_assoc();
processlist_ui.inputlist = input.list_assoc();
processlist_ui.processlist = input.getallprocesses();
processlist_ui.variableprocesslist = input.processlist(processlist_ui.inputid);
processlist_ui.init();

$(document).ready(function() {
  processlist_ui.draw();
  processlist_ui.events();
});

// SET INPUT NAME
var inputname = "";
if (processlist_ui.inputlist[processlist_ui.inputid].description!="") inputname = processlist_ui.inputlist[processlist_ui.inputid].description; else inputname = processlist_ui.inputlist[processlist_ui.inputid].name;
$("#inputname").html("Node "+processlist_ui.inputlist[processlist_ui.inputid].nodeid+": "+inputname);


</script>
