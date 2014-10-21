<?php
    global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/processlist.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/process_info.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<style>
input[type="text"] {
         width: 88%;
}

#table td:nth-of-type(1) { width:5%;}
#table td:nth-of-type(2) { width:10%;}
#table td:nth-of-type(3) { width:25%;}

#table td:nth-of-type(7) { width:30px; text-align: center; }
#table td:nth-of-type(8) { width:30px; text-align: center; }
#table td:nth-of-type(9) { width:30px; text-align: center; }
</style>

<br>
<div id="apihelphead"><div style="float:right;"><a href="api"><?php echo _('Input API Help'); ?></a></div></div>

<div class="container">
    <div id="localheading"><h2><?php echo _('Inputs'); ?></h2></div>
    
    <div id="processlist-ui" style="padding:20px; background-color:#efefef; display:none">
    
    <div style="font-size:30px; padding-bottom:20px; padding-top:18px"><b><span id="inputname"></span></b> config</div>
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

                    <span id="type-value" style="display:none">
                        <input type="text" id="value-input" style="width:125px" />
                    </span>

                    <span id="type-input" style="display:none">
                        <select id="input-select" style="width:140px;"></select>
                    </span>

                    <span id="type-feed">        
                        <select id="feed-select" style="width:140px;"></select>
                        
                        <input type="text" id="feed-name" style="width:150px;" placeholder="Feed name..." />
                        <input type="hidden" id="feed-tag"/>

                        <span class="add-on feed-engine-label">Feed engine: </span>
                        <select id="feed-engine">
                        <option value=5 >Fixed Interval No Averaging (PHPFINA)</option>
                        <option value=2 >Variable Interval No Averaging (PHPTIMESERIES)</option>
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
    <br>
    
    <div id="table"></div>

    <div id="noinputs" class="alert alert-block hide">
            <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
            <p><?php echo _('Inputs is the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>

</div>

<script>

    var path = "<?php echo $path; ?>";
    
    var firstrun = true;
    var assoc_inputs = {};

    // Extend table library field types
    for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];

    table.element = "#table";

    table.fields = {
        //'id':{'type':"fixed"},
        'nodeid':{'title':'<?php echo _("Node:"); ?>','type':"fixed"},
        'name':{'title':'<?php echo _("Key"); ?>','type':"text"},
        'description':{'title':'<?php echo _("Name"); ?>','type':"text"},
        'processList':{'title':'<?php echo _('Process list'); ?>','type':"processlist"},
        'time':{'title':'last updated', 'type':"updated"},
        'value':{'type':"value"},

        // Actions
        'edit-action':{'title':'', 'type':"edit"},
        'delete-action':{'title':'', 'type':"delete"},
        'view-action':{'title':'', 'type':"iconbasic", 'icon':'icon-wrench'}

    }

    table.groupprefix = "Node ";
    table.groupby = 'nodeid';

    update();

    function update()
    {   
        $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(data) {
        
            table.data = data;
            table.draw();
            if (table.data.length != 0) {
                $("#noinputs").hide();
                $("#apihelphead").show();
                $("#localheading").show();
            } else {
                $("#noinputs").show();
                $("#localheading").hide();
                $("#apihelphead").hide();
            }
            
            if (firstrun) {
                firstrun = false;
                load_all(); 
            }
        }});
    }

    var updater = setInterval(update, 10000);

    $("#table").bind("onEdit", function(e){
        clearInterval(updater);
    });

    $("#table").bind("onSave", function(e,id,fields_to_update){
        input.set(id,fields_to_update);
        updater = setInterval(update, 10000);
    });

    $("#table").bind("onDelete", function(e,id){
        input.remove(id);
        update();
    });
    
    
//------------------------------------------------------------------------------------------------------------------------------------
// Process list UI js
//------------------------------------------------------------------------------------------------------------------------------------
 
    $("#table").on('click', '.icon-wrench', function() {
        
        var i = table.data[$(this).attr('row')];
        console.log(i);
        processlist_ui.inputid = i.id;
        
        var processlist = [];

        if (i.processList!=null && i.processList!="")
        {
            var tmp = i.processList.split(",");
            for (n in tmp)
            {
                var process = tmp[n].split(":"); 
                processlist.push(process);
            }
        }
        
        processlist_ui.variableprocesslist = processlist;
        processlist_ui.draw();
        
        // SET INPUT NAME
        var inputname = "";
        if (processlist_ui.inputlist[processlist_ui.inputid].description!="") {
            inputname = processlist_ui.inputlist[processlist_ui.inputid].description;
            $("#feed-name").val(inputname);
        } else {
            inputname = processlist_ui.inputlist[processlist_ui.inputid].name;
            $("#feed-name").val("node:"+processlist_ui.inputlist[processlist_ui.inputid].nodeid+":"+inputname);
        }
        
        $("#inputname").html("Node"+processlist_ui.inputlist[processlist_ui.inputid].nodeid+": "+inputname);
        
        $("#feed-tag").val("Node:"+processlist_ui.inputlist[processlist_ui.inputid].nodeid);
        
        $("#processlist-ui").show();
        window.scrollTo(0,0);
        
    });

function load_all()
{
    for (z in table.data) assoc_inputs[table.data[z].id] = table.data[z];
    console.log(assoc_inputs);
    processlist_ui.inputlist = assoc_inputs;
    
    // Inputlist
    var out = "";
    for (i in processlist_ui.inputlist) {
      var input = processlist_ui.inputlist[i];
      out += "<option value="+input.id+">Node "+input.nodeid+":"+input.name+" "+input.description+"</option>";
    }
    $("#input-select").html(out);
    
    $.ajax({ url: path+"feed/list.json", dataType: 'json', async: true, success: function(result) {
        
        var feeds = {};
        for (z in result) feeds[result[z].id] = result[z];
        
        processlist_ui.feedlist = feeds;
        // Feedlist
        var out = "<option value=-1>CREATE NEW:</option>";
        for (i in processlist_ui.feedlist) {
          out += "<option value="+processlist_ui.feedlist[i].id+">"+processlist_ui.feedlist[i].name+"</option>";
        }
        $("#feed-select").html(out);
    }});
    
    $.ajax({ url: path+"input/getallprocesses.json", async: true, dataType: 'json', success: function(result){
        processlist_ui.processlist = result;
        var processgroups = [];
        var i = 0;
        for (z in processlist_ui.processlist)
        {
            i++;
            var group = processlist_ui.processlist[z][5];
            if (group!="Deleted") {
                if (!processgroups[group]) processgroups[group] = []
                processlist_ui.processlist[z]['id'] = z;
                processgroups[group].push(processlist_ui.processlist[z]);
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
        
        $("#description").html(process_info[1]);
        processlist_ui.showfeedoptions(1);
    }});
   
    processlist_ui.events();
}
</script>
