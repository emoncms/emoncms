<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

global $path, $session, $default_engine;

?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<br>
<div style="float:right;"><a href="../api"><?php echo _("Input API Help") ?></a></div>

<h2><?php echo _('Input configuration:   '); ?><span id="inputname"></span> (<?php echo $inputid; ?>)</h2>
<p><?php echo _('Input processes are executed sequentially with the result being passed back for further processing by the next processor in the input processing list.'); ?></p>

<div class="alert alert-info"><b>Feed intervals: </b>When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</div>

<table class="table">

    <tr>
        <th style='width:10%;'></th>
        <th style='width:5%;'><?php echo _('Order'); ?></th>
        <th style='width:35%;'><?php echo _('Process'); ?></th>
        <th style='width:40%;'><?php echo _('Arg'); ?></th>
        <th><?php echo _('Actions'); ?></th>
    </tr>

    <tbody id="inputprocesslist"></tbody>

    <tr>
        <td><?php echo _("New"); ?></td>
        <td></td>
        <td>
            <input type="hidden" name="inputid" value="<?php echo $inputid; ?>">
            <!-- Populate list of input processes availabble -->
            <select class="processSelect" name="type" id="type">

            </select>
        </td>
        <!-- cointainer for new process arguments -->
        <td><span id="newProcessArgField"></span><span id="options"></span></td>
        <td><button id="submit_add" class="btn btn-primary"/><?php echo _('Add'); ?></button></td>
    </tr>

</table>

<hr/>

<div class="input-prepend" id="engine-selector">
  <span class="add-on">Feed engine: </span>
  <select id="engine">
    <option value=0 >MYSQL</option>
    <option value=1 selected>TIMESTORE</option>
    <option value=2 >PHPTIMESERIES</option>
    <option value=3 >GRAPHITE</option>
    <option value=4 >PHPTIMESTORE</option>
  </select>
</div>

<script type="text/javascript">

var default_engine = <?php echo $default_engine; ?>;
var path = "<?php echo $path; ?>";
var inputid = <?php echo $inputid; ?>;
var feedlist = <?php echo json_encode($feedlist); ?>;
var inputlist = <?php echo json_encode($inputlist); ?>;

// DRAW PROCESS SELECTOR

var processlist = <?php echo json_encode($processlist); ?>;
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
$(".processSelect").html(out);

// SET INPUT NAME

for (i in inputlist)
{
    if (inputlist[i].id == inputid)
    {
        $("#inputname").html(inputlist[i].nodeid+":"+inputlist[i].name+" "+inputlist[i].description);
        break;
    }
}

function update_list()
{
    var inputprocesslist = input.processlist(inputid);

    var i = 0;
    var out="";

    for (z in inputprocesslist)
    {
        i++; // process id
        out += '<tr>';

        // Move process up or down
        out += '<td>';
        if (i > 1) {
            out += '<a class="move-process" href="#" title="<?php echo _("Move up"); ?>" processid='+i+' moveby=-1 ><i class="icon-arrow-up"></i></a>';
        }

        if (i < inputprocesslist.length) {
            out += '<a class="move-process" href="#" title="<?php echo _("Move up"); ?>" processid='+i+' moveby=1 ><i class="icon-arrow-down"></i></a>';
        }
        out += '</td>';

        // Process name and argument
        out += "<td>"+i+"</td><td>"+inputprocesslist[z][0]+"</td><td>"+inputprocesslist[z][1]+"</td>";

        // Delete process button (icon)
        out += '<td><a href="#" class="delete-process" title="<?php echo _('Delete'); ?>" processid='+i+'><i class="icon-trash"></i></a></td>';

        out += '</tr>';
    }

    if (inputprocesslist.length==0) {
        out += "<tr class='alert'><td></td><td></td><td><b><?php echo _('You have no processes defined'); ?></b></td><td></td><td></td></tr>";
    }

    $('#inputprocesslist').html(out);
}

