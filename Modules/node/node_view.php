<?php 
  global $path, $feed_settings; 

?>
<style>
body .modal {
    /* new custom width */
    width: 1080px; /* TODO  remove because distorting modal windows on lower resolutions*/
    /* must be half of the width, minus scrollbar on the left (30px) */
    margin-left: -540px; /* TODO remove because distorting modal windows on lower resolutions*/
}
.modal-body {
    max-height: 800px;
}
</style>

<script type="text/javascript" src="<?php echo $path; ?>Modules/node/node.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/node/processlist.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/input/Views/process_info.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

<br>
<div id="apihelphead"><div style="float:right;"><a href="api"><?php echo _('Node API Help'); ?></a></div></div>
<h2><?php echo _('Nodes'); ?></h2>
<p><?php echo _('This is an alternative entry point to inputs designed around providing flexible decoding of RF12b struct based data packets'); ?></p>
<br>

<table class="table">

<tbody id="nodes"></tbody>
</table>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close modal-exit">×</button>
    <br><h3 id="myModalLabel"><b><?php echo _('Node'); ?> <span id="myModal-nodeid"></span>: <span id="myModal-variablename"></span></b> config:</h3>
  </div>

  <div class="modal-body">
  
    <p><?php echo _('Input processes are executed sequentially with the result being passed back for further processing by the next processor in the input processing list.'); ?></p>

    <div id="processlist-ui">
        <table id="process-table" class="table">

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

        <table id="process-table" class="table">
        <tr><th><?php echo _('Add process:'); ?></th><tr>
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

                        <span class="add-on feed-engine-label"><?php echo _('Feed engine'); ?>: </span>
                        <select id="feed-engine">

                        <optgroup label="Recommended">
                        <option value=6 selected><?php echo _('Fixed Interval With Averaging'); ?></option>
                        <option value=5 ><?php echo _('Fixed Interval No Averaging'); ?></option>
                        <option value=2 ><?php echo _('Variable Interval No Averaging'); ?></option>
                        </optgroup>

                        <optgroup label="Other">
                        <option value=4 ><?php echo _('PHPTIMESTORE (Port of timestore to PHP)'); ?></option>  
                        <option value=1 ><?php echo _('TIMESTORE (Requires installation of timestore)'); ?></option>
                        <option value=3 ><?php echo _('GRAPHITE (Requires installation of graphite)'); ?></option>
                        <option value=0 ><?php echo _('MYSQL (Slow when there is a lot of data)'); ?></option>
                        </optgroup>

                        </select>


                        <select id="feed-interval" style="width:130px">
                            <option value=""><?php echo _('Select interval'); ?></option>
                            <option value=5>5 <?php echo _('s'); ?></option>
                            <option value=10>10 <?php echo _('s'); ?></option>
                            <option value=15>15 <?php echo _('s'); ?></option>
                            <option value=20>20 <?php echo _('s'); ?></option>
                            <option value=30>30 <?php echo _('s'); ?></option>
                            <option value=60>60 <?php echo _('s'); ?></option>
                            <option value=120>2 <?php echo _('mins'); ?></option>
                            <option value=300>5 <?php echo _('mins'); ?></option>
                            <option value=600>10 <?php echo _('mins'); ?></option>
                            <option value=1200>20 <?php echo _('mins'); ?></option>
                            <option value=1800>30 <?php echo _('mins'); ?></option>
                            <option value=3600>1 <?php echo _('hour'); ?></option>
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

  
  </div>

  <div class="modal-footer">
    <button class="btn btn-primary modal-exit"><?php echo _('Ok'); ?></button>
  </div>
</div>

