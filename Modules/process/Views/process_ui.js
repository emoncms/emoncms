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
    
    // console.log("contextid=" + this.contextid);
    // console.log("contextprocesslist=" + this.contextprocesslist);
    
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
          out += '<a class="move-process" title="'+_Tr("Move up")+'" processid='+i+' moveby=-1 ><i class="icon-arrow-up"></i></a>';
        } else {
          out += "<span style='display: inline-block; width:14px; ' />";
        }

        if (i < this.contextprocesslist.length-1) {
          out += "<a class='move-process' title='"+_Tr("Move down")+"' processid="+i+" moveby=1 ><i class='icon-arrow-down'></i></a>";
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
                arg += "<span class='label label-info' title='"+_Tr("Value")+"'>";
                arg += "<i class='icon-edit icon-white'></i> ";
                arg += this.contextprocesslist[z][1];
                arg += "</span>";
                break;

              case 1: //INPUTID
                var inpid = this.contextprocesslist[z][1];
                if (this.inputlist[inpid]!=undefined) {
                arg += "<span class='label label-info' title='"+_Tr("Input")+" "+inpid+"'>";
                arg += "<i class='icon-signal icon-white'></i> ";
                arg += "Node "+this.inputlist[inpid].nodeid+":"+this.inputlist[inpid].name;
                if (this.inputlist[inpid].description!="") arg += " "+this.inputlist[inpid].description;
                arg += "</span>";
                lastvalue = "<span style='color:#888; font-size:12px'>("+_Tr("input last value:")+" "+(this.inputlist[inpid].value*1).toFixed(2)+")</span>";
                } else {
                  arg += "<span class='label label-important'>Input "+schid+" does not exists or was deleted</span>"
                }
                break;

              case 2: //FEEDID
                var feedid = this.contextprocesslist[z][1];
                if (this.feedlist[feedid]!=undefined) {
                arg += "<a class='label label-info feedaccesslabel' title='"+_Tr("Feed")+" "+feedid+"' href='"+path+"vis/auto?feedid="+feedid+"'>";
                arg += "<i class='icon-list-alt icon-white'></i> ";
                if (this.feedlist[feedid].tag) arg += this.feedlist[feedid].tag+": ";
                arg += this.feedlist[feedid].name;
                arg += "</a>";
                lastvalue = "<span style='color:#888; font-size:12px'>("+_Tr("feed last value:")+" "+(this.feedlist[feedid].value*1).toFixed(2)+")</span>";
                } else {
                  arg += "<span class='label label-important'>Feedid "+feedid+" does not exists or was deleted</span>"
                }
                break;

              case 4: // TEXT
                arg += "<span class='label label-info' title='"+_Tr("Text")+"'>";
                arg += "<i class='icon-edit icon-white'></i> ";
                arg += this.contextprocesslist[z][1];
                arg += "</span>";
                break;

              case 5: // SCHEDULEID
                var schid = this.contextprocesslist[z][1];
                if (this.schedulelist[schid]!=undefined) {
                arg += "<span class='label label-info' title='"+_Tr("Schedule")+" "+schid+"' >";
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
        out += '<td><a class="edit-process" title="'+_Tr("Edit")+'" processid='+i+'><i class="icon-pencil" style="cursor:pointer"></i></a></td>';
        out += '<td><a class="delete-process" title="'+_Tr("Delete")+'" processid='+i+'><i class="icon-trash" style="cursor:pointer"></i></a></td>';
        out += '</tr>';
        
        i++; // process id
      }
    }
    $('#process-table-elements').html(out);
  },

  'drawpreview':function(processlist,input){
    if (!processlist) return "";
    var localprocesslist = processlist_ui.decode(processlist);
    if (localprocesslist.length==0) {
      return ""
    } else {
      var out = "";
      // create coloured link or span for each process 
      for(b of this.getBadges(processlist,input)){
        out+= b.href ? '<a target="_blank" href="'+b.href+'"' : '<span';
        out+= ' class="label '+b.cssClass+'" title="'+b.title+'">';
        out+= (b.text||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        out+= b.href ? '</a> ' : '</span> ';
      }
      return out;
    }
  },
  'getBadges': function (processlist,input) {
    if (!processlist) return ""
    var processPairs = processlist.split(",")
    // create empty list of badges
    let badges = []
    // array of short names
    const processList_short_names = [null,'log','x','+','kwh','kwhd','x inp','ontime','kwhinckwhd','kwhkwhd','update','+ inp','/ inp','phaseshift','accumulate','rate','hist','average','flux','pwrgain','pulsdiff','kwhpwr','- inp','kwhkwhd','> 0','< 0','unsign','max','min','+ feed','- feed','x feed','/ feed','= 0','whacc','MQTT','null','ori','!sched 0','!sched N','sched 0','sched N','0? skip','!0? skip','N? skip','!N? skip','>? skip','>=? skip','<? skip','<=? skip','=? skip','!=? skip','GOTO']
    // set process types and what process they're accociated with
    const types = [
      {name: 'user value',  cssClass: 'label-important',  title: 'Value: {longText} - {value}',                                  process_ids: [2,3,46,47,48,49,50,51,52]},
      {name: 'input',       cssClass: 'label-warning',    title: 'Input: {longText} - ({input.nodeid}:{input.name}) {input.description}',    process_ids: [6,11,12,22]},
      {name: 'feed',        cssClass: 'label-info',       title: 'Feed: {longText} - ({feed.tag}:{feed.name})  [{feed.id}]',     process_ids: [1,4,5,7,8,9,10,13,14,15,16,17,18,19,20,21,23,27,28,34]},
      {name: '',            cssClass: 'label-important',  title: 'Text: {longText} - {value}',                                   process_ids: [24,25,26,33,36,37,42,43,44,45]},
      {name: 'feed',        cssClass: 'label-warning',    title: 'Feed: {longText}',                                             process_ids: [29,30,31,32]},
      {name: 'topic',       cssClass: 'label-info',       title: 'Topic: {longText} - {value}',                                  process_ids: [35]},
      {name: 'schedule',    cssClass: 'label-warning',    title: 'Schedule: {longText} - {schedule.name}',                       process_ids: [38,39,40,41]},
      {name: 'other',       cssClass: 'label-default',    title: '{longText}',                                                   process_ids: "eventp__ifmuteskip|eventp__ifnotmuteskip|eventp__ifrategtequalskip|eventp__ifrateltskip|eventp__sendemail|process__error_found|process__max_value_allowed|process__min_value_allowed|schedule__if_not_schedule_null|schedule__if_not_schedule_zero|schedule__if_schedule_null|schedule__if_schedule_zero".split('|')}
    ]
    for (z in processPairs)
    {
      // create empty badge object to store all the properties
      let badge = {}
      var keyvalue = processPairs[z].split(":")
      var key = parseInt(keyvalue[0])
      key = isNaN(key) ? keyvalue[0] : key;
      // get badge type from the known process id (key)
      badge.type_key = types.findIndex(function(type){
        // if true returns index
        return type.process_ids.indexOf(key) > -1
      })
      
      // set badge properties
      badge.type = types[badge.type_key]
      badge.text = processList_short_names[key]
      badge.longText = this.processlist[key] ? this.processlist[key][0] : ''
      badge.value = keyvalue[1]
      badge.typeName = badge.type.name
      badge.cssClass = badge.type.cssClass
      badge.input =  input || {}
      badge.feed =  this.feedlist[badge.value] || {}
      badge.schedule = this.schedulelist[badge.value] || {}
      badge.href = badge.typeName == 'feed' ? path+"vis/auto?feedid="+badge.value : false;
      // pass the collected badge object as values for the title string template
      badge.title = badge.type.title.format(badge);
      
      // add badge to list or add a blank one if there are any issues.
      if(this.init_done === 0){
        badges.push(badge);
      } else if(this.has_redis == 0 && this.processlist[key]['requireredis'] !== undefined && this.processlist[key]['requireredis'] == true ? 1 : 0){
        // no reids
        badges.push({
          text: this.processlist[key]['internalerror_reason'],
          title: this.processlist[key]['internalerror_desc'],
          cssClass: 'badge-important',
          href: false
        })
      } else if(!badge.value){
        // input,feed or schedule doesnt exist
        badges.push({
          title: '{typeName} {value} does not exist or was deleted'.format(badge),
          text: 'ERROR',
          cssClass: 'badge-important',
          href: false
        })
      } else if(!this.processlist[key]){
        // process not available
        badges.push({
          title: '{typeName} {value} does not exist or was deleted'.format(badge),
          text: 'UNSUPPORTED',
          cssClass: 'badge-important',
          href: false
        })
      } else {
        // default else
        badges.push({
          text: 'wait&hellip;',
          title: '',
          cssClass: 'muted',
          href: false
        })
      }
    }
    
    return badges;
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

            units = {
              'log_to_feed':'',//Log to feed 
              'power_to_kwh':'kWh',//Power to kWh
              'power_to_kwhd':'kWh',//Power to kWh/d
              'whinc_to_kwhd':'Wh',//Wh increments to kWh/d
              'kwh_to_kwhd_old':'',//kWh to kWh/d (OLD)
              'update_feed_data':'',//Upsert feed at day
              'ratechange':'',//Rate of change
              'histogram':'',//Histogram
              'average':'',//Daily Average
              'heat_flux':'',//Heat flux
              'power_acc_to_kwhd':'',//Power gained to kWh/d
              'pulse_diff':'',//Total pulse count to pulse increment
              'kwh_to_kwhd':'',//kWh to kWh/d
              'min_value':'',//Min daily value
              'sub_feed':'',// - feed
              'multiply_by_feed':'',// * feed
              'divide_by_feed':'',// / feed
              'wh_accumulator':'Wh',//Wh Accumulator
              'source_feed_data_time':'',//Source Feed
              //'get_feed_data_day':'',//Source Daily (TBD)
              'add_source_feed':'',// + source feed
              'sub_source_feed':'',// - source feed
              'multiply_by_source_feed':'',// * source feed
              'divide_by_source_feed':'',// / source feed
              'reciprocal_by_source_feed':''//1/ source feed
            };
            unit = units[process[2]] || ''
            var result = feed.create(feedtag,feedname,datatype,engine,options,unit);
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
      // console.log(processlist_ui.processlist[processid][0]);
      if (processid) {
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

          var does_modify = "<p><b>Output:</b> "+_Tr("Modified value passed onto next process step.")+"</p>";
          var does_not_modify = "<p><b>Output:</b> "+_Tr("Does NOT modify value passed onto next process step.")+"</p>";
          var redis_required = "<p><b>REDIS:</b> "+_Tr("Requires REDIS.")+"</p>";
          var help = _Tr("Click here for additional information about this process.");

          if ('helpurl' in processlist_ui.processlist[processid] &&
              typeof processlist_ui.processlist[processid]['helpurl'] === 'string') {
            $("#description").append('<p><a href="' + processlist_ui.processlist[processid]['help_url'] + '">' + help+'</p>');
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
        }//end of if proccessid
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
    $feedSelect = $('#feed-select');
    $feedEngineSelect = $('#feed-engine');
    $feedTypeSelect = $('#feed-data-type');

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
    // overwrite feed list
    $feedSelect.data('value',$feedSelect.val());// store previous value before <select> changes
    $feedSelect.html(out);
    // recall the old value if available
    if($feedSelect.data('value')!=""){
      $feedSelect.val($feedSelect.data('value'));
      $feedSelect.data('value','');    
    }
    $feedTypeSelect.find("option").hide();  // Start by hiding all feed engine options
    $feedTypeSelect.find("option").prop('disabled', true);  //for IE hide (grayed out)
    $feedTypeSelect.val(datatype); // select datatype
    $feedTypeSelect.find("option[value="+datatype+"]").show();   // Show only the feed engine options that are available
    $feedTypeSelect.find("option[value="+datatype+"]").prop('disabled', false);  //for IE show

    $feedEngineSelect.find("option").hide();  // Start by hiding all feed engine options
    $feedEngineSelect.find("option").prop('disabled', true);  //for IE hide (grayed out)
    for (e in engines) { 
      $feedEngineSelect.find("option[value="+engines[e]+"]").show();   // Show only the feed engine options that are available
      $feedEngineSelect.find("option[value="+engines[e]+"]").prop('disabled', false);  //for IE show
    }

    $feedEngineSelect.hide();
    $(".feed-engine-label").hide(); 
    if (typeof(engines) != "undefined") {
      $feedEngineSelect.val(engines[0]);     // Select the first feed engine in the engines array by default
      $feedSelect.find("option[value=-1]").show(); // enable create new feed
      $feedSelect.find("option[value=-1]").prop('disabled', false);  //for IE show
    } else {
      $feedSelect.find("option[value=-1]").hide(); // disable create new feed as we have no supported engines for this proccess
      $feedSelect.find("option[value=-1]").prop('disabled', true);  //for IE hide (grayed out)
      for (f in this.feedlist) {
        if (datatype == 0 || (this.feedlist[f].datatype == datatype)) {  // Only feeds of the supported datatype
          var exists = false;
          $feedSelect.find('option').each(function(){
            if (this.value == $feedSelect.val()) {
              exists = true;
              return false;
            }
          });
          if (!exists) {
            if($feedSelect.val()!=this.feedlist[f].id){
              $feedSelect.val(this.feedlist[f].id); // select first feed
            }
          }
          break;
        }
      }
    }

    $('#processlist-ui #feed-select').change();  // refresh feed select
  },

  'modified':function(){
    $("#save-processlist").attr('class','btn btn-warning').text(_Tr("Changed, press to save"));
    $(".feedaccesslabel").attr("href","#"); // disable access to feeds
  },

  'saved':function(t){
    $("#save-processlist").attr('class','btn btn-success').text(_Tr("Saved"));
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
        //hide sendEmail and Publish to MQTT from virtual feeds
        if (processlist_ui.contexttype == 1 && (
          processlist_ui.processlist[z]['feedwrite'] == true ||
          processlist_ui.processlist[z][2] == "sendEmail" || 
          processlist_ui.processlist[z][2] == "publish_to_mqtt"))
        {
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
    $("#save-processlist").attr('class','btn btn-success').text(_Tr("Not modified"));
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

// takes plain object with key / value pairs. 
// if found swaps placeholder for variable
// can handle 2 deep nested objects
if (!String.prototype.format) {
  String.prototype.format = function(data) {   
    return this.replace(/{([\w\.-]+)}/g, function(match, placeholder) {
      if (placeholder.indexOf('.') > -1){
        p = placeholder.split('.')
        return typeof data[p[0]] != 'undefined' ? data[p[0]][p[1]] : match
      } else {
        return typeof data[placeholder] != 'undefined' ? data[placeholder] : match
      }
    });
  };
}

/**
 * pre select processes dropdown
 * @param {ClickEvent} event 
 */
function selectProcess(event){
  event.preventDefault();
  processid = event.target.dataset.processid
  select = document.getElementById('process-select')
  select.focus()
  select.value = processid
  select.blur()
  $(select).trigger('change');// trigger jquery event
}
