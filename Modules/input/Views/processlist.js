
var processlist_ui =
{
    variableprocesslist: [],
    variableid: 0,
    nodeid: 10,
    
    processlist: [],
    feedlist:[],
    inputlist:[],
    schedulelist:[],
    
    engines_hidden:[],

    'draw':function()
    {
        var i = 0;
        var out="";
        
        console.log(this.variableprocesslist);
        
        if (this.variableprocesslist.length==0) {
            $("#process-table").hide();
            $("#noprocess").show();
        } else {
            $("#process-table").show();
            $("#noprocess").hide();
            for (z in this.variableprocesslist)
            {
                
                out += '<tr>';

                // Move process up or down
                out += '<td>';
                if (i > 0) {
                    out += '<a class="move-process" title="Move up" processid='+i+' moveby=-1 ><i class="icon-arrow-up"></i></a>';
                }

                if (i < this.variableprocesslist.length-1) {
                    out += '<a class="move-process" title="Move up" processid='+i+' moveby=1 ><i class="icon-arrow-down"></i></a>';
                }
                out += '</td>';

                // Process name and argument
                var processid = parseInt(this.variableprocesslist[z][0]);
                var arg = "";
                var lastvalue = "";
                
                var processname = "";
                // Check ProcessArg Type
                if (this.processlist[processid] != undefined) {
                    switch(this.processlist[processid][1]) {
                        case 0: // VALUE
                            arg += "<span class='label label-info' title='Value'>";
                            arg += "<i class='icon-edit icon-white'></i> ";
                            arg += this.variableprocesslist[z][1];
                            arg += "</span>";
                            break;
                        
                        case 1: //INPUTID
                            var inpid = this.variableprocesslist[z][1];
                            if (this.inputlist[inpid]!=undefined) {
                                arg += "<span class='label label-info' title='Input "+inpid+"'>";
                                arg += "<i class='icon-signal icon-white'></i> ";
                                arg += "Node "+this.inputlist[inpid].nodeid+":"+this.inputlist[inpid].name;
                                if (this.inputlist[inpid].description!="") arg += " "+this.inputlist[inpid].description;
                                arg += "</span>";
                                lastvalue = "<span style='color:#888; font-size:12px'>(input last value:"+(this.inputlist[inpid].value*1).toFixed(2)+")</span>";
                            } else {
                                arg += "<span class='label label-important'>Input "+schid+" does not exists or was deleted</span>"
                                // does not exist or was deleted
                            }
                            break;
                            
                        case 2: //FEEDID
                            var feedid = this.variableprocesslist[z][1];
                            if (this.feedlist[feedid]!=undefined) {
                                arg += "<a class='label label-info' title='Feed "+feedid+"' href='"+path+"vis/auto?feedid="+feedid+"'>";
                                arg += "<i class='icon-list-alt icon-white'></i> ";
                                if (this.feedlist[feedid].tag) arg += this.feedlist[feedid].tag+": ";
                                arg += this.feedlist[feedid].name;
                                arg += "</a>";
                                lastvalue = "<span style='color:#888; font-size:12px'>(feed last value:"+(this.feedlist[feedid].value*1).toFixed(2)+")</span>";
                            } else {
                                arg += "<span class='label label-important'>Feedid "+feedid+" does not exists or was deleted</span>"
                              // does not exist or was deleted
                            }
                            break;

                        case 4: // TEXT
                            arg += "<span class='label label-info' title='Text'>";
                            arg += "<i class='icon-edit icon-white'></i> ";
                            arg += this.variableprocesslist[z][1];
                            arg += "</span>";
                            break;

                        case 5: // SCHEDULEID
                            var schid = this.variableprocesslist[z][1];
                            if (this.schedulelist[schid]!=undefined) {
                                arg += "<span class='label label-info' title='Schedule "+schid+"' >";
                                arg += "<i class='icon-time icon-white'></i> ";
                                arg += this.schedulelist[schid].name;
                                arg += "</span>";
                            } else {
                                arg += "<span class='label label-important'>Schedule "+schid+" does not exists or was deleted</span>"
                                // does not exist or was deleted
                            }
                            //lastvalue = "<span style='color:#888; font-size:12px'>(input last value:"+this.schedulelist[schid].value+")</span>";
                            break;
                    }
                    processname = this.processlist[processid][0];
                }
                else {
                    processname = "UNSUPPORTED";
                    arg += "<span class='label label-important' title='Value'>Process '"+processid+"' not available. Module missing?</span>";
                }
                out += "<td>"+(i+1)+"</td><td>"+processname+"</td><td>"+arg+"</td><td>"+lastvalue+"</td>";
         
                // Delete process button (icon)
                out += '<td><a class="delete-process" title="Delete" processid='+i+'><i class="icon-trash"></i></a></td>';

                out += '</tr>';
                
                i++; // process id
            }
        }
        $('#variableprocesslist').html(out);
    },


    'events':function()
    {
        $("#processlist-ui #feed-engine").change(function(){
            var engine = $(this).val();
            $("#feed-interval").hide();
            if (engine==6 || engine==5 || engine==4 || engine==1) $("#feed-interval").show();
            
            var processid = $("#process-select").val();
            var datatype = processlist_ui.processlist[processid][4]; // 1:REALTIME, 2:DAILY, 3:HISTOGRAM
            // If the datatype is daily then the interval is fixed to 3600s x 24h = 1d and user cant select other
            if (datatype==2) {
                $("#feed-interval option").hide();                  // Hide all
                $("#feed-interval option").prop('disabled', true);  // for IE hide (grayed out)
                $("#feed-interval option[value=86400]").show();     // show this 3600*24
                $("#feed-interval option[value=86400]").prop('disabled', false);  //for IE show
                $("#feed-interval").val(86400); 
            } else {
                $("#feed-interval option").show(); // Show all
                $("#feed-interval option").prop('disabled', false);  //for IE show
                $("#feed-interval").val(10);   // default to 10s
            } 
        });

        $('#processlist-ui #process-add').click(function() 
        {
            var processid = $('#process-select').val();
            var process = processlist_ui.processlist[processid];
            var arg = '';
            
            // Check ProcessArg Type
            switch(process[1]) {
                case 0: // VALUE (scale, offset)
                    arg = $("#value-input").val();
                    break;
                    
                case 1: //INPUTID (* / + - by input)
                    arg = $("#input-select").val();
                    break;
                    
                case 2: //FEEDID
                    var feedid = $("#feed-select").val();
                    
                    if (feedid==-1) 
                    {
                        var feedname = $('#feed-name').val();
                        var feedtag = $('#feed-tag').val();
                        var engine = $('#feed-engine').val();
                        var datatype = process[4];
                        
                        var options = {};
                        options = {interval:$('#feed-interval').val()};
                        
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
                        
                        processlist_ui.feedlist[feedid] = {'id':feedid, 'name':feedname,'value':'n/a','tag':feedtag,'datatype':datatype};
                        processlist_ui.showfeedoptions(processid);  // Refresh Feedlist
                        
                        $.ajax({ url: path+"feed/set.json", data: "id="+feedid+"&fields="+JSON.stringify({'tag':feedtag}), async: true, success: function(data){} });
                    }
                    arg = feedid;
                    break;
                    
                case 4: // TEXT
                    arg = $("#text-input").val();
                    break;
                    
                case 5: // SCHEDULEID
                    arg = $("#schedule-select").val();
                    break;
            }
            
            processlist_ui.variableprocesslist.push([processid,""+arg]);
            processlist_ui.draw();
            
            input.add_process(processlist_ui.inputid,processid,arg);
            
            update_main_list(processlist_ui.inputid, processlist_ui.variableprocesslist);
            

        });
        
        $('#processlist-ui #process-select').change(function() {
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
            if (process_info[processid] === undefined) {
                $("#description").html("<b style='color: red'>No process description available for process '"+processlist_ui.processlist[processid][0]+"' with id '"+processid+"'.<br>Add a description to Module\\input\\Views\\process_info.js array.</b><br>Please <a target='_blank' href='https://github.com/emoncms/emoncms/issues/new'>click here</a> and past the text above to ask a developer to include a process description.</b>");  
            } else {
                $("#description").html(process_info[processid]);
            }
            
        });

        $('#processlist-ui #feed-select').change(function() {
            var feedid = $("#feed-select").val();

            if (feedid == -1) {
                $("#feed-name").show();
                $("#processlist-ui #feed-engine").change(); // select available interval for engine
                // If there's only one feed engine to choose from then dont show feed engine selector
                // CHAVEIRO: Commented for now so user can see what processor it's using.
                //var processid = $('#process-select').val();
                //var engines = processlist_ui.processlist[processid][6];   // 5:PHPFINA, 6:PHPFIWA
                //if (engines.length > 1) 
                    $("#feed-engine, .feed-engine-label").show();
            } else {
                $("#feed-name").hide();
                $("#feed-interval").hide();
                $("#feed-engine, .feed-engine-label").hide(); 
            }
        });
        
        $('#processlist-ui .table').on('click', '.delete-process', function() {
            processlist_ui.variableprocesslist.splice($(this).attr('processid'),1);
            
            var processid = $(this).attr('processid')*1;
            processlist_ui.draw();
            input.delete_process(processlist_ui.inputid,processid+1);
            
            update_main_list(processlist_ui.inputid, processlist_ui.variableprocesslist);
        });

        $('#processlist-ui .table').on('click', '.move-process', function() {

            var processid = $(this).attr('processid')*1;
            console.log(processid);
            var curpos = parseInt(processid);
            var moveby = parseInt($(this).attr('moveby'));
            var newpos = curpos + moveby;
            if (newpos>=0 && newpos<processlist_ui.variableprocesslist.length)
            { 
                processlist_ui.variableprocesslist = processlist_ui.array_move(processlist_ui.variableprocesslist,curpos,newpos);
                processlist_ui.draw();
                input.move_process(processlist_ui.inputid,processid+1,moveby);
            }

            update_main_list(processlist_ui.inputid, processlist_ui.variableprocesslist);
        });
        
        function update_main_list(inputid, processlist)
        {
            var process_str = "";
            for (z in processlist)
            {
                process_str += processlist[z].join(':') + ",";
            }
            process_str = process_str.slice(0, -1);
            
            // Update input table immedietly
            for (z in table.data) {
                if (table.data[z].id == inputid) {
                    table.data[z].processList = process_str;
                }
            }
            table.draw();
        }

    },
    
    'showfeedoptions':function(processid)
    {
        var prc = processlist_ui.processlist[processid][2];
        var engines = processlist_ui.processlist[processid][6];   // 5:PHPFINA, 6:PHPFIWA
        var datatype = processlist_ui.processlist[processid][4]; // 1:REALTIME, 2:DAILY, 3:HISTOGRAM
        
        var out = "<option value=-1>CREATE NEW:</option>";
        for (i in processlist_ui.feedlist) {
          out += "<option value="+processlist_ui.feedlist[i].id+">"+(processlist_ui.feedlist[i].tag === null ? "" : processlist_ui.feedlist[i].tag + ":") + processlist_ui.feedlist[i].name+"</option>";
        }
        $("#feed-select").html(out);
        
        $("#feed-select option").hide();    // Start by hiding all feeds
        $("#feed-select option").prop('disabled', true);  //for IE hide (grayed out)
        for (f in processlist_ui.feedlist) {
            if (processlist_ui.feedlist[f].datatype == datatype) {
                $("#feed-select option[value="+processlist_ui.feedlist[f].id+"]").show(); // Only show feeds of the supported datatype
                $("#feed-select option[value="+processlist_ui.feedlist[f].id+"]").prop('disabled', false);  //for IE show
            }
        }
        
        $("#feed-engine option").hide();    // Start by hiding all feed engine options
        $("#feed-engine option").prop('disabled', true);  //for IE hide (grayed out)
        for (e in engines) { 
            $("#feed-engine option[value="+engines[e]+"]").show();   // Show only the feed engine options that are available
            $("#feed-engine option[value="+engines[e]+"]").prop('disabled', false);  //for IE show
        }

        $("#feed-engine, .feed-engine-label").hide(); 
        if (typeof(engines) != "undefined") {
            $("#feed-engine").val(engines[0]);         // Select the first feed engine in the engines array by default
            $("#feed-select option[value=-1]").show(); // enable create new feed
            $("#feed-select option[value=-1]").prop('disabled', false);  //for IE show
        } else {
            $("#feed-select option[value=-1]").hide(); // disable create new feed as we have no supported engines for this proccess
            $("#feed-select option[value=-1]").prop('disabled', true);  //for IE hide (grayed out)
            for (f in processlist_ui.feedlist) {
                if (processlist_ui.feedlist[f].datatype == datatype) {    // Only feeds of the supported datatype
                    $("#feed-select").val(processlist_ui.feedlist[f].id); // select first feed
                    break;
                }
            }
        }

        $('#processlist-ui #feed-select').change();    // refresh feed select
    },

    // Process list functions
    'decode':function(str)
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
    },

    'encode':function(array)
    {
        var parts = [];
        for (z in array) parts.push(array[z][0]+":"+array[z][1]);
        return parts.join(",");
    },


    'array_move':function(array,old_index, new_index) 
    {
        if (new_index >= array.length) {
            var k = new_index - array.length;
            while ((k--) + 1) {
                array.push(undefined);
            }
        }
        array.splice(new_index, 0, array.splice(old_index, 1)[0]);
        return array; // for testing purposes
    },


    'load_all':function()
    {
        // Input Select List    
        var out = "";
        for (i in processlist_ui.inputlist) {
          var input = processlist_ui.inputlist[i];
          out += "<option value="+input.id+">"+input.nodeid+":"+input.name+" "+input.description+"</option>";
        }
        $("#input-select").html(out);
        
        // Schedule Select List
        $.ajax({ url: path+"schedule/list.json", dataType: 'json', async: true, success: function(result) {
            var schedules = {};
            for (z in result) schedules[result[z].id] = result[z];
            
            processlist_ui.schedulelist = schedules;
            var groupname = {0:'Public',1:'Mine'};
            var groups = [];
            //for (z in result) schedules[result[z].id] = result[z];
            
            for (z in processlist_ui.schedulelist)
            {
                var group = processlist_ui.schedulelist[z].own;
                group = groupname[group];
                if (!groups[group]) groups[group] = []
                processlist_ui.schedulelist[z]['_index'] = z;
                groups[group].push(processlist_ui.schedulelist[z]);
            }
            
            var out = "";
            for (z in groups)
            {
                out += "<optgroup label='"+z+"'>";
                for (p in groups[z])
                {
                    out += "<option value="+groups[z][p]['id']+">"+groups[z][p]['name']+(z!=groupname[1]?" ["+groups[z][p]['id']+"]":"")+"</option>";
                }
                out += "</optgroup>";
            }
            $("#schedule-select").html(out);
        }});
        
        // Feeds Select List
        $.ajax({ url: path+"feed/list.json", dataType: 'json', async: true, success: function(result) {
            var feeds = {};
            for (z in result) { feeds[result[z].id] = result[z]; }
            processlist_ui.feedlist = feeds;
        }});
        
        // Processors Select List
        $.ajax({ url: path+"input/getallprocesses.json", async: true, dataType: 'json', success: function(result){
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

}
