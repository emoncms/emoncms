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
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/process_info.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<br>

<div style="font-size:30px; padding-bottom:20px; padding-top:18px"><b>Node <span id="nodeid"></span>:<span id="variableid"></span></b> config</div>
<p><?php echo _('Input processes are executed sequentially with the result being passed back for further processing by the next processor in the input processing list.'); ?></p>

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

                <span class="add-on">Feed engine: </span>
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


<hr/>



<script type="text/javascript">

var path = "<?php echo $path; ?>";

var nodes = node.getall();
  
var nodeid = <?php echo $nodeid; ?>;
var variableid = <?php echo $variableid; ?>;

$("#nodeid").html(nodeid);

if (nodes[nodeid].decoder.variables[variableid].name!=undefined) {
    $("#variableid").html(nodes[nodeid].decoder.variables[variableid].name);
} else {
    $("#variableid").html(variableid);
}

var variableprocesslist = decode_processlist(nodes[nodeid].decoder.variables[variableid].processlist);

var feedlist = feed.list_assoc();
var inputlist = input.list_assoc();

// DRAW PROCESS SELECTOR
var processlist = input.getallprocesses();

var processgroups = [];
var i = 0;
for (z in processlist)
{
    i++;
    var group = processlist[z][5];
    if (group!="Deleted") {
        if (!processgroups[group]) processgroups[group] = []
        processlist[z]['id'] = i;
        processgroups[group].push(processlist[z]);
    }
}

var out = "";
for (z in processgroups)
{
    out += "<optgroup label='"+z+"'>";
    for (p in processgroups[z])
    {
        out += "<option value="+processgroups[z][p]['id']+">"+processgroups[z][p][0]+"</option>";
    }
    out += "</optgroup>";
}
$("#process-select").html(out);

// Inputlist
var out = "";
for (i in inputlist) out += "<option value="+inputlist[i].id+">Node "+inputlist[i].nodeid+":"+inputlist[i].name+" "+inputlist[i].description+"</option>";
$("#input-select").html(out);

// Feedlist
var out = "<option value=-1>CREATE NEW:</option>";
for (i in feedlist) out += "<option value="+feedlist[i].id+">"+feedlist[i].name+"</option>";
$("#feed-select").html(out);


$("#type-value").hide();
$("#type-input").hide();
$("#type-feed").hide();

$("#type-feed").show();
$("#description").html(process_info[1]);

function update_list()
{
    var i = 0;
    var out="";
    
    if (variableprocesslist.length==0) {
        out += "<tr class='alert'><td></td><td></td><td><b><?php echo _('You have no processes defined'); ?></b></td><td></td><td></td><td></td></tr>";
    } else {
    
        for (z in variableprocesslist)
        {
            
            out += '<tr>';

            // Move process up or down
            out += '<td>';
            if (i > 0) {
                out += '<a class="move-process" href="#" title="<?php echo _("Move up"); ?>" processid='+i+' moveby=-1 ><i class="icon-arrow-up"></i></a>';
            }

            if (i < variableprocesslist.length-1) {
                out += '<a class="move-process" href="#" title="<?php echo _("Move up"); ?>" processid='+i+' moveby=1 ><i class="icon-arrow-down"></i></a>';
            }
            out += '</td>';

            // Process name and argument
            var processid = parseInt(variableprocesslist[z][0]);
            var arg = "";
            var lastvalue = "";
            
            if (processlist[processid][1]==0) {
                arg = variableprocesslist[z][1];
            }
            
            if (processlist[processid][1]==1) {
                var inpid = variableprocesslist[z][1];
                arg += "Node "+inputlist[inpid].nodeid+": ";
                if (inputlist[inpid].description!="") arg += inputlist[inpid].description; else arg += inputlist[inpid].name;
                lastvalue = "<span style='color:#888; font-size:12px'>(inputvalue:"+(inputlist[inpid].value*1).toFixed(2)+")</span>";
            }
            
            if (processlist[processid][1]==2) {
                var feedid = variableprocesslist[z][1];
                
                if (feedlist[feedid]!=undefined) {
                    arg += "<a class='label label-info' href='"+path+"vis/auto?feedid="+feedid+"'>";
                    if (feedlist[feedid].tag) arg += feedlist[feedid].tag+": ";
                    arg += feedlist[feedid].name;
                    arg += "</a>";
                    lastvalue = "<span style='color:#888; font-size:12px'>(feedvalue:"+(feedlist[feedid].value*1).toFixed(2)+")</span>";
                } else {
                  // delete feed
                }
            }
            
            out += "<td>"+(i+1)+"</td><td>"+processlist[processid][0]+"</td><td>"+arg+"</td><td>"+lastvalue+"</td>";
     
            // Delete process button (icon)
            out += '<td><a href="#" class="delete-process" title="<?php echo _('Delete'); ?>" processid='+i+'><i class="icon-trash"></i></a></td>';

            out += '</tr>';
            
            i++; // process id
        }
    }
    $('#variableprocesslist').html(out);
}

