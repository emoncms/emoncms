var processlist_ui =
{
  contexttype: 0,         // Editor type (0:input, 1:feed/virtual)
  contextid: 0,           // The current inputid or virtual feed id being edited
  contextprocesslist: [], // The current process list being edited

  processlist: [], // Cache this lists
  feedlist:[],
  inputlist:[],
  schedulelist:[],

  init_done: -1, // when 0 all lists are loaded
  
  engines_hidden:[],
  has_redis: 0,
  
  table: table,

  'draw':function(){
    var i = 0;
    var out="";
    
    console.log("contextid=" + this.contextid);
    console.log("contextprocesslist=" + this.contextprocesslist);
    
    if (this.contextprocesslist.length==0) {
      $("#process-table").hide();
      $("#noprocess").show();
    } else {
      $("#process-table").show();
      $("#noprocess").hide();
      for (z in this.contextprocesslist) {
        out += '<tr>';
        // Move process up or down
        out += '<td>';
        if (i > 0) {
          out += '<a class="move-process" title="Move up" processid='+i+' moveby=-1 ><i class="icon-arrow-up"></i></a>';
        }
        if (i < this.contextprocesslist.length-1) {
          out += '<a class="move-process" title="Move up" processid='+i+' moveby=1 ><i class="icon-arrow-down"></i></a>';
        }
        out += '</td>';

        // Process name and argument
        var processkey = this.contextprocesslist[z][0];
        var arg = "";
        var lastvalue = "";
        var processname = "";

        if (this.processlist[processkey] != undefined) {
          var procneedredis = (this.has_redis == 0 && this.processlist[processkey]['requireredis'] != undefined && this.processlist[processkey]['requireredis'] == true ? 1 : 0);
          if (this.processlist[processkey]['internalerror'] !== undefined && this.processlist[processkey]['internalerror'] == true) {
              arg += "<span class='label label-important' title='Value'>" + this.processlist[processkey]['internalerror_desc'] + "</span>";
              processname = "<span class='label label-important' title='Value'>" + this.processlist[processkey][0] + "</span>";
          }  
          else if (procneedredis) {
            arg += "<span class='label label-important' title='Value'>Process ´"+processkey+"´ not available. Redis not installed.</span>";
            processname = this.processlist[processkey][0];
          }
          else {
            // Check ProcessArg Type
            switch(this.processlist[processkey][1]) {
              case 0: // VALUE
                arg += "<span class='label label-info' title='Value'>";
                arg += "<i class='icon-edit icon-white'></i> ";
                arg += this.contextprocesslist[z][1];
                arg += "</span>";
                break;

              case 1: //INPUTID
                var inpid = this.contextprocesslist[z][1];
                if (this.inputlist[inpid]!=undefined) {
                arg += "<span class='label label-info' title='Input "+inpid+"'>";
                arg += "<i class='icon-signal icon-white'></i> ";
                arg += "Node "+this.inputlist[inpid].nodeid+":"+this.inputlist[inpid].name;
                if (this.inputlist[inpid].description!="") arg += " "+this.inputlist[inpid].description;
                arg += "</span>";
                lastvalue = "<span style='color:#888; font-size:12px'>(input last value:"+(this.inputlist[inpid].value*1).toFixed(2)+")</span>";
                } else {
                  arg += "<span class='label label-important'>Input "+schid+" does not exists or was deleted</span>"
                }
                break;

              case 2: //FEEDID
                var feedid = this.contextprocesslist[z][1];
                if (this.feedlist[feedid]!=undefined) {
                arg += "<a class='label label-info' title='Feed "+feedid+"' href='"+path+"vis/auto?feedid="+feedid+"'>";
                arg += "<i class='icon-list-alt icon-white'></i> ";
                if (this.feedlist[feedid].tag) arg += this.feedlist[feedid].tag+": ";
                arg += this.feedlist[feedid].name;
                arg += "</a>";
                lastvalue = "<span style='color:#888; font-size:12px'>(feed last value:"+(this.feedlist[feedid].value*1).toFixed(2)+")</span>";
                } else {
                  arg += "<span class='label label-important'>Feedid "+feedid+" does not exists or was deleted</span>"
                }
                break;

              case 4: // TEXT
                arg += "<span class='label label-info' title='Text'>";
                arg += "<i class='icon-edit icon-white'></i> ";
                arg += this.contextprocesslist[z][1];
                arg += "</span>";
                break;

              case 5: // SCHEDULEID
                var schid = this.contextprocesslist[z][1];
                if (this.schedulelist[schid]!=undefined) {
                arg += "<span class='label label-info' title='Schedule "+schid+"' >";
                arg += "<i class='icon-time icon-white'></i> ";
                arg += this.schedulelist[schid].name;
                arg += "</span>";
                } else {
                  arg += "<span class='label label-important'>Schedule "+schid+" does not exists or was deleted</span>"
                }
                break;
            }
            processname = this.processlist[processkey][0];
          }
        }
        else {
          processname = "UNSUPPORTED";
          arg += "<span class='label label-important' title='Value'>Process ´"+processkey+"´ not available. Module missing?</span>";
        }
        out += "<td>"+(i+1)+"</td><td>"+processname+"</td><td>"+arg+"</td><td>"+lastvalue+"</td>";
     
        // Delete process button (icon)
        out += '<td><a class="edit-process" title="Edit" processid='+i+'><i class="icon-pencil" style="cursor:pointer"></i></a></td>';
        out += '<td><a class="delete-process" title="Delete" processid='+i+'><i class="icon-trash" style="cursor:pointer"></i></a></td>';
        out += '</tr>';
        
        i++; // process id
      }
    }
    $('#process-table-elements').html(out);
  },

  'drawpreview':function(processlist){
    if (!processlist) return "";
    var localprocesslist = processlist_ui.decode(processlist);
    if (localprocesslist.length==0) {
      return ""
    } else {
      var out = "";
      if (this.init_done === 0)
      {
        for (z in localprocesslist) {
          // Process name and argument
          var processkey = localprocesslist[z][0];
          var key = "";

          if (this.processlist[processkey] != undefined) {
            var procneedredis = (this.has_redis == 0 && this.processlist[processkey]['requireredis'] !== undefined && this.processlist[processkey]['requireredis'] == true ? 1 : 0);
            if (this.processlist[processkey]['internalerror'] !== undefined && this.processlist[processkey]['internalerror'] == true) {
                out += "<span class='badge badge-important' title='" + this.processlist[processkey]['internalerror_desc'] + "'>"+ this.processlist[processkey]['internalerror_reason'] +"</span> "
            } else if (procneedredis) {
                out += "<span class='badge badge-important' title='Process ´"+processkey+"´ not available. Redis not installed.'>NO REDIS</span> "
            } else {
              // Check ProcessArg Type
              value = localprocesslist[z][1];
              key = "<small>"+this.processlist[processkey][0]+"</small>"; // name
              switch(this.processlist[processkey][1]) {
                case 0: // VALUE
                title = "Value " + value;
                color = 'info';
                out += "<span class='label label-"+color+"' title='"+title+"' style='cursor:default'>"+key+"</span> ";
                break;

                case 1: //INPUTID
                var inpid = localprocesslist[z][1];
                if (this.inputlist[value]!=undefined) {
                  title = "Input " +value+ " (Node "+this.inputlist[value].nodeid+":"+this.inputlist[value].name + (this.inputlist[value].description!="" ? " "+this.inputlist[value].description : "") +")";
                  color = 'info';
                  out += "<span class='label label-"+color+"' title='"+title+"' style='cursor:default'>"+key+"</span> ";
                } else {
                  return "<span class='badge badge-important' title='Input "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;

                case 2: //FEEDID
                if (this.feedlist[value]!=undefined) {
                  title = "Feed " + value + " (" + (this.feedlist[value].tag ? this.feedlist[value].tag+": " : "") + this.feedlist[value].name +")";
                  color = 'info';
                  out += "<a target='_blank' href='"+path+"vis/auto?feedid="+value+"'<span class='label label-"+color+"' title='"+title+"' style='cursor:pointer'>"+key+"</span></a> "; 
                } else {
                  return "<span class='badge badge-important' title='Feedid "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;

                case 4: // TEXT
                title = "Text " + value;
                color = 'info';
                out += "<span class='label label-"+color+"' title='"+title+"' style='cursor:default'>"+key+"</span> ";
                break;

                case 5: // SCHEDULEID
                if (this.schedulelist[value]!=undefined) {
                  title = "Schedule " +value + " (" + this.schedulelist[value].name + ")";
                  color = 'info';
                  out += "<span class='label label-"+color+"' title='"+title+"' style='cursor:default'>"+key+"</span> ";
                } else {
                  return "<span class='badge badge-important' title='Schedule "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;

                default:
                title = value;
                color = 'info';
                out += "<span class='label label-"+color+"' title='"+title+"' style='cursor:default'>"+key+"</span> ";
                break;
              }
            }
          } else {
              out += "<span class='badge badge-important' title='Process ´"+processkey+"´ not available. Module missing?'>UNSUPPORTED</span> "
          }
        }
      } else {
        return "<div class='muted'>wait…</div>"
      }
      return out;
    }
  },

  'group_drawerror':function(processlist){
    if (!processlist) return "";
    var localprocesslist = processlist_ui.decode(processlist);
    if (localprocesslist.length==0) {
      return ""
    } else {
      var out = "";
      if (this.init_done === 0)
      {
        for (z in localprocesslist) {
          // Process name and argument
          var processkey = localprocesslist[z][0];

          if (this.processlist[processkey] != undefined) {
            var procneedredis = (this.has_redis == 0 && this.processlist[processkey]['requireredis'] !== undefined && this.processlist[processkey]['requireredis'] == true ? 1 : 0);
            if (this.processlist[processkey]['internalerror'] !== undefined && this.processlist[processkey]['internalerror'] == true) {
                out += "<span class='badge badge-important' title='" + this.processlist[processkey]['internalerror_desc'] + "'>"+ this.processlist[processkey]['internalerror_reason'] + "</span> "
            }  
            else if (procneedredis) {
                out += "<span class='badge badge-important' title='Process ´"+processkey+"´ not available. Redis not installed.'>NO REDIS</span> "
            } else {
              // Check ProcessArg Type
              value = localprocesslist[z][1];
              switch(this.processlist[processkey][1]) {
                case 1: //INPUTID
                var inpid = localprocesslist[z][1];
                if (this.inputlist[value]==undefined) {
                  out +=  "<span class='badge badge-important' title='Input "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;

                case 2: //FEEDID
                if (this.feedlist[value]==undefined) {
                  out +=  "<span class='badge badge-important' title='Feedid "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;

                case 5: // SCHEDULEID
                if (this.schedulelist[value]==undefined) {
                  out +=  "<span class='badge badge-important' title='Schedule "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;
              }
            }
          } else {
              out += "<span class='badge badge-important' title='Process ´"+processkey+"´ not available. Module missing?'>UNSUPPORTED</span> "
          }
          if (out != "") return out; // return first error
        }
      }
      return out;
    }
  },

  'events':function(){
    $("#processlist-ui #feed-engine").change(function(){
      var engine = $(this).val();
      $("#feed-interval").hide();
      if (engine==6 || engine==5 || engine==4 || engine==1) $("#feed-interval").show();

      var processid = $("#process-select").val();
      var datatype = processlist_ui.processlist[processid][4]; // 1:REALTIME, 2:DAILY, 3:HISTOGRAM
      // If the datatype is daily then the interval is fixed to 3600s x 24h = 1d and user cant select other
      if (datatype==2) {
        $("#feed-interval option").hide();          // Hide all
        $("#feed-interval option").prop('disabled', true);  // for IE hide (grayed out)
        $("#feed-interval option[value=86400]").show();   // show this 3600*24
        $("#feed-interval option[value=86400]").prop('disabled', false);  //for IE show
        $("#feed-interval").val(86400); 
      } else {
        $("#feed-interval option").show(); // Show all
        $("#feed-interval option").prop('disabled', false);  //for IE show
        $("#feed-interval").val(10);   // default to 10s
      } 
    });

    $('#processlist-ui #process-add, #processlist-ui #process-edit').click(function(){
      var processid = $('#process-select').val();
      var process = processlist_ui.processlist[processid];
      var arg = '';

      // Check ProcessArg Type
      switch(process[1]) {
        case 0: // VALUE (scale, offset)
          arg = $("#value-input").val();
          arg = parseFloat(arg.replace(",", "."));
          if (isNaN(arg)) {
            alert('ERROR: Value must be a valid number');
            return false;
          }
          break;

        case 1: //INPUTID (* / + - by input)
          arg = $("#input-select").val();
          break;

        case 2: //FEEDID
          var feedid = $("#feed-select").val();

          if (feedid==-1) {
            var feedname = $('#new-feed-name').val();
            var feedtag = $('#new-feed-tag').val();
            var engine = $('#feed-engine').val();
            var datatype = process[4];

            var options = {};
            options = {interval:$('#feed-interval').val()};

            if (feedname == '') {
              alert('ERROR: Please enter a feed name');
              return false;
            }

            var result = feed.create(feedtag,feedname,datatype,engine,options);
            feedid = result.feedid;

            if (!result.success || feedid<1) {
              alert('ERROR: Feed could not be created, '+result.message);
              return false;
            }

            processlist_ui.feedlist[feedid] = {'id':feedid, 'name':feedname,'value':'n/a','tag':feedtag,'datatype':datatype};
            processlist_ui.showfeedoptions(processid);  // Refresh Feedlist
          }
          arg = feedid;
          break;

        case 3: // NONE
          arg = 0;
          break;

        case 4: // TEXT
          arg = $("#text-input").val();
          break;

        case 5: // SCHEDULEID
          arg = $("#schedule-select").val();
          break;
      }

      if ($(this).attr("id") == "process-edit") {
        processlist_ui.contextprocesslist[$("#type-btn-edit").attr('curpos')] = ([processid,""+arg]);
        $("#process-header-add").show();
        $("#process-header-edit").hide();
        $("#type-btn-add").show();
        $("#type-btn-edit").hide();
        processlist_ui.scrollto($("a.edit-process[processid='"+$("#type-btn-edit").attr('curpos')+"']"));
      } else {
        processlist_ui.contextprocesslist.push([processid,""+arg]);  
      }

      processlist_ui.draw();
      processlist_ui.modified();
    });

    $('#processlist-ui #process-select').change(function(){
      var processid = $(this).val();

      $("#description").html("");
      $("#type-value").hide();
      $("#type-text").hide();
      $("#type-input").hide();
      $("#type-feed").hide();
      $("#type-schedule").hide();

      // Check ProcessArg Type
      switch(processlist_ui.processlist[processid][1]) {
        case 0: // VALUE
          $("#type-value").show();
          break;
        case 1: //INPUTID
          $("#type-input").show();
          break;
        case 2: //FEEDID
          $("#type-feed").show();
          processlist_ui.showfeedoptions(processid);
          break;
        case 4: // TEXT
          $("#type-text").show();
          break;
        case 5: // SCHEDULEID
          $("#type-schedule").show();
          break;
      }
      if (processlist_ui.processlist[processid]['desc'] === undefined || processlist_ui.processlist[processid]['desc'] =="") {
        $("#description").html("<b style='color: orange'>No process description available for process '"+processlist_ui.processlist[processid][0]+"' with id '"+processid+"'.<br>Add a description to Module\\<i>module_name</i>\\<i>module_name</i>_processlist.php in process_list() function, $list[] array at the 'desc' key.</b><br>Please <a target='_blank' href='https://github.com/emoncms/emoncms/issues/new'>click here</a> and paste the text above to ask a developer to include a process description.</b>");
      } else {
        $("#description").html(processlist_ui.processlist[processid]['desc']);

	var does_modify = "<p><b>Output:</b> Modified value passed onto next process step</p>";
	var does_not_modify = "<p><b>Output:</b> Does NOT modify value passed onto next process step</p>";
	var redis_required = "<p><b>REDIS:</b> Requires REDIS</p>";
	var help = "<p><b>See Also:</b> ";

	if ('help_page' in processlist_ui.processlist[processid] &&
	    'help_url' in processlist_ui.processlist[processid] &&
	    typeof processlist_ui.processlist[processid]['help_page'] === 'string' &&
	    typeof processlist_ui.processlist[processid]['help_url'] === 'string') {
		$("#description").append(help + '<a href="' + processlist_ui.processlist[processid]['help_url'] + '">' + processlist_ui.processlist[processid]['help_page']);
	}

	if ('nochange' in processlist_ui.processlist[processid] &&
	    processlist_ui.processlist[processid]['nochange'] == true) {
		$("#description").append(does_not_modify);
	} else {
		$("#description").append(does_modify);
	}

	if ('requireredis' in processlist_ui.processlist[processid] &&
	    processlist_ui.processlist[processid]['requireredis'] == true) {
		$("#description").append(redis_required);

	}
      }
      
    });

    $('#processlist-ui #feed-select').change(function(){
      var feedid = $("#feed-select").val();

      if (feedid == -1) {
        $("#new-feed-name").show();
        $("#processlist-ui #feed-engine").change(); // select available interval for engine
        // If there's only one feed engine to choose from then dont show feed engine selector
        // CHAVEIRO: Commented for now so user can see what processor it's using.
        //var processid = $('#process-select').val();
        //var engines = processlist_ui.processlist[processid][6];   // 0:MYSQL, 5:PHPFINA, 6:PHPFIWA
        //if (engines.length > 1) 
          $("#feed-engine, .feed-engine-label").show();
      } else {
        $("#new-feed-name").hide();
        $("#feed-interval").hide();
        $("#feed-engine, .feed-engine-label").hide(); 
      }
    });

    $('#processlist-ui .table').on('click', '.delete-process', function(){
      processlist_ui.contextprocesslist.splice($(this).attr('processid'),1);
      var processid = $(this).attr('processid')*1;
      processlist_ui.draw();
      processlist_ui.modified();
    });

    $('#processlist-ui .table').on('click', '.move-process', function(){
      var processid = $(this).attr('processid')*1;
      var curpos = parseInt(processid);
      var moveby = parseInt($(this).attr('moveby'));
      var newpos = curpos + moveby;
      if (newpos>=0 && newpos<processlist_ui.contextprocesslist.length){ 
        processlist_ui.contextprocesslist = processlist_ui.array_move(processlist_ui.contextprocesslist,curpos,newpos);
        processlist_ui.draw();
        processlist_ui.modified();
      }
    });

    $('#processlist-ui .table').on('click', '.edit-process', function(){
      var process = processlist_ui.contextprocesslist[$(this).attr('processid')];
      var processid = process[0];
      var processval = process[1];
      var curpos = parseInt($(this).attr('processid'));

      $("#process-header-add").hide();
      $("#process-header-edit").show();
      $("#type-btn-add").hide();
      $("#type-btn-edit").show();
      $("#type-btn-edit").attr('curpos', curpos);

      if (processlist_ui.processlist[processid] == undefined) {
        if (processlist_ui.contexttype == 0) {
          $("#process-select").val(1); // default process for input context
        } else {
          $("#process-select").val(53); // default process for feed context
        }
        $("#processlist-ui #process-select").change();  // Force a refresh
      } else {
        $("#process-select").val( processlist_ui.processlist[processid]['id']);
        $("#processlist-ui #process-select").change(); // Force a refresh
        // Check ProcessArg Type
        switch(processlist_ui.processlist[processid][1]) {
          case 0: // VALUE
            $("#value-input").val(processval);
            break;
          case 1: //INPUTID
            $("#input-select").val(processval);
            break;
          case 2: //FEEDID
            $("#feed-select").val(processval);
            $('#processlist-ui #feed-select').change();  // refresh feed select
            break;
          case 4: // TEXT
            $("#text-input").val(processval);
            break;
          case 5: // SCHEDULEID
            $("#schedule-select").val(processval);
            break;
        }
      }
      processlist_ui.scrollto($('#process-header-edit'));
    });

    $('#processlist-ui #process-cancel').click(function(){
      $("#process-header-add").show();
      $("#process-header-edit").hide();
      $("#type-btn-add").show();
      $("#type-btn-edit").hide();
      processlist_ui.scrollto($("a.edit-process[processid='"+$("#type-btn-edit").attr('curpos')+"']"));
    });

    $("#processlistModal").on('click', '#close', function(){
      $("#processlistModal").modal('hide');
    });

  },

  'showfeedoptions':function(processid){
    var prc = this.processlist[processid][2];     // process function
    var feedwrite = this.processlist[processid]['feedwrite']; // process writes to feed
    var engines = this.processlist[processid][6];   // 0:MYSQL, 5:PHPFINA, 6:PHPFIWA
    var datatype = this.processlist[processid][4];  // 0:UNDEFINED, 1:REALTIME, 2:DAILY, 3:HISTOGRAM

    var feedgroups = [];
    for (z in this.feedlist) {
      if (datatype == 0 || (this.feedlist[z].datatype == datatype)) {
        if (this.contexttype == 0 && this.feedlist[z].engine == 7 && feedwrite == true) { //input context and virtual feed and process writes to feed ?
          continue; // Dont list virtual feed
        }
        var group = (this.feedlist[z].tag === null ? "NoGroup" : this.feedlist[z].tag);
        if (group!="Deleted") {
          if (!feedgroups[group]) feedgroups[group] = []
          feedgroups[group].push(this.feedlist[z]);
        }
       }
    }
    var out = "<option value=-1>CREATE NEW:</option>";
    for (z in feedgroups) {
      out += "<optgroup label='"+z+"'>";
      for (p in feedgroups[z]) {
        out += "<option value="+feedgroups[z][p]['id']+">"+feedgroups[z][p].name+"</option>";
      }
      out += "</optgroup>";
    }
    $("#feed-select").html(out);

    $("#feed-data-type option").hide();  // Start by hiding all feed engine options
    $("#feed-data-type option").prop('disabled', true);  //for IE hide (grayed out)
    $("#feed-data-type").val(datatype); // select datatype
    $("#feed-data-type option[value="+datatype+"]").show();   // Show only the feed engine options that are available
    $("#feed-data-type option[value="+datatype+"]").prop('disabled', false);  //for IE show

    $("#feed-engine option").hide();  // Start by hiding all feed engine options
    $("#feed-engine option").prop('disabled', true);  //for IE hide (grayed out)
    for (e in engines) { 
      $("#feed-engine option[value="+engines[e]+"]").show();   // Show only the feed engine options that are available
      $("#feed-engine option[value="+engines[e]+"]").prop('disabled', false);  //for IE show
    }

    $("#feed-engine, .feed-engine-label").hide(); 
    if (typeof(engines) != "undefined") {
      $("#feed-engine").val(engines[0]);     // Select the first feed engine in the engines array by default
      $("#feed-select option[value=-1]").show(); // enable create new feed
      $("#feed-select option[value=-1]").prop('disabled', false);  //for IE show
    } else {
      $("#feed-select option[value=-1]").hide(); // disable create new feed as we have no supported engines for this proccess
      $("#feed-select option[value=-1]").prop('disabled', true);  //for IE hide (grayed out)
      for (f in this.feedlist) {
        if (datatype == 0 || (this.feedlist[f].datatype == datatype)) {  // Only feeds of the supported datatype
          $("#feed-select").val(this.feedlist[f].id); // select first feed
          break;
        }
      }
    }

    $('#processlist-ui #feed-select').change();  // refresh feed select
  },

  'modified':function(){
    $("#save-processlist").attr('class','btn btn-warning').text("Changed, press to save");
  },

  'saved':function(t){
    $("#save-processlist").attr('class','btn btn-success').text("Saved");
    // Update context table immedietly
    for (z in t.data) {
      if (t.data[z].id == processlist_ui.contextid) {
        t.data[z].processList = processlist_ui.encode(processlist_ui.contextprocesslist);
      }
    }
    table.draw();
  },

  'decode':function(str){
    var processlist = [];
    if (str != null && str != "") {
      var tmp = str.split(",");
      for (n in tmp) {
        var process = tmp[n].split(":"); 
        processlist.push(process);
      }
    }
    return processlist;
  },

  'encode':function(array){
    var parts = [];
    for (z in array) parts.push(array[z][0]+":"+array[z][1]);
    return parts.join(",");
  },

  'array_move':function(array,old_index, new_index){
    if (new_index >= array.length) {
      var k = new_index - array.length;
      while ((k--) + 1) {
        array.push(undefined);
      }
    }
    array.splice(new_index, 0, array.splice(old_index, 1)[0]);
    return array; 
  },

  'scrollto':function(scrollTo){
    var container = $('#processlist-ui');
    container.animate({
      scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
    });
  },

  'init':function(contexttype){
    this.contexttype = contexttype;
    this.init_done = 4; // going to load 4 lists

    // Processors Select List
    $.ajax({ url: path+"process/list.json", dataType: 'json', async: true, success: function(result){

      for (p in result)  // for each processor
      {
        result[p]['feedwrite']=false;
        if (result[p][6]!=undefined) { // processor has supported engines?
          result[p]['feedwrite']=true; // If has an engine so assume process writes to feed 
          if (processlist_ui.engines_hidden.length > 0) {
            for (var e=result[p][6].length-1; e > -1; e--) {  // for each processor engine
              for (h in processlist_ui.engines_hidden) {
                if (result[p][6][e]==processlist_ui.engines_hidden[h]) { // if engine is to be hidden
                  result[p][6].splice(e, 1);     // remove engine from processor
                }
              }
            }
          }
          if (result[p][6].length == 0) {
            result[p][6] = undefined;  // if processor now has no engines, undefine its array
          }
        }
      }

      processlist_ui.processlist = result;
      var processgroups = [];
      for (z in processlist_ui.processlist) {
        if (processlist_ui.contexttype == 1 && processlist_ui.processlist[z]['feedwrite'] == true) {
          continue;  // in feed context and processor has a engine? dont show on virtual processlist selector
        }
        var group = processlist_ui.processlist[z][5];
        if (processlist_ui.contexttype == 0 && group=="Virtual") { 
          continue;  // in input context and group name is virtual? dont show on input processlist selector
        }
        if (group!="Deleted") {
          if (!processgroups[group]) processgroups[group] = []
          processlist_ui.processlist[z]['id'] = z;
          processgroups[group].push(processlist_ui.processlist[z]);
        }
      }

      var out = "";
      for (pg in processgroups) {
        out += "<optgroup " + (pg == "Hidden" ? "hidden " : "") + "label='"+pg+"'>";
        for (p in processgroups[pg])
        {
          var procdisabled = "";
          var procneedredis = "";
          if ((pg == "Hidden") || 
              (processgroups[pg][p]['internalerror'] != undefined && processgroups[pg][p]['internalerror'] == true)
              )
          {
            procdisabled = 'hidden';
            procneedredis = "";
          }
          else  if (processlist_ui.has_redis == 0 && processgroups[pg][p]['requireredis'] != undefined && processgroups[pg][p]['requireredis'] == true) { 
            procdisabled = 'disabled=""';
            procneedredis = " (needs REDIS)";
          }
          out += "<option "+procdisabled+" value="+processgroups[pg][p]['id']+">"+processgroups[pg][p][0]+procneedredis+"</option>";
        }
        out += "</optgroup>";
      }
      $("#process-select").html(out);
      processlist_ui.initprogress();
    }});

    // Feeds Select List
    $.ajax({ url: path+"feed/list.json", dataType: 'json', async: true, success: function(result) {
      var feeds = {};
      for (z in result) {
        feeds[result[z].id] = result[z]; 
      }
      processlist_ui.feedlist = feeds;
      processlist_ui.initprogress();
    }});

    // Schedule Select List
    $.ajax({ url: path+"schedule/list.json", dataType: 'json', async: true, success: function(result) {
      var schedules = {};
      for (z in result) schedules[result[z].id] = result[z];

      processlist_ui.schedulelist = schedules;
      var groupname = {0:'Public',1:'Mine'};
      var groups = [];
      //for (z in result) schedules[result[z].id] = result[z];

      for (z in processlist_ui.schedulelist) {
        var group = processlist_ui.schedulelist[z].own;
        group = groupname[group];
        if (!groups[group]) groups[group] = [];
        processlist_ui.schedulelist[z]['_index'] = z;
        groups[group].push(processlist_ui.schedulelist[z]);
      }

      var out = "";
      for (z in groups) {
        out += "<optgroup label='"+z+"'>";
        for (p in groups[z])
        {
          out += "<option value="+groups[z][p]['id']+">"+groups[z][p]['name']+(z!=groupname[1]?" ["+groups[z][p]['id']+"]":"")+"</option>";
        }
        out += "</optgroup>";
      }
      $("#schedule-select").html(out);
      processlist_ui.initprogress();
    }});

    // Input Select List  
    $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(result) {
      var inputs = {};
      for (z in result) inputs[result[z].id] = result[z];
      processlist_ui.inputlist = inputs;

      var groups = [];
      for (z in processlist_ui.inputlist) {
        var group = processlist_ui.inputlist[z]['nodeid'];
        group = 'Node ' + group;
        if (!groups[group]) groups[group]=[];
        groups[group].push(processlist_ui.inputlist[z]);
      }

      var out = "";
      for (z in groups) {
        out += "<optgroup label='"+z+"'>";
        for (p in groups[z])
        {
          out += "<option value="+groups[z][p]['id']+">"+groups[z][p]['name']+ ": " + groups[z][p]['description'] + "</option>";
        }
        out += "</optgroup>";
      }
      $("#input-select").html(out);
      processlist_ui.initprogress();
    }});

    processlist_ui.events();
  },

  'initprogress':function(){
    processlist_ui.init_done--;
    if (processlist_ui.init_done == 0) {
      processlist_ui.draw();
      table.draw();
      if (processlist_ui.contexttype == 0) {
        $("#process-select").val(1); // default process for input context
      } else {
        $("#process-select").val(53); // default process for feed context
      }
      $("#processlist-ui #process-select").change();  // Force a refresh
    }
  },

  'load': function(contextid,contextprocesslist,contextname,newfeedname,newfeedtag){
    this.contextid = contextid;
    this.contextprocesslist = contextprocesslist;
    $("#contextname").html(contextname);
    $("#new-feed-name").val(newfeedname);
    $("#new-feed-tag").val(newfeedtag);
    $("#process-header-add").show();
    $("#process-header-edit").hide();
    $("#type-btn-add").show();
    $("#type-btn-edit").hide();
    processlist_ui.scrollto($('#processlist-ui'));
    this.draw();
    $("#save-processlist").attr('class','btn btn-success').text("Not modified");
    $("#processlist-ui #process-select").change(); // Force a refresh
    $("#processlistModal").modal('show');          // Show
    this.adjustmodal();
  },

  'adjustmodal':function() {
    if ($("#processlistModal").length) {
      var h = $(window).height() - $("#processlistModal").position().top - 180;
      $("#processlist-ui").height(h);
    }
  }
}