function generate_process_arg_box()
{
    var process_id = $('select[name="type"]').val();
    if (process_id==1) $("#engine-selector").show(); else $("#engine-selector").hide();
    
    var process = processlist[process_id];
    if (process[4]==2) $("#engine").val(0);
    if (process[4]==3) $("#engine").val(0);
    
    var out = "";
    if (process[1]==0) // Process type is multiply input by value or apply an offset - the argument is a value
    {
        out += "<input type='text' name='arg' class='processArgBox' id='arg' style='width:100px;'/ >";
    }

    if (process[1]==1) // Process type is multiply, divide by input or add another input - argument type is input
    {
        out +='<select class="processArgBox" name="arg" id="arg" onChange="update_process_arg_box()" style="width:140px;">'
        for (i in inputlist) out += '<option value="'+inputlist[i].id+'">'+inputlist[i].nodeid+":"+inputlist[i].name+'</option>';
        out +='</select>';
    }

    if (process[1]==2) // Argument type is a feed to log to, or output as a kwhd feed and so on.
    {
        out +='<select class="processArgBox" name="arg" id="arg" onChange="update_process_arg_box()" style="width:140px;">'
        out +='<option value="-1"><?php echo _("CREATE NEW:"); ?></option>';
        for (i in feedlist) out += '<option value="'+feedlist[i].id+'">'+feedlist[i].name+'</option>';
        out +='</select>';
    }

    $('#newProcessArgField').html(out);

    update_process_arg_box();
}

// Add or remove newfeedname text box (for new feed name) if Create New feed is selected
function update_process_arg_box()
{
    if ($('.processArgBox').val() == -1) {

        $('#options').html('<input type="text" name="newfeedname" class="processArgBox2" style="width:100px;" id="newfeedname"/ >');

        // Only show interval selector for timestore based feeds: datatype = 1
        var selected_processid = $('select#type').val();
        if (processlist[selected_processid][4] == 1)
        {
            if ($("#engine").val()==1 || $("#engine").val()==4) {
                $('#options').append('<select id="newfeedinterval"><option value="">Select interval</option><option value=5>5s</option><option value=10>10s</option><option value=15>15s</option><option value=20>20s</option><option value=25>25s</option><option value=30>30s</option><option value=60>60s</option><option value=120>2 mins</option><option value=300>5 mins</option><option value=600>10 mins</option><option value=3600>1 hour</option><option value=21600>6 hours</option><option value=86400>24 hours</option></select>');
            }
        }
    }
    else {
        $('#newfeedname').remove();
        $('#newfeedinterval').remove();
    }
}

function process_add() {

    var arg = $('#arg').val();
    var process_id = $('select[name="type"]').val();
    var process = processlist[process_id];

    var feedid = false;
    
    // If create feed    
    if (process[1]==2 && arg==-1) {
    
        var newfeedname = $('#newfeedname').val();
        var engine = $('#engine').val();
        var options = {interval:$('#newfeedinterval').val()};
        var datatype = process[4];
        console.log(datatype);
        
        if (newfeedname == '') {
            alert('ERROR: Please enter a feed name');
            return false;
        }
        
        var result = feed.create(newfeedname,datatype,engine,options);
        feedid = result.feedid;
        
        if (!result.success || feedid<1) {
            alert('ERROR: Feed could not be created');
            return false;
        }
        
        arg = feedid;
    }
    
    var result = input.add_process(inputid,process_id,arg);
    
    if (result.success == false) {
        alert(data.message);
        return false;
    }
    
    update_list();
    
    return true;
}

$("#engine").change(function(){
    update_process_arg_box();
});

$('#submit_add').click(function() {

    // console.log($('select#type').val());
    // console.log($('#arg').val());
    // console.log($('#newfeedname').val());
    // console.log($('#newfeedinterval').val());
    // console.log($('#engine').val());
    
    if (!process_add()) return false;
    generate_process_arg_box();
    return false;
});

$('.processSelect').change(function() {
    generate_process_arg_box();
});

$('.processArgBox').change(function() {
    update_process_arg_box();
});

$('.table').on('click', '.delete-process', function() {
    input.delete_process(inputid,$(this).attr('processid'));
    update_list();
});

$('.table').on('click', '.move-process', function() {
    input.move_process(inputid,$(this).attr('processid'),$(this).attr('moveby'));
    update_list();
});

$(document).ready(function() {
    update_list();
    generate_process_arg_box();
});

</script>