$("#feed-engine").change(function(){
    var engine = $(this).val();
    $("#feed-interval").hide();
    if (engine==6 || engine==5 || engine==4 || engine==1) $("#feed-interval").show();
});

$('#process-add').click(function() 
{
    var processid = $('#process-select').val();
    var process = processlist[processid];
    var arg = '';
    
    // Type: value (scale, offset)
    if (process[1]==0) arg = $("#value-input").val();
    
    // Type: input (* / + - by input)
    if (process[1]==1) arg = $("#input-select").val();
    
    // Type: feed
    if (process[1]==2)
    {
        var feedid = $("#feed-select").val();
      
        if (feedid==-1) 
        {
            var feedname = $('#feed-name').val();
            var options = {interval:$('#feed-interval').val()};
            var engine = $('#feed-engine').val();
            var datatype = process[4];
            
            if (feedname == '') {
                alert('ERROR: Please enter a feed name');
                return false;
            }
            
            var result = feed.create(feedname,datatype,engine,options);
            feedid = result.feedid;
        
            if (!result.success || feedid<1) {
                alert('ERROR: Feed could not be created, '+result.message);
                return false;
            }
        }
        arg = feedid;


    }
    
    if (arg!="") 
    {
        console.log(processid+" "+arg);
        variableprocesslist.push([processid,arg]);
        nodes[nodeid].decoder.variables[variableid].processlist = encode_processlist(variableprocesslist);
        node.setdecoder(nodeid,nodes[nodeid].decoder);
        
        //if (result.success == false) {
        //    alert(data.message);
        //    return false;
        //}
        update_list();
    }
});

$('#process-select').change(function() {
    var processid = $(this).val();
    
    $("#description").html("");
    $("#type-value").hide();
    $("#type-input").hide();
    $("#type-feed").hide();
    
    if (processlist[processid][1]==0) $("#type-value").show();
    if (processlist[processid][1]==1) $("#type-input").show();
    if (processlist[processid][1]==2) 
    {
        $("#type-feed").show();

        var prc = processlist[processid][2];

        if (prc=='log_to_feed') { 
            $("#feed-engine option").hide(); 
            $("#feed-engine option[value=6]").show();
            // $("#feed-engine option[value=0]").show();
            $("#feed-engine").val(6); 
            $("#feed-interval").show();
        }

        if (prc=='power_to_kwh' || prc=='power_to_kwhd') { 
            $("#feed-engine option").hide(); 
            $("#feed-engine option[value=5]").show();
            // $("#feed-engine option[value=0]").show();
            $("#feed-engine").val(5); 
            $("#feed-interval").hide();
        }
    }
    $("#description").html(process_info[processid]);
});

$('#feed-select').change(function() {
    var feedid = $("#feed-select").val();
    
    if (feedid!=-1) {
        $("#feed-name").hide();
        $("#feed-interval").hide();
    } else {
        $("#feed-name").show();
        $("#feed-interval").show();   
    }
});

$('.table').on('click', '.delete-process', function() {
    variableprocesslist.splice($(this).attr('processid'),1);
    nodes[nodeid].decoder.variables[variableid].processlist = encode_processlist(variableprocesslist);
    node.setdecoder(nodeid,nodes[nodeid].decoder);
    
    update_list();
});

$('.table').on('click', '.move-process', function() {

    var curpos = parseInt($(this).attr('processid'));
    var moveby = parseInt($(this).attr('moveby'));
    var newpos = curpos + moveby;
    if (newpos>=0 && newpos<variableprocesslist.length)
    { 
        variableprocesslist = array_move(variableprocesslist,curpos,newpos);
    }

    nodes[nodeid].decoder.variables[variableid].processlist = encode_processlist(variableprocesslist);
    node.setdecoder(nodeid,nodes[nodeid].decoder);
    update_list();
});

$(document).ready(function() {
    update_list();
});

// Process list functions

function decode_processlist(str)
{
    var processlist = [];
    if (str!="")
    {
        var tmp = str.split(",");
        for (n in tmp)
        {
            var process = tmp[n].split(":"); 
            processlist.push(process);
        }
    }
    return processlist;
}

function encode_processlist(array)
{
    var parts = [];
    for (z in array) parts.push(array[z][0]+":"+array[z][1]);
    return parts.join(",");
}


function array_move(array,old_index, new_index) {
    if (new_index >= array.length) {
        var k = new_index - array.length;
        while ((k--) + 1) {
            array.push(undefined);
        }
    }
    array.splice(new_index, 0, array.splice(old_index, 1)[0]);
    return array; // for testing purposes
};

</script>