<script>

  var path = "<?php echo $path; ?>";
  
  processlist_ui.engines_hidden = <?php echo json_encode($feed_settings['engines_hidden']); ?>;
  
  var nodes = node.getall();
  
  var decoders = {
  
    nodecoder: {
      name: '<?php echo _('No decoder'); ?>',
      variables:[]
    },
  
    lowpowertemperaturenode: {
      name: '<?php echo _('Low power temperature node'); ?>',
      updateinterval: 60,
      variables: [
        {name: '<?php echo _('Temperature'); ?>', type: 1, scale: 0.01, units: '°C' },
        {name: '<?php echo _('Battery Voltage'); ?>', type: 1, scale:0.001, units: 'V'}
      ]
    },
    
    emonTxV3_RFM12B_DiscreteSampling: {
      name: '<?php echo _('EmonTx V3 RFM12B DiscreteSampling'); ?>',
      updateinterval: 10,
      variables: [
        {name: '<?php echo _('Power 1'); ?>', type: 1, units: 'W'}, 
        {name: '<?php echo _('Power 2'); ?>', type: 1, units: 'W'}, 
        {name: '<?php echo _('Power 3'); ?>', type: 1, units: 'W'}, 
        {name: '<?php echo _('Power 4'); ?>', type: 1, units: 'W'},
        {name: '<?php echo _('Vrms'); ?>', type: 1, scale: 0.01, units: 'V'}, 
        {name: '<?php echo _('temp'); ?>', type: 1, scale: 0.1, units: '°C'}
      ]
    },

    emonTxV3_continuous_whtotals: {
      name: '<?php echo _('EmonTx V3 (Continuous sampling with Wh totals)'); ?>',
      updateinterval: 10,
      variables: [
        {name: '<?php echo _('Message Number'); ?>', type: 2 },
        {name: '<?php echo _('Power CT1'); ?>', type: 1, units: 'W'}, 
        {name: '<?php echo _('Power CT2'); ?>', type: 1, units: 'W'}, 
        {name: '<?php echo _('Power CT3'); ?>', type: 1, units: 'W'}, 
        {name: '<?php echo _('Power CT4'); ?>', type: 1, units: 'W'},
        {name: '<?php echo _('Wh CT1'); ?>', type: 2, units: 'Wh'}, 
        {name: '<?php echo _('Wh CT2'); ?>', type: 2, units: 'Wh'}, 
        {name: '<?php echo _('Wh CT3'); ?>', type: 2, units: 'Wh'}, 
        {name: '<?php echo _('Wh CT4'); ?>', type: 2, units: 'Wh'}
      ]
    },
    
    emonTH_DHT22_DS18B20: {
      name: '<?php echo _('EmonTH DHT22 DS18B20'); ?>',
      updateinterval: 60,
      variables: [
        {name: '<?php echo _('Internal temperature'); ?>', type: 1, scale: 0.1, units: '°C'}, 
        {name: '<?php echo _('External temperature'); ?>', type: 1, scale: 0.1, units: '°C'}, 
        {name: '<?php echo _('Humidity'); ?>', type: 1, scale: 0.1, units: '%'}, 
        {name: '<?php echo _('Battery Voltage'); ?>', type: 1, scale: 0.1, units: 'V'},
      ]
    },
    
    custom: {
      name: '<?php echo _('Custom decoder'); ?>',
      variables:[]
    },
  };
 
 redraw();
 
 var variable_edit_mode = false;
 
 var interval = setInterval(update,5000);
 
 function update()
 {
   nodes = node.getall();
   redraw();
 }
 function redraw()
 {
    var out = "";
    for (z in nodes)
    {
      var nodename = '<?php echo _('(Click to select a decoder)'); ?>';
      if (nodes[z].decoder!=undefined && nodes[z].decoder.name!=undefined) nodename = nodes[z].decoder.name;
        
      out += "<tr style='background-color:#eee' node="+z+"><td><b><?php echo _('Node'); ?> "+z+"</b></td><td><span class='select-decoder' node="+z+" mode='namedisplay'><b>"+nodename+"</b></span><span node="+z+" class='customdecoder'></span></td><td>"+list_format_updated(nodes[z].time)+"</td><td></td></tr>";
     
      var bytes = nodes[z].data.split(',');
      var pos = 0;
      
      if (nodes[z].decoder!=undefined && nodes[z].decoder.variables.length>0)
      {
        for (i in nodes[z].decoder.variables)
        {
          var variable = nodes[z].decoder.variables[i];
          
          out += "<tr style='padding:0px' node="+z+" variable="+i+"><td></td><td class='variable-name'>"+variable.name+" <i class='edit-variable icon-pencil' style='display:none'></i></td>";

          if (variable.type==0)
          {
            var value = parseInt(bytes[pos]);
            pos += 1;
          }
          
          if (variable.type==1)
          {
            var value = parseInt(bytes[pos]) + parseInt(bytes[pos+1])*256;
            if (value>32768) value += -65536;  
            pos += 2;
          }
          
          if (variable.type==2)
          {
            var value = parseInt(bytes[pos]) + parseInt(bytes[pos+1])*Math.pow(2,1*8) + parseInt(bytes[pos+2])*Math.pow(2,2*8) + parseInt(bytes[pos+3])*Math.pow(2,3*8);
            //if (value>32768) value += -65536;  
            pos += 4;
          }
          out += "<td>";
          
          if (variable.scale!=undefined) {
            value *= parseFloat(variable.scale);
            if (variable.scale==1.0) out += value.toFixed(0);
            else if (variable.scale==0.1) out += value.toFixed(1);
            else if (variable.scale==0.01) out += value.toFixed(2);
            else if (variable.scale==0.001) out += value.toFixed(3);
            else out += value;
          } else {
            out += value;
          }
          
          if (variable.units!=undefined) {
          
          if (variable.units=='u00b0C') variable.units = "°C";
              out += " "+variable.units;
          }
          
          var labelcolor = ""; if (variable.feedid) labelcolor = 'label-info';
          
          var updateinterval = nodes[z].decoder.updateinterval;
          
          var processliststr = ""; if (variable.processlist!=undefined) processliststr = processlist_ui.drawinline(variable.processlist);
          out += "</td><td style='text-align:right'>"+processliststr+"<span class='label "+labelcolor+" record' style='cursor:pointer' ><?php echo _('Config'); ?> <i class='icon-wrench icon-white'></i></span></td></tr>";
         
        }
      }
      
      if (nodes[z].decoder==undefined || nodes[z].decoder.variables.length==0)
      {
        out += "<tr><td></td><td><i style='color:#aaa'><?php echo _('Raw byte data:'); ?> "+nodes[z].data+"</i>"; 
        out += "</td><td></td></tr>";
      }
      
    }
    
    if (out=="") out = "<div class='alert alert-info' style='padding:40px; text-align:center'><h3><?php echo _('No nodes detected yet'); ?></h3><p><?php echo _('To use this module send a byte value csv string and the node id to: '); ?><a href='./set.json?nodeid=10&data=20,20,20,20'>"+path+"node/set.json?nodeid=10&data=20,20,20,20</a></p></div>";

    $("#nodes").html(out);
  }

  // Show edit
  $("#nodes").on("mouseover",'tr',function() {
    $(".icon-pencil").hide();
    if (!variable_edit_mode) $(this).find("td[class=variable-name] > i").show();
  });
  
  // Draw in line editing for a variable when the pencil icon is clicked.
  $("#nodes").on("click", ".edit-variable", function() {
    console.log("edit variable");
    
    // Fetch the nodeid and variableid from closest table row (tr)
    var nodeid = $(this).closest('tr').attr('node');
    var variableid = $(this).closest('tr').attr('variable');

    console.log("Nodeid: "+nodeid+" Variable: "+variableid);
    
    interval = clearInterval(interval);
    
    var currentname = nodes[nodeid].decoder.variables[variableid].name;
    var currentscale = nodes[nodeid].decoder.variables[variableid].scale;
    if (currentscale==undefined) currentscale = 1;
                                    
    // Inline editing html
    var out = "<div class='input-prepend input-append'>";
    out += "<span class='add-on'><?php echo _('Name:'); ?></span>";
    out += "<input style='width:150px' class='variable-name-edit' type='text'/ value='"+currentname+"'>";
    out += "<span class='add-on'><?php echo _('Datatype:'); ?></span>";
    out += "<select class='variable-datatype-selector' style='width:130px'><option value=1><?php echo _('Integer'); ?></option><option value=2><?php echo _('Unsigned long'); ?></option></select>";
    out += "<span class='add-on'><?php echo _('Scale:'); ?></span>";
    out += "<input class='variable-scale-edit' style='width:60px' type='text' value='"+currentscale+"' / >";
    out += "<span class='add-on'><?php echo _('Units:'); ?></span>";
    out += "<select class='variable-units-selector' style='width:60px;'><option value=''></option><option>W</option><option>kW</option><option>Wh</option><option>kWh</option><option>°C</option><option>V</option><option>mV</option><option>A</option><option>mA</option></select>";
    out += "<button class='btn save-variable'><?php echo _('Save'); ?></button>";
    out += "</div>";
    
    // Insert in place of variable name
    $("tr[node="+nodeid+"][variable="+variableid+"] td[class=variable-name]").html(out);
    
    // Its easiest to set a select input via jquery selectors
    $(".variable-datatype-selector").val(nodes[nodeid].decoder.variables[variableid].type);
    $(".variable-units-selector").val(nodes[nodeid].decoder.variables[variableid].units);
    
    // The variable edit mode flag disabled the edit icon from appearing on other variables while editing of one is in progress
    variable_edit_mode = true;
  });

  // Called when the save button is clicked on the inline variable editor
  $("#nodes").on("click",'.save-variable', function() 
  {
    variable_edit_mode = false;
    
    // Fetch the nodeid and variableid from closest table row (tr)
    var nodeid = $(this).closest('tr').attr('node');
    var variableid = $(this).closest('tr').attr('variable');
    
    // Fetch the edited values from the input fields & update the decoder
    nodes[nodeid].decoder.variables[variableid].name = $(".variable-name-edit").val();
    nodes[nodeid].decoder.variables[variableid].scale = $(".variable-scale-edit").val()*1;
    nodes[nodeid].decoder.variables[variableid].units = $(".variable-units-selector").val();    
    nodes[nodeid].decoder.variables[variableid].type = $(".variable-datatype-selector").val(); 
    
    // Save the decoder
    node.setdecoder(nodeid,nodes[nodeid].decoder);
    
    interval = setInterval(update,5000);
    // redraw, apply new decoder
    redraw();
  });
  
  
  $("#nodes").on("click",'.record', function() 
  {
    interval = clearInterval(interval);
    // Fetch the nodeid and variableid from closest table row (tr)
    var nodeid = $(this).closest('tr').attr('node');
    var variableid = $(this).closest('tr').attr('variable');

    $("#myModal-nodeid").html(nodeid);
    $("#myModal-variablename").html(nodes[nodeid].decoder.variables[variableid].name);
   
    processlist_ui.nodeid = nodeid;
    processlist_ui.variableid = variableid;

    processlist_ui.init();
    processlist_ui.draw();
    
  
    $("#myModal").modal('show');
    $("#myModal").attr('node',nodeid);
    $("#myModal").attr('variable',variableid);
    
  });
  
  $(".modal-exit").click(function() 
  {
    $("#myModal").modal('hide');
    update();
    interval = setInterval(update,5000);
  });

  
  $("#nodes").on("click",'.select-decoder', function() 
  {
    interval = clearInterval(interval);
    var nodeid = $(this).attr('node');
    var mode = $(this).attr('mode');
    
    var current_decoder = 'raw';
    if (nodes[nodeid].decoder!=undefined) {
      current_decoder = nodes[nodeid].decoder.decoder;
    }
    
    if (mode=='namedisplay')
    {
      var out = "";
      for (z in decoders)
      {
        var selected = ""; if (current_decoder==z) selected = "selected";
        out += "<option value='"+z+"' "+selected+">"+decoders[z].name+"</option>";
      }
      $(this).html("<select class='decoderselect' node="+nodeid+">"+out+"</select>");
    }
    
    $(this).attr('mode','selecting')
  
  });
  
  $("#nodes").on("change",'.decoderselect', function() 
  {
    var nodeid = $(this).attr('node');
    var decoder = $(this).val();
    
    if (decoder=='custom')
    {
      var out = " <div class='input-prepend input-append'>";
      out += "<span class='add-on'><?php echo _('Name:'); ?></span>";
      out += "<input style='width:150px' class='node-name-edit' type='text'/ >";
      out += "<span class='add-on'><?php echo _('No of variables:'); ?></span>";
      out += "<input style='width:60px' class='node-varnum-edit' type='text'/ >";
      out += "<button class='btn node-create' class='btn'><?php echo _('Create'); ?></button>";
      out += "</div>";
      $('.customdecoder[node='+nodeid+']').html(out);
    }
    else 
    {
      nodes[nodeid].decoder = decoders[decoder];
      nodes[nodeid].decoder.decoder = decoder;
      
      node.setdecoder(nodeid,nodes[nodeid].decoder);
      redraw();
      
      $(this).parent().html("<b>"+nodes[nodeid].decoder.name+"</b>");
      $(this).attr('mode','namedisplay');
      interval = setInterval(update,5000);
    }
  });
  
  
  $("#nodes").on("click",'.node-create', function() 
  {
    // Fetch the nodeid from closest table row (tr)
    var nodeid = $(this).closest('tr').attr('node');

    var nodename = $(".node-name-edit").val();
    var no_of_variables = parseInt($(".node-varnum-edit").val()); 
       
    nodes[nodeid].decoder = {
      name: nodename,
      updateinterval: 10,
      variables: []
    };
    
    for (var i=0; i<no_of_variables; i++)
    {
      nodes[nodeid].decoder.variables.push({name: "<?php echo _('variable:'); ?> "+(i+1), type: 1, scale: 1, units: ''});
    }
    
    nodes[nodeid].decoder.decoder = nodename.toLowerCase().replace(/ /g, '-');
    
    node.setdecoder(nodeid,nodes[nodeid].decoder);
    redraw();
    
    //interval = setInterval(update,5000);
    // redraw, apply new decoder
    //redraw();
  });
  
  // Calculate and color updated time
  function list_format_updated(time)
  {
    time = time * 1000;
    var now = (new Date()).getTime();
    var update = (new Date(time)).getTime();
    var lastupdate = (now-update)/1000;

    var secs = (now-update)/1000;
    var mins = secs/60;
    var hour = secs/3600

    var updated = secs.toFixed(0)+"<?php echo _('s ago'); ?>";
    if (secs>180) updated = mins.toFixed(0)+" <?php echo _('mins ago'); ?>";
    if (secs>(3600*2)) updated = hour.toFixed(0)+" <?php echo _('hours ago'); ?>";
    if (hour>24) updated = "<?php echo _('inactive'); ?>";

    var color = "rgb(255,125,20)";
    if (secs<25) color = "rgb(50,200,50)"
    else if (secs<60) color = "rgb(240,180,20)"; 

    return "<span style='color:"+color+";'>"+updated+"</span>";
  }
   
  processlist_ui.nodes = nodes;
  processlist_ui.feedlist = feed.list_assoc();
  processlist_ui.inputlist = input.list_assoc();
  var result = input.getallprocesses();
  if (processlist_ui.engines_hidden.length > 0) {
    for (p in result)  // for each processor
    {
        if (result[p][6]!=undefined) {  // processor has supported engines?
            for (var e=result[p][6].length-1; e > -1; e--) {  // for each processor engine
                for (h in processlist_ui.engines_hidden) {
                    if (result[p][6][e]==processlist_ui.engines_hidden[h]) { // if engine is to be hidden
                        result[p][6].splice(e, 1);       // remove engine from processor
                    }
                }
            }
            if (result[p][6].length == 0) {
                result[p][6] = undefined;    // if processor now has no engines, undefine its array
            }
        }
    }
  }
  processlist_ui.processlist = result;
  processlist_ui.events();
 
</script>
