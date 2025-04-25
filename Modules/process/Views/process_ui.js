 //---------------------------------------------------------------------
 // Version 11.8 - Nuno Chaveiro nchaveiro(at)gmail.com 04/2025
 //---------------------------------------------------------------------
var ProcessArg = {
  VALUE:0,
  INPUTID:1,
  FEEDID:2,
  NONE:3,
  TEXT:4,
  SCHEDULEID:5,
  MULTI:6
}

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
  
  table: typeof table !== "undefined" ? table : null,

  'draw':function(){
    var i = 0;
    var out="";
    
    // console.log("contextid=" + this.contextid);
    // console.log("contextprocesslist=" + this.contextprocesslist);
    
    if (this.contextprocesslist.length==0) {
      $("#process-table").hide();
      $("#select-all-lines").hide();
      $(".process-cut, .process-copy, .process-delete").hide();
      $("#noprocess").show();
    } else {
      $("#process-table").show();
      $("#select-all-lines").show();
      $(".process-cut, .process-copy, .process-delete").show();
      $("#noprocess").hide();
      for (z in this.contextprocesslist) {
        out += '<tr>';
        out += '<td><div class="select text-center"><input class="process-select" type="checkbox" processid='+i+'></div></td>';
        
        // Process name and argument
        var processkey = this.contextprocesslist[z][0];
        var arg = {};
        var lastvalue = "";
        var processname = "";
        processkey = this.getProcessKeyById(processkey); // convert id numbers to key names (backward compatible)
        
        if (this.processlist[processkey] != undefined) {
          var procneedredis = (this.has_redis == 0 && this.processlist[processkey]['requireredis'] != undefined && this.processlist[processkey]['requireredis'] == true ? 1 : 0);
          if (this.processlist[processkey]['internalerror'] !== undefined && this.processlist[processkey]['internalerror'] == true) {
            arg.text = this.processlist[processkey]['internalerror_desc']
            // arg += "<span class='label label-important' title='Value'>" + this.processlist[processkey]['internalerror_desc'] + "</span>";
            processname = "<span class='label label-important' title='Value'>" + this.processlist[processkey][0] + "</span>";
          }  
          else if (procneedredis) {
            // arg += "<span class='label label-important' title='Value'>Process ´"+processkey+"´ not available. Redis not installed.</span>";
            arg.text = "Process '"+processkey+"' not available. Redis not installed."
            processname = this.processlist[processkey].name;
          }
          else {
            // Check ProcessArg Type
            switch(this.processlist[processkey].argtype) {
              case ProcessArg.MULTI:
                arg.text = this.convertb64args(this.contextprocesslist[z][1])
                arg.title = _Tr("Arguments")
                arg.icon = 'icon-cog'
              break;

              case ProcessArg.VALUE:
                arg.text = this.contextprocesslist[z][1]
                arg.title = _Tr("Value")
                arg.icon = 'icon-edit'
              break;
              
              case ProcessArg.INPUTID:
              var inpid = this.contextprocesslist[z][1];
              if (this.inputlist[inpid]!=undefined) {
                arg.text = "Node "+this.inputlist[inpid].nodeid+":"+this.inputlist[inpid].name+' ' + (this.inputlist[inpid].description || '')
                arg.title = _Tr("Input")+" "+inpid
                arg.icon = 'icon-signal'

                lastvalue = (this.inputlist[inpid].value*1).toFixed(2);
              } else {
                arg.text = 'Input "+schid+" does not exists or was deleted'
              }
              break;
              
              case ProcessArg.FEEDID:
              var feedid = this.contextprocesslist[z][1];
              if (this.feedlist[feedid]!=undefined) {
                arg.text = (this.feedlist[feedid].tag || '') + ': '+this.feedlist[feedid].name
                arg.title = _Tr("Feed")+" "+feedid
                arg.icon = 'icon-list-alt'
                var feedviewpath = "graph/";
                if (_SETTINGS && _SETTINGS.hasOwnProperty('feedviewpath') && _SETTINGS.feedviewpath !== "") {
                    var feedviewpath = _SETTINGS.feedviewpath;
                }
                arg.href = [path, feedviewpath, feedid].join("");
                lastvalue = (this.feedlist[feedid].value*1).toFixed(2);
              } else {
                arg.text = 'Feedid "+feedid+" does not exists or was deleted'
              }
              break;

              case ProcessArg.TEXT:
                arg.title = _Tr("Text")
                arg.text = this.contextprocesslist[z][1]
                arg.icon = 'icon-edit'
              break;
              
              case ProcessArg.SCHEDULEID:
              var schid = this.contextprocesslist[z][1];
              if (this.schedulelist[schid]!=undefined) {
                arg.title = _Tr("Schedule")+" "+schid
                arg.text = this.schedulelist[schid].name
                arg.icon = 'icon-time'

              } else {
                arg.text = "Schedule "+schid+" does not exists or was deleted"
              }
              break;
            }
            processname = this.processlist[processkey].name;
          }
        }
        else {
          processname = "UNSUPPORTED";
          arg.text = "Process ´"+processkey+"´ not available. Module missing?"
        }

        // create the badge markup to display the process argument detail
        label = ""
        if(arg.text){
          label += arg.href ? '<a href="'+arg.href+'" class="text-info"' : '<span class="muted"'
          label += ' title="'+arg.title+'"'
          label += ">"
          label += arg.icon ? '<i class="'+arg.icon+'"></i> ' : ''
          label += arg.text || arg.title
          label += arg.href ? '</a>':'</span>'
        }

        try {
            tag = `<span title="${this.processlist[processkey].description.replace(/<(?:.|\n)*?>/gm, '')}" 
            style="cursor:help" 
            class="fw-label overflow-hidden label ${this.argtypes[this.processlist[processkey].argtype].cssClass}">${this.processlist[processkey].short.replace(/>/g, "&gt;").replace(/</g, "&lt;")}</span>`
        } catch (e) {
            tag = ""
        }

        try {
            latest = lastvalue ? `<small title="Last recorded ${this.processlist[processkey].argtype} value" class="muted">(${lastvalue})</small>` : ''
        } catch (e) {
            latest = ""
        }
        

        out += '<td style="text-align:right;">'+(i+1)+'</td><td>'+processname+'</td><td style="text-align:right">'+tag+'</td><td>'+label+'</td><td>'+latest+'</td>';
     
        out += '<td style="white-space:nowrap;">';
        // Move process up or down
        if (i < this.contextprocesslist.length-1) {
          out += "<a class='move-process' title='"+_Tr("Move down")+"' processid="+i+" moveby=1 ><i class='icon-arrow-down' style='cursor:pointer'></i></a>";
        } else {
          out += "<span style='display: inline-block; width:14px; ' />";
        }
        if (i > 0) {
          out += '<a class="move-process" title="'+_Tr("Move up")+'" processid='+i+' moveby=-1 ><i class="icon-arrow-up" style="cursor:pointer"></i></a>';
        }
        out += '</td>';
        
        // Delete process button (icon)
        out += '<td><a class="edit-process" title="'+_Tr("Edit")+'" processid='+i+'><i class="icon-pencil" style="cursor:pointer"></i></a></td>';
        out += '<td><a class="delete-process" title="'+_Tr("Delete")+'" processid='+i+'><i class="icon-trash" style="cursor:pointer"></i></a></td>';
        out += '</tr>';
        
        i++; // process id
      }
    }
    $('#process-table-elements').html(out);

    $("#select-all-lines").data("state", false).trigger("click");
    $(".process-cut, .process-copy, .process-past, .process-delete").prop("disabled", true);
  },
  
  'convertb64args':function(inbase64){
    mout = ""
    try {
        mjsonStr = atob(inbase64);
        mjson = JSON.parse(mjsonStr);
        mout = Object.entries(mjson)
            .map(([key, val]) => `${key}=${val}`)
            .join(', ');
    } catch (error) {
        mout = _Tr("Invalid base64 or JSON format")
        console.error(mout);
    }
    return mout
  },

  'drawpreview':function(processlist,input){
    if (!processlist) return "";
    var localprocesslist = processlist_ui.decode(processlist);
    if (localprocesslist.length==0) {
      return ""
    } else {
      var out = [];
      // create coloured link or span for each process 
      for(b of this.getBadges(processlist,input)){
        let markup = []
        markup.push(b.href ? '<a target="_blank" href="'+b.href+'"' : '<span')
        markup.push(' class="label '+b.cssClass+'" title="'+b.title+'">')
        markup.push((b.text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'))
        markup.push(b.href ? '</a> ' : '</span> ')
        out.push(markup.join(''));
      }
      return out.join('');
    }
  },

  /**
   * return array of objects with id,id_num properties
   */
  'backward_compatible_list': function(){
    if(!this.processlist) return
    let pl = this.processlist
    let ids = [];
    Object.keys(pl).forEach(function(key) {
      ids.push({id:key,id_num: pl[key].id_num})
    });
    return ids
  },

  /**
   * return process "name" when given a valid id (if id not number original input returned)
   */
  'getProcessKeyById': function(id){
    id_int = parseInt(id)
    if (isNaN(id_int)) return id
    old_ids = this.backward_compatible_list()
    // add numeric and textual ids (backward compatible)
    for (id2 in old_ids) {
      if (old_ids[id2].id_num === id_int){
        return old_ids[id2].id
      }
    }
  },

  'argtypes': {
      0: {cssClass: 'label-important',  title: 'Value: {longText} - {value}'},
      1: {cssClass: 'label-warning',    title: 'Input: {longText} - ({input.nodeid}:{input.name}) {input.description}'},
      2: {cssClass: 'label-info',       title: 'Feed: {longText} - ({feed.tag}:{feed.name})  [{feed.id}]'},
      3: {cssClass: 'label-important',  title: 'Text: {longText} - {value}'},
      4: {cssClass: 'label-info',       title: 'Topic: {longText} - {value}'},
      5: {cssClass: 'label-warning',    title: 'Schedule: {longText} - {schedule.name}'},
      6: {cssClass: 'label-important',  title: 'Multi arguments: {longText} - {value}'},
  },

  'getBadges': function (processlist,input) {
    if (!processlist) return ""
    var processPairs = processlist.split(",")
    // create empty list of badges
    let badges = []
    for (z in processPairs)
    {
      // add badge to list or add a blank one if there are any issues.
      let badge = {}
      var keyvalue = processPairs[z].split(":")
      var key = parseInt(keyvalue[0])
      key = isNaN(key) ? keyvalue[0]: this.getProcessKeyById(key);
      badge.value = keyvalue[1]

      badge.process = this.processlist.hasOwnProperty(key) ? this.processlist[key] : false

      if(this.init_done === 0 && badge.process!==false){
        if (badge.process.argtype == ProcessArg.MULTI) {
          badge.value = this.convertb64args(keyvalue[1])
        }
        // set badge properties
        badge.type = this.argtypes[badge.process.argtype]
        badge.typeName = badge.type.name
        badge.cssClass = badge.type.cssClass
        var feedviewpath = "graph/";
        if (_SETTINGS && _SETTINGS.hasOwnProperty('feedviewpath') && _SETTINGS.feedviewpath !== "") {
            var feedviewpath = _SETTINGS.feedviewpath;
        }
        badge.href = badge.process.argtype == ProcessArg.FEEDID ? [path, feedviewpath, badge.value].join("") : false;
        badge.text = badge.process.short || ''
        badge.longText = badge.process.name
        badge.input = input
        badge.feed =  this.feedlist[badge.value] || {}
        badge.schedule = this.schedulelist[badge.value] || {}
        badge.title = badge.type.title.format(badge);
        // pass the collected badge object as values for the title string template
        badges.push(badge);
      } else if(this.init_done === 0 && this.has_redis == 0 && badge.process['requireredis'] !== undefined && badge.process['requireredis'] == true ? 1 : 0){
        // no redis
        badges.push({
          text: badge.process['internalerror_reason'],
          title: badge.process['internalerror_desc'],
          cssClass: 'badge-important',
          href: false
        })
      } else if(this.init_done === 0 && !badge.value){
        // input,feed or schedule doesnt exist
        badges.push({
          title: '{typeName} {value} does not exist or was deleted'.format(badge),
          text: 'ERROR',
          cssClass: 'badge-important',
          href: false
        })
      } else if(this.init_done === 0 && !badge.process){
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
          text: ' 🕒… ',
          title: '',
          cssClass: 'muted',
          href: false
        })
      }
    }
    // console.log(badges)
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
          var processkey = parseInt(localprocesslist[z][0]);
          processkey = isNaN(processkey) ? localprocesslist[z][0]: this.getProcessKeyById(processkey);
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
              switch(this.processlist[processkey].argtype) {
                case ProcessArg.INPUTID:
                var inpid = localprocesslist[z][1];
                if (this.inputlist[value]==undefined) {
                  out +=  "<span class='badge badge-important' title='Input "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;

                case ProcessArg.FEEDID:
                if (this.feedlist[value]==undefined) {
                  out +=  "<span class='badge badge-important' title='Feedid "+value+" does not exists or was deleted'>ERROR</span> "
                }
                break;

                case ProcessArg.SCHEDULEID:
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
      if (engine==6 || engine==5 || engine==4 || engine==1) {
          $("#feed-interval").show();
      }
      else {
          $("#feed-interval").hide();
      }
      if (engine==8 || engine==0) {
          $("#feed-table").empty().show();
      }
      else {
          $("#feed-table").hide();
      }
    });

    $('#processlist-ui #process-add, #processlist-ui #process-edit').click(function(){
      var processid = $('#process-select').val();
      var process = processlist_ui.processlist[processid];
      var arg = '';

      // Check ProcessArg Type
      switch(process.argtype) {
        case ProcessArg.VALUE:
          arg = $("#value-input").val();
          arg = parseFloat(arg.replace(",", "."));
          if (isNaN(arg)) {
            alert('ERROR: Value must be a valid number');
            return false;
          }
          break;

        case ProcessArg.INPUTID:
          arg = $("#input-select").val();
          break;

        case ProcessArg.FEEDID:
          var feedid = $("#feed-select").val();

          if (feedid==-1) {
            var feedname = $('#new-feed-name').val();
            var feedtag = $('#new-feed-tag').val();
            var engine = $('#feed-engine').val();
            
            var options = {};
            if (engine==6 || engine==5 || engine==4 || engine==1) {
              let interval = $('#feed-interval').val();
              if (interval=="") {
                alert('ERROR: Please select a feed interval');
                return false;
              }
              options = {"interval":interval};
            }
            else if (engine==8 || engine==0) {
              options = {"name":$('#feed-table').val()};
            }
            
            if (feedname == '') {
              alert('ERROR: Please enter a feed name');
              return false;
            }
            
            var unit = '';
            if (process.unit!=undefined) unit = process.unit;
            
            var result = feed.create(feedtag,feedname,engine,options,unit);
            feedid = result.feedid;

            if (!result.success || feedid<1) {
              alert('ERROR: Feed could not be created, '+result.message);
              return false;
            }

            processlist_ui.feedlist[feedid] = {'id':feedid, 'name':feedname,'value':'n/a','tag':feedtag};
            processlist_ui.showfeedoptions(processid);  // Refresh Feedlist
          }
          arg = feedid;
          break;

        case ProcessArg.NONE:
          arg = 0;
          break;

        case ProcessArg.TEXT:
          arg = $("#text-input").val();
          break;

        case ProcessArg.SCHEDULEID:
          arg = $("#schedule-select").val();
          break;
          
        case ProcessArg.MULTI:
            if (Array.isArray(processlist_ui.processlist[processid]['argmulti'])) {
                const $multiArgs = $('.argmulti');
                if ($multiArgs.length === 0) {
                    alert('ERROR: Multi process but no argmulti elements found');
                    break;
                }

                processlist_ui.processlist[processid]['argmulti'].forEach((marg) => {
                    let found = false;
                    $multiArgs.each(function() {
                        const key = $(this).data('key');
                        value = $(this).val();
                        if (key === marg.key) {
                            found = true;
                            if (marg.argmtype === ProcessArg.VALUE) { 
                                value = parseFloat(value.replace(",", "."));
                                if (isNaN(value)) { 
                                    alert(`ERROR: Value for key ${key} must be a valid number`);
                                    return false;
                                }
                                $(this).val(value);; // fixed value to form
                            }
                            return false; // break each
                        }
                    });
                    if (!found) {
                        alert(`ERROR: Key ${marg.key} not found in argmulti elements`);
                    }
                });

                let args = {};
                $multiArgs.each(function() {
                    const key = $(this).data('key');
                    const value = $(this).val();
                    args[key] = value;
                });

                const json = JSON.stringify(args);
                arg = btoa(json); // Encode to Base64 to avoid character conflicts
            }
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
      $("#type-multi").hide();
      $("#type-multi-args").html("");

      // Check ProcessArg Type
      if (processid) {
        switch(processlist_ui.processlist[processid].argtype) {
          case ProcessArg.VALUE:
            $("#type-value").show();
            break;
          case ProcessArg.INPUTID:
            $("#type-input").show();
            break;
          case ProcessArg.FEEDID:
            $("#type-feed").show();
            processlist_ui.showfeedoptions(processid);
            break;
          case ProcessArg.TEXT:
            $("#type-text").show();
            break;
          case ProcessArg.SCHEDULEID:
            $("#type-schedule").show();
            break;
          case ProcessArg.MULTI:
              if (Array.isArray(processlist_ui.processlist[processid]['argmulti'])) {
                  processlist_ui.processlist[processid]['argmulti'].forEach((arg) => {
                      $('#type-multi-args').append(processlist_ui.createMultiArgInput(arg));
                  });
              }
              $("#type-multi").show();
              break;
        }

        $("#description").html("<p><strong>" + processlist_ui.processlist[processid]['name'] + "</strong></p>");

        if (processlist_ui.processlist[processid]['description'] === undefined || processlist_ui.processlist[processid]['description'] == "") {
          $("#description").append("<p><b style='color: orange'>No process description available for process '"+processlist_ui.processlist[processid][0]+"' with id '"+processid+"'.<br>Add a description to Module\\<i>module_name</i>\\<i>module_name</i>_processlist.php in process_list() function, $list[] array at the 'desc' key.</b><br>Please <a target='_blank' href='https://github.com/emoncms/emoncms/issues/new'>click here</a> and paste the text above to ask a developer to include a process description.</b></p>");
        } else {
          $("#description").append("<p>" + processlist_ui.processlist[processid]['description'] + "</p>");
          if (processlist_ui.processlist[processid].argtype == ProcessArg.MULTI) {
              if (Array.isArray(processlist_ui.processlist[processid]['argmulti']) && processlist_ui.processlist[processid]['argmulti'].length > 0) {
                  $("#description").append("<p><b>" + _Tr("Process arguments:") + "</b></p>");
                  $("#description").append("<ul id='desc-multi-args'></ul>");
                  processlist_ui.processlist[processid]['argmulti'].forEach((arg) => {
                      $("#desc-multi-args").append(
                          '<li>' +
                              '<span style="font-weight: bold; display: inline-block; min-width: 100px;">' + arg.name + '&nbsp</span>' +
                              '<span style="display: inline-block;">' + (arg.desc ? arg.desc : '') + (arg.default ? ' (Default: ' + arg.default + ')' : '') + '</span>' +
                          '</li>'
                      );
                  });
              }
          }
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
        $("#new-feed-tag").show();
        $('#feed-select').css({'border-radius': 0, 'border-right': 0})
        
        $("#processlist-ui #feed-engine").change(); // select available interval for engine
        // If there's only one feed engine to choose from then dont show feed engine selector
        // CHAVEIRO: Commented for now so user can see what processor it's using.
        //var processid = $('#process-select').val();
        //var engines = processlist_ui.processlist[processid][6];   // 0:MYSQL, 5:PHPFINA
        //if (engines.length > 1) 
        $("#feed-engine, .feed-engine-label").show();
    } else {
        $("#new-feed-name").hide();
        $("#new-feed-tag").hide();
        $('#feed-select').css({'border-radius': 4, 'border-right': 4})
        $("#feed-interval").hide();
        $("#feed-table").hide();
        $("#feed-engine, .feed-engine-label").hide(); 
      }
      if (typeof nodes_display !== 'undefined') {
          autocomplete(document.getElementById("new-feed-tag"), Object.keys(nodes_display));
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
      var processid = processlist_ui.getProcessKeyById(process[0]); // get process id name (backward compatible)
      var processval = process[1];
      var curpos = parseInt($(this).attr('processid'));
      
      $("#process-header-add").hide();
      $("#process-header-edit").show();
      $("#type-btn-add").hide();
      $("#type-btn-edit").show();
      $("#type-btn-edit").attr('curpos', curpos);

      if (processlist_ui.processlist[processid] == undefined) {
        if (processlist_ui.contexttype == 0) {
          $("#process-select").val($("#process-select option").first().val()); // default process for input context
        } else {
          $("#process-select").val('process__source_feed_data_time'); // default process for feed context
        }
        $("#processlist-ui #process-select").change();  // Force a refresh
      } else {
        $("#process-select").val( processlist_ui.processlist[processid]['id']);
        $("#processlist-ui #process-select").change(); // Force a refresh
        // Check ProcessArg Type
        switch(processlist_ui.processlist[processid].argtype) {
          case ProcessArg.VALUE:
            $("#value-input").val(processval);
            break;
          case ProcessArg.INPUTID:
            $("#input-select").val(processval);
            break;
          case ProcessArg.FEEDID:
            $("#feed-select").val(processval);
            $('#processlist-ui #feed-select').change();  // refresh feed select
            break;
          case ProcessArg.TEXT:
            $("#text-input").val(processval);
            break;
          case ProcessArg.SCHEDULEID:
            $("#schedule-select").val(processval);
            break;
          case ProcessArg.MULTI:
            mjson = [];
            try {
                const mjsonStr = atob(processval);
                mjson = JSON.parse(mjsonStr);
            } catch (error) {
                alert("Invalid base64 or JSON format")
            }
            const $multiArgs = $('.argmulti');
            if ($multiArgs.length === 0) {
                alert('ERROR: Multi process but no argmulti elements found');
            } else {
                $multiArgs.each(function() {
                    const key = $(this).data('key');
                    for (const arg in mjson) {
                        const value = mjson[arg];
                        if (key === arg) {
                            $(this).val(value);
                            break;
                        }
                    }
                });
            }
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

    $("#select-all-lines").on("click", function () {
        const $this = $(this);
        const checkboxes = $("#process-table-elements").find(".process-select");
        if (!$this.data("originalTitle")) {
            $this.data("originalTitle", $this.attr("title"));
        }
        const state = $this.data('state') !== false;
        checkboxes.prop("checked", state);
        checkboxes.trigger("change");
        $this.find('.icon').toggleClass('icon-ban-circle', state);
        $this.find('.icon').toggleClass('icon-check', !state);
        const title = state ? $this.data('alt-title') : $this.data('originalTitle');
        $this.attr('title', title);
        $this.data('state', !state);
    });

    // Support keyboard shortcuts
    $(document).on("keydown", function (e) {
        if ($("#processlistModal").is(":visible")) { // Ensure modal is visible
            if (e.ctrlKey) { 
                switch (e.key) {
                    case "c":
                        $(".process-copy").trigger("click");
                        e.preventDefault();
                        break;
                    case "v":
                        $(".process-paste").trigger("click");
                        e.preventDefault();
                        break;
                    case "x":
                        $(".process-cut").trigger("click");
                        e.preventDefault();
                        break;
                }
            } else if (e.key === "Delete") {
                $(".process-delete").trigger("click");
                e.preventDefault();
            }
        }
    });

    $(".process-copy").on("click", function () {
        const copiedProcessIds = $(".process-select:checked").map(function () {
            return $(this).attr("processid");
        }).get();

        if (copiedProcessIds.length > 0) {
           let contextprocesslist = JSON.parse(JSON.stringify(processlist_ui.contextprocesslist)); // clone

           const clipboardText = JSON.stringify(
                copiedProcessIds.map(processId => {
                    let process = contextprocesslist[processId];
                    let process_name = processlist_ui.getProcessKeyById(process[0]);

                    // Handle Base64 decoding if argtype is MULTI
                    if (processlist_ui.processlist[process_name].argtype === ProcessArg.MULTI) {
                        try {
                            process[1] = JSON.parse(atob(process[1]));
                        } catch (error) {
                            console.error("Failed to decode and parse Base64 data:", error);
                        }
                    }
                    return process;
                })
            );

            // Copy to external clipboard
            navigator.clipboard.writeText(clipboardText).then(() => {
                //alert("Copied processes to clipboard:\n" + clipboardText);
            }).catch((error) => {
                console.error("Failed to copy to clipboard:", error);
                alert("Failed to copy processes to clipboard." + error);
            });
        } else {
            alert("No processes selected to copy.");
        }
    });

    $(".process-paste").on("click", function () {
        navigator.clipboard.readText().then((clipboardText) => {
            try {
                const pastedProcesses = JSON.parse(clipboardText);
                if (!Array.isArray(pastedProcesses)) {
                    throw new Error("Clipboard data is not a valid array");
                }

                pastedProcesses.forEach(process => {
                    let process_name = processlist_ui.getProcessKeyById(process[0]);

                    // Handle Base64 encoding for MULTI argtype
                    if (processlist_ui.processlist[process_name].argtype === ProcessArg.MULTI) {
                        try {
                            process[1] = btoa(JSON.stringify(process[1])); // Convert back to Base64
                        } catch (error) {
                            console.error("Failed to encode data to Base64:", error);
                        }
                    }

                    // Add the processed entry back to contextprocesslist
                    processlist_ui.contextprocesslist.push(process);
                });

                processlist_ui.draw();
                processlist_ui.modified();
            } catch (error) {
                alert("Failed to paste processes. The clipboard data is not in the correct format.");
                console.error("Error parsing clipboard data:", error);
            }
        }).catch((error) => {
            console.error("Failed to read data from the clipboard:", error);
            alert("Failed to read data from the clipboard." + error);
        });
    });

    $(".process-cut").on("click", function () {
        $(".process-copy").trigger("click"); 
        $(".process-delete").trigger("click");
    });
    
    $(".process-delete").on("click", function () {
        const deletedProcessIds = $(".process-select:checked").map(function () {
            return $(this).attr("processid");
        }).get();
        let changesMade = false;

        deletedProcessIds.forEach((processId) => {
            if (processId in processlist_ui.contextprocesslist) {
                delete processlist_ui.contextprocesslist[processId];
                changesMade = true;
            }
        });

        if (changesMade) {
            processlist_ui.contextprocesslist = Object.values(processlist_ui.contextprocesslist); // reindex list due to removal of indexs
            processlist_ui.draw();
            processlist_ui.modified();
        }
    });

    $('#processlist-ui .table').on("change", ".process-select", function () {
        const anyChecked = $(".process-select:checked").length > 0;

        if (!anyChecked) {
            $(".process-cut, .process-copy, .process-delete").prop("disabled", true);
        } else {
            $(".process-cut, .process-copy, .process-delete").prop("disabled", false);
        }
    });

  },

  'createMultiArgInput':function(arg) {
    let $input;
    switch (arg.argmtype) {
        case ProcessArg.VALUE:
            $input = $('<input type="text">').addClass('argmulti').attr('data-key', arg.key);
            if (arg.default !== undefined) {
                arg.default = parseFloat((""+arg.default).replace(",", "."));
                if (isNaN(arg.default)) {
                    alert("ERROR: Default value for '" + arg.key + "' is not a valid number.");
                    break;
                }
              $input.val(arg.default); // Set the default value
            }
            break;
        case ProcessArg.INPUTID:
            $input = $('<select>').attr('id', 'input-select-'+arg.key).addClass('argmulti').attr('data-key', arg.key).html(processlist_ui.fillinput);
            break;
        case ProcessArg.FEEDID:
            $input = $('<select>').attr('id', 'input-select-'+arg.key).addClass('argmulti').attr('data-key', arg.key).html(processlist_ui.fillfeed);
            break;
        case ProcessArg.SCHEDULEID:
            $input = $('<select>').attr('id', 'schedule-select-'+arg.key).addClass('argmulti').attr('data-key', arg.key).html(processlist_ui.fillschedule);
            break;
        case ProcessArg.TEXT:
            $input = $('<input type="text">').addClass('argmulti').attr('data-key', arg.key);
            if (arg.default !== undefined) $input.val(arg.default); // Set the default value
            break;
        default:
            console.log('Unsupported arg type: ' + argmtype);
            return;
    }
    const desc = (arg.desc ? arg.desc.replace(/<(?:.|\n)*?>/gm, '') : '') + (arg.default ? ' (Default: ' + arg.default + ')' : '');
    $field = $('<div class="form-element">').append($('<label>').text(arg.name).attr('for', arg.key).attr('title', desc).attr('style', 'cursor:help')).append($input);
    return $field
  },

  'showfeedoptions':function(processid){
    $feedSelect = $('#feed-select');
    $feedEngineSelect = $('#feed-engine');
    $feedTypeSelect = $('#feed-data-type');
    var prc = this.processlist[processid].function;     // process function
    var feedwrite = this.processlist[processid].feedwrite; // process writes to feed
    var engines = this.processlist[processid].engines;   // 0:MYSQL, 5:PHPFINA

    out = this.fillfeed();
    out = "<option value=-1>CREATE NEW:</option>" + out;

    // overwrite feed list
    var lastval = $feedSelect.val();
    if (lastval==null) lastval = -1;
    $feedSelect.data('value',lastval);// store previous value before <select> changes
    $feedSelect.html(out);
    // recall the old value if available
    if($feedSelect.data('value')!=""){
      $feedSelect.val($feedSelect.data('value'));
      $feedSelect.data('value','');    
    }
    $feedTypeSelect.find("option").hide();  // Start by hiding all feed engine options
    $feedTypeSelect.find("option").prop('disabled', true);  //for IE hide (grayed out)

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

    $('#processlist-ui #feed-select').change();  // refresh feed select
  },

  'modified':function(){
    $("#save-processlist").attr('class','btn btn-warning').text(_Tr("Changed, press to save"));
    $(".feedaccesslabel").attr("href","#"); // disable access to feeds
  },

  'saved':function(feeds){
    $("#save-processlist").attr('class','btn btn-success').text("Saved");

    // compatibility input vs device view transpose
    if (feeds.data!=undefined) feeds = feeds.data;

    for (z in feeds) {
      if (feeds.hasOwnProperty(z) && (feeds[z].id == processlist_ui.contextid)) {
        feeds[z].processList = processlist_ui.encode(processlist_ui.contextprocesslist);
      }
    }
    if (window.table!=undefined && window.table.draw!=undefined) {
        table.draw();
    }
    if (typeof update == 'function') update()
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
    for (z in array) {
      parts.push(array[z][0]+":"+array[z][1]);
    }
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
        if (result[p]['engines']!=undefined) { // processor has supported engines?
          result[p]['feedwrite']=true; // If has an engine so assume process writes to feed 
          if (processlist_ui.engines_hidden.length > 0) {
            for (var e=result[p]['engines'].length-1; e > -1; e--) {  // for each processor engine
              for (h in processlist_ui.engines_hidden) {
                if (result[p]['engines'][e]==processlist_ui.engines_hidden[h]) { // if engine is to be hidden
                  result[p]['engines'].splice(e, 1);     // remove engine from processor
                }
              }
            }
          }
          if (result[p]['engines'].length == 0) {
            result[p]['engines'] = undefined;  // if processor now has no engines, undefine its array
          }
        }
      }

      processlist_ui.processlist = result;
      var processgroups = [];
      for (z in processlist_ui.processlist) {
        var group = processlist_ui.processlist[z]['group'];
        
        // hide the following from virtual feeds
        if (processlist_ui.contexttype == 1) {
          if (processlist_ui.processlist[z]['feedwrite'] == true) continue;
          if (processlist_ui.processlist[z]['function'] == "sendEmail") continue;
          if (processlist_ui.processlist[z]['function'] == "publish_to_mqtt") continue;
          if (group=="Feed" || group=="Input") continue;
        }

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
          out += "<option "+procdisabled+" value="+processgroups[pg][p]['id']+">"+processgroups[pg][p].name+procneedredis+"</option>";
        }
        out += "</optgroup>";
      }
      $("#process-select").html(out);
      processlist_ui.initprogress();
      // Automatic call of feed table update
      if (typeof window.update_feed_list == 'function') update_feed_list();
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
      $("#schedule-select").html(processlist_ui.fillschedule());
      processlist_ui.initprogress();
    }});

    // Input Select List  
    $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(result) {
      var inputs = {};
      for (z in result) inputs[result[z].id] = result[z];
      processlist_ui.inputlist = inputs;
      $('#input-select').html(processlist_ui.fillinput());
      processlist_ui.initprogress();
    }});

    processlist_ui.events();
  },

  'fillschedule':function(){
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
      return out;
  },
  
  'fillinput':function(){
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
      return out;
  },

  'fillfeed':function(){
    var feedgroups = [];
    for (z in processlist_ui.feedlist) {
        if (parseInt(processlist_ui.feedlist[z].engine) == 7) { //input context and virtual feed and process writes to feed ?
            continue; // Dont list virtual feed
        }
        var group = (processlist_ui.feedlist[z].tag === null ? "NoGroup" : processlist_ui.feedlist[z].tag);
        if (group!="Deleted") {
            if (!feedgroups[group]) feedgroups[group] = []
            feedgroups[group].push(processlist_ui.feedlist[z]);
        }
    }
    var out = "";
    for (z in feedgroups) {
      out += "<optgroup label='"+z+"'>";
      for (p in feedgroups[z]) {
          out += "<option value="+feedgroups[z][p]['id']+">"+feedgroups[z][p].name+"</option>";
      }
      out += "</optgroup>";
    }
    return out;
  },

  'initprogress':function(){
    processlist_ui.init_done--;
    if (processlist_ui.init_done == 0) {
      processlist_ui.draw();
      if (window.table!=undefined && window.table.draw!=undefined) table.draw();

      if (processlist_ui.contexttype == 0) {
        $("#process-select").val(this.getProcessKeyById(1)); // default process for input context
      } else {
        $("#process-select").val(this.getProcessKeyById(53)); // default process for feed context
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
    $("#processlistModal").modal('show');
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
