 //---------------------------------------------------------------------
 // Version 11.8 - Nuno Chaveiro nchaveiro(at)gmail.com 04/2025
 //---------------------------------------------------------------------

var vue_args = false; // Vue.js args, used in processlist_ui.vue.js

var ProcessArg = {
  VALUE:0,
  INPUTID:1,
  FEEDID:2,
  NONE:3,
  TEXT:4,
  SCHEDULEID:5
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

  newfeedname: "",
  newfeedtag: "",

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
        var args = [];
        var lastvalue = "";
        var processname = "";
        processkey = this.getProcessKeyById(processkey); // convert id numbers to key names (backward compatible)
        
        if (this.processlist[processkey] != undefined) {
          var procneedredis = (this.has_redis == 0 && this.processlist[processkey]['requireredis'] != undefined && this.processlist[processkey]['requireredis'] == true ? 1 : 0);
          if (this.processlist[processkey]['internalerror'] !== undefined && this.processlist[processkey]['internalerror'] == true) {
            args.push({text:this.processlist[processkey]['internalerror_desc']});
            // arg += "<span class='label label-important' title='Value'>" + this.processlist[processkey]['internalerror_desc'] + "</span>";
            processname = "<span class='label label-important' title='Value'>" + this.processlist[processkey][0] + "</span>";
          }  
          else if (procneedredis) {
            // arg += "<span class='label label-important' title='Value'>Process ´"+processkey+"´ not available. Redis not installed.</span>";
            args.push({text:"Process '"+processkey+"' not available. Redis not installed."});
            processname = this.processlist[processkey].name;
          }
          else {

            if (this.processlist[processkey].args !== undefined && Array.isArray(this.processlist[processkey].args)) {
              for (let i = 0; i < this.processlist[processkey].args.length; i++) {
                args.push({
                  type: this.processlist[processkey].args[i].type
                })
              }
            } else if (this.processlist[processkey].argtype !== undefined) {
              // If argtype is defined, create a single argument object
              args.push({
                type: this.processlist[processkey].argtype,
              });
            }

            for (let i = 0; i < args.length; i++) {
              let argtype = args[i].type;

              let text = "";
              let title = "";
              let icon = "";
              let href = "";

              // Check ProcessArg Type
              switch(argtype) {
                
                case ProcessArg.VALUE:
                  text = this.contextprocesslist[z][i+1]
                  title = _Tr("Value")
                  icon = 'icon-edit'
                break;
                
                case ProcessArg.INPUTID:
                var inpid = this.contextprocesslist[z][i+1];
                if (this.inputlist[inpid]!=undefined) {
                  text = "Node "+this.inputlist[inpid].nodeid+":"+this.inputlist[inpid].name+' ' + (this.inputlist[inpid].description || '')
                  title = _Tr("Input")+" "+inpid
                  icon = 'icon-signal'

                  lastvalue = (this.inputlist[inpid].value*1).toFixed(2);
                } else {
                  text = 'Input "+schid+" does not exists or was deleted'
                }
                break;
                
                case ProcessArg.FEEDID:
                var feedid = this.contextprocesslist[z][i+1];
                if (this.feedlist[feedid]!=undefined) {
                  text = (this.feedlist[feedid].tag || '') + ': '+this.feedlist[feedid].name
                  title = _Tr("Feed")+" "+feedid
                  icon = 'icon-list-alt'
                  var feedviewpath = "graph/";
                  if (_SETTINGS && _SETTINGS.hasOwnProperty('feedviewpath') && _SETTINGS.feedviewpath !== "") {
                      var feedviewpath = _SETTINGS.feedviewpath;
                  }
                  href = [path, feedviewpath, feedid].join("");
                  lastvalue = (this.feedlist[feedid].value*1).toFixed(2);
                } else {
                  text = 'Feedid "+feedid+" does not exists or was deleted'
                }
                break;

                case ProcessArg.TEXT:
                  title = _Tr("Text")
                  text = this.contextprocesslist[z][i+1]
                  icon = 'icon-edit'
                break;
                
                case ProcessArg.SCHEDULEID:
                var schid = this.contextprocesslist[z][i+1];
                if (this.schedulelist[schid]!=undefined) {
                  title = _Tr("Schedule")+" "+schid
                  text = this.schedulelist[schid].name
                  icon = 'icon-time'

                } else {
                  text = "Schedule "+schid+" does not exists or was deleted"
                }
                break;
              }

              args[i] = {
                text: text,
                title: title,
                icon: icon,
                href: href
              };
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
        for (let i=0; i<args.length; i++) {
          let arg = args[i];
          if(arg.text){
            label += arg.href ? '<a href="'+arg.href+'" class="text-info"' : '<span class="muted"'
            label += ' title="'+arg.title+'"'
            label += ">"
            label += arg.icon ? '<i class="'+arg.icon+'"></i> ' : ''
            label += arg.text || arg.title
            label += arg.href ? '</a>':'</span>'
          }
          if (i < args.length - 1) {
            label += ', ';
          }
        }

        try {

            let cssClass = "";
            if (this.processlist[processkey].argtype !== undefined) {
              cssClass = this.argtypes[this.processlist[processkey].argtype].cssClass;
            } else {
              cssClass = 'label-warning'; // Default class if no argtype is defined
            }

            tag = `<span title="${this.processlist[processkey].description.replace(/<(?:.|\n)*?>/gm, '')}" 
            style="cursor:help" 
            class="fw-label overflow-hidden label ${cssClass}">${this.processlist[processkey].short.replace(/>/g, "&gt;").replace(/</g, "&lt;")}</span>`
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
      5: {cssClass: 'label-warning',    title: 'Schedule: {longText} - {schedule.name}'}
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
      let id_and_args = processPairs[z].split(":")
      var process_id = parseInt(id_and_args[0])
      process_id = isNaN(process_id) ? id_and_args[0]: this.getProcessKeyById(process_id);

      badge.value = id_and_args[1]

      badge.process = this.processlist.hasOwnProperty(process_id) ? this.processlist[process_id] : false

      if(this.init_done === 0 && badge.process!==false){

        // set badge properties
        let argtype = ProcessArg.NONE;
        if (badge.process.argtype !== undefined) {
          argtype = badge.process.argtype;
        } else if (badge.process.args !== undefined && Array.isArray(badge.process.args) && badge.process.args.length > 0) {
          // If args is an array, use the first argument's type
          // Review this!!
          argtype = badge.process.args[0].type;
        }

        badge.type = this.argtypes[argtype]
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
          text: ' ⌛ ',
          title: '',
          cssClass: 'muted',
          href: false
        })
      }
    }
    // console.log(badges)
    return badges;
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

      let new_process = [];
      new_process.push(processid); // process id

      if (vue_args.args != undefined && Array.isArray(vue_args.args)) {
        // Loop through the Vue args and get the values
        for (let i = 0; i < vue_args.args.length; i++) {
          let arg_type = vue_args.args[i].type;
          let arg_value = vue_args.args[i].value;
          switch (arg_type) {

            // Value
            // Check if the value is a valid number
            case ProcessArg.VALUE:
              if (arg_value === undefined || arg_value === null || arg_value === "" || isNaN(arg_value)) {
                alert('ERROR: Value must be a valid number');
                return false;
              }
              new_process.push(parseFloat(arg_value));
              break;

            // Input ID
            // Input id is from input select so no need to check if it's a valid input
            case ProcessArg.INPUTID:
              new_process.push(parseInt(arg_value));
              break;

            // Feed ID
            // Feed id is from feed select so no need to check if it's a valid feed
            // Create new feed if feed id is -1
            case ProcessArg.FEEDID:
              let feedid = parseInt(arg_value);
              if (feedid == -1) {
                let feedtag = vue_args.args[i].new_feed_tag;
                let feedname = vue_args.args[i].new_feed_name;
                let engine = vue_args.args[i].new_feed_engine;

                var options = {};
                if (engine==6 || engine==5 || engine==4 || engine==1) {
                  let interval = vue_args.args[i].new_feed_interval;
                  if (interval=="") {
                    alert('ERROR: Please select a feed interval');
                    return false;
                  }
                  options = {"interval":interval};
                }
                else if (engine==8 || engine==0) {
                  options = {"name":vue_args.args[i].new_feed_table_name};
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
                new_process.push(feedid);
              } else {
                new_process.push(feedid);
              }
              break;

            // Text
            // Text must not be empty and must not contain commas or colons
            case ProcessArg.TEXT:
              // Text must not contain commas and semi-colons
              if (arg_value === undefined || arg_value === null || arg_value === "") {
                alert('ERROR: Text must not be empty');
                return false;
              }
              if (arg_value.includes(',') || arg_value.includes(':')) {
                alert('ERROR: Text must not contain commas or colons');
                return false;
              }
              new_process.push(arg_value);
              break;

            // Schedule ID
            // Schedule id is from schedule select so no need to check if it's a valid schedule
            case ProcessArg.SCHEDULEID:
              new_process.push(parseInt(arg_value));
              break;

            // None
            case ProcessArg.NONE:
              new_process.push(0);
              break;
          }
        }
      }

      console.log("Adding process:", new_process);

      processlist_ui.contextprocesslist.push(new_process); 


      /*
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
      */


       
      processlist_ui.draw();
      processlist_ui.modified();

    });

    $('#processlist-ui #process-select').change(function(){
      var processid = $(this).val();

      $("#description").html("");

      // Check ProcessArg Type
      if (processid) {

        let process = processlist_ui.processlist[processid];


        let args = {};
        // Set the Vue args data
        if (process.args != undefined && Array.isArray(process.args)) {
          args = JSON.parse(JSON.stringify(process.args));
          
        } else if (process.argtype != undefined) {

          // Base type
          let singular_arg = {"type": process.argtype};

          // Copy over egines if available
          if (process.engines !== undefined && Array.isArray(process.engines)) {
            singular_arg.engines = process.engines;
          }

          // Copy over unit if available
          if (process.unit !== undefined) {
            singular_arg.unit = process.unit;
          }
          
          args = [singular_arg];
        }

        // Set default values for Vue args
        
        for (let i = 0; i < args.length; i++) {
          let arg = args[i];
          switch (arg.type) {
            case ProcessArg.VALUE:
              arg.value = 0; // Default value for VALUE type
              if (arg.default !== undefined) {
                arg.value = arg.default; // Use default value if available
              }
              break;
            case ProcessArg.INPUTID:
              arg.value = 0; // Default value for INPUTID type
              if (processlist_ui.inputlist.length > 0) {
                arg.value = processlist_ui.inputlist[0].id; // Default to first input
              }
              break;
            case ProcessArg.FEEDID:
              arg.value = -1; // Default value for FEEDID type (create new feed)
              arg.new_feed_tag = processlist_ui.newfeedtag; // Default feed tag
              arg.new_feed_name = processlist_ui.newfeedname; // Default feed name
              arg.new_feed_engine = 5; // Default feed engine
              arg.new_feed_interval = 10; // Default feed interval
              arg.new_feed_table_name = ''; // Default feed table name

              if (arg.engines !== undefined && Array.isArray(arg.engines)) {
                arg.new_feed_engine = parseInt(arg.engines[0]); // Default to first engine in the list
              }
              break;
            case ProcessArg.TEXT:
              arg.value = ''; // Default value for TEXT type
              break;
            case ProcessArg.SCHEDULEID:
              arg.value = 0; // Default value for SCHEDULEID type
              if (processlist_ui.schedulelist.length > 0) {
                arg.value = processlist_ui.schedulelist[0].id; // Default to first schedule
              }
              break;
            case ProcessArg.NONE:
              arg.value = 0; // Default value for NONE type
              break;
          }
        }

        // Set the Vue args data
        Vue.set(vue_args, 'args', args);

        $("#description").html("<p><strong>" + processlist_ui.processlist[processid]['name'] + "</strong></p>");

        if (processlist_ui.processlist[processid]['description'] === undefined || processlist_ui.processlist[processid]['description'] == "") {
          $("#description").append("<p><b style='color: orange'>No process description available for process '"+processlist_ui.processlist[processid][0]+"' with id '"+processid+"'.<br>Add a description to Module\\<i>module_name</i>\\<i>module_name</i>_processlist.php in process_list() function, $list[] array at the 'desc' key.</b><br>Please <a target='_blank' href='https://github.com/emoncms/emoncms/issues/new'>click here</a> and paste the text above to ask a developer to include a process description.</b></p>");
        } else {
          $("#description").append("<p>" + processlist_ui.processlist[processid]['description'] + "</p>");
       
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
                    return contextprocesslist[processId];
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
      parts.push(array[z].join(":"));
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

    vue_args = new Vue({
      el: '#vue_args',
      data: {
        args: [],
        inputs_by_node: {},
        feeds_by_tag: {}
      },
      methods: {
        feedSelectChange: function() {
          if (typeof nodes_display !== 'undefined') {
              autocomplete(document.getElementById("new-feed-tag"), Object.keys(nodes_display));
          }
        }
      }
    });


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


      var feeds_by_tag = {};
      for (let z in feeds) {
        let tag = feeds[z].tag;
        if (!feeds_by_tag[tag]) feeds_by_tag[tag] = [];
        feeds_by_tag[tag].push(feeds[z]);
      }

      vue_args.$set(vue_args, 'feeds_by_tag', feeds_by_tag);
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
      let inputs = result;
      // set vue inputs
      let inputs_by_node = {};
      for (let z in inputs) {
        let node = inputs[z].nodeid;
        if (!inputs_by_node[node]) inputs_by_node[node] = [];
        inputs_by_node[node].push(inputs[z]);
      }
      vue_args.$set(vue_args, 'inputs_by_node', inputs_by_node);
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
    this.newfeedname = newfeedname;
    this.newfeedtag = newfeedtag;
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