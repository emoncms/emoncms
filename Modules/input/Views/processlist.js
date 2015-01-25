
var processlist_ui =
{
    variableprocesslist: [],
    variableid: 0,
    nodeid: 10,
    
    processlist: [],
    feedlist:[],
    inputlist:[],
    schedulelist:[],
    
    enable_mysql_all: false,

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
                
                // Check ProcessArg Type
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
                            lastvalue = "<span style='color:#888; font-size:12px'>(input last value:"+this.inputlist[inpid].value+")</span>";
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
                            lastvalue = "<span style='color:#888; font-size:12px'>(feed last value:"+this.feedlist[feedid].value+")</span>";
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
                
                out += "<td>"+(i+1)+"</td><td>"+this.processlist[processid][0]+"</td><td>"+arg+"</td><td>"+lastvalue+"</td>";
         
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
                        if (datatype==2) { 
                            options = {interval:3600*24};
                        } else {
                            options = {interval:$('#feed-interval').val()};
                        }
                        
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
                        
                        processlist_ui.feedlist[feedid] = {'id':feedid, 'name':feedname,'value':'n/a','tag':feedtag};
                        
                        // Feedlist
                        var out = "<option value=-1>CREATE NEW:</option>";
                        for (i in processlist_ui.feedlist) {
                          out += "<option value="+processlist_ui.feedlist[i].id+">"+processlist_ui.feedlist[i].name+"</option>";
                        }
                        $("#feed-select").html(out);
                        
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

            if (feedid!=-1) {
                $("#feed-name").hide();
                $("#feed-interval").hide();
                $("#feed-engine, .feed-engine-label").hide(); 
            } else {
                $("#feed-name").show();
                $("#processlist-ui #feed-engine").change(); // select available interval for engine
                $("#feed-engine, .feed-engine-label").show(); 
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
        
        if (this.enable_mysql_all) {
            var mysql_found = false;
            for (e in engines) {
                if (engines[e]==0) mysql_found = true;
            }
            if (!mysql_found) engines.push(0);
        }

        if (prc!='histogram')
        {
            // Start by hiding all feed engine options
            $("#feed-engine option").hide();

            // Show only the feed engine options that are available
            for (e in engines) $("#feed-engine option[value="+engines[e]+"]").show(); 

            $("#feed-engine, .feed-engine-label").hide(); 
            if (typeof(engines) != "undefined") {
                // Select the first feed engine in the engines array by default
                $("#feed-engine").val(engines[0]);
                
                // If there's only one or none feed engine to choose from then dont show feed engine selector
                if (engines.length > 1) {
                    $("#feed-engine, .feed-engine-label").show();
                }
                $("#feed-select option[value=-1]").show(); // enable create new feed
            } else {
                $("#feed-select option[value=-1]").hide(); // disable create new feed as we have no supported engines for this proccess
                for (i in processlist_ui.feedlist) {
                    $("#feed-select").val(processlist_ui.feedlist[i].id); // select first feed
                    break;
                }
            }

            // If the datatype is daily then the interval is fixed to 3600s x 24h, no need to show interval selector
            if (datatype==2) {
                $("#feed-interval").hide();
            } else {
                $("#feed-interval").show();
                $("#feed-interval").val(10);
            } 
        }
        else
        {
            // Else feed engine is histogram
            $("#feed-engine, .feed-engine-label").hide();
            $("#feed-interval").hide();
            $("#feed-engine").val(engines[0]);
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
    }

}
