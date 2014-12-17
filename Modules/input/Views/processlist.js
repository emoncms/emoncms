
var processlist_ui =
{
    variableprocesslist: [],
    variableid: 0,
    nodeid: 10,
    
    processlist: [],
    feedlist:[],
    inputlist:[],
    
    enable_mysql_all: false,

    'draw':function()
    {
        var i = 0;
        var out="";
        
        console.log(this.variableprocesslist);
        
        if (this.variableprocesslist.length==0) {
            out += "<tr class='alert'><td></td><td></td><td><b>You have no processes defined</b></td><td></td><td></td><td></td></tr>";
        } else {
        
            for (z in this.variableprocesslist)
            {
                
                out += '<tr>';

                // Move process up or down
                out += '<td>';
                if (i > 0) {
                    out += '<a class="move-process" href="#" title="Move up" processid='+i+' moveby=-1 ><i class="icon-arrow-up"></i></a>';
                }

                if (i < this.variableprocesslist.length-1) {
                    out += '<a class="move-process" href="#" title="Move up" processid='+i+' moveby=1 ><i class="icon-arrow-down"></i></a>';
                }
                out += '</td>';

                // Process name and argument
                var processid = parseInt(this.variableprocesslist[z][0]);
                var arg = "";
                var lastvalue = "";
                
                if (this.processlist[processid][1]==0) {
                    arg = this.variableprocesslist[z][1];
                }
                
                if (this.processlist[processid][1]==1) {
                    var inpid = this.variableprocesslist[z][1];
                    arg += "Node "+this.inputlist[inpid].nodeid+": ";
                    if (this.inputlist[inpid].description!="") arg += this.inputlist[inpid].description; else arg += this.inputlist[inpid].name;
                    lastvalue = "<span style='color:#888; font-size:12px'>(inputvalue:"+(this.inputlist[inpid].value*1).toFixed(2)+")</span>";
                }
                
                if (this.processlist[processid][1]==2) {
                    var feedid = this.variableprocesslist[z][1];
                    if (processlist_ui.feedlist[feedid]!=undefined) {
                        arg += "<a class='label label-info' href='"+path+"vis/auto?feedid="+feedid+"'>";
                        if (processlist_ui.feedlist[feedid].tag) arg += processlist_ui.feedlist[feedid].tag+": ";
                        arg += processlist_ui.feedlist[feedid].name;
                        arg += "</a>";
                        lastvalue = "<span style='color:#888; font-size:12px'>(feedvalue:"+(processlist_ui.feedlist[feedid].value*1).toFixed(2)+")</span>";
                    } else {
                      // delete feed
                    }
                }
                
                if (this.processlist[processid][1]==4) {
                    arg = this.variableprocesslist[z][1];
                }
                
                out += "<td>"+(i+1)+"</td><td>"+this.processlist[processid][0]+"</td><td>"+arg+"</td><td>"+lastvalue+"</td>";
         
                // Delete process button (icon)
                out += '<td><a href="#" class="delete-process" title="Delete" processid='+i+'><i class="icon-trash"></i></a></td>';

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
            
            // Type: value (scale, offset)
            if (process[1]==0) arg = $("#value-input").val();
            if (process[1]==4) arg = $("#value-input").val();
            
            // Type: input (* / + - by input)
            if (process[1]==1) arg = $("#input-select").val();
            
            // Type: feed
            if (process[1]==2)
            {
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
                    
                    processlist_ui.feedlist[feedid] = {'id':feedid, 'name':feedname,'value':''};
                    
                    // Feedlist
                    var out = "<option value=-1>CREATE NEW:</option>";
                    for (i in processlist_ui.feedlist) {
                      out += "<option value="+processlist_ui.feedlist[i].id+">"+processlist_ui.feedlist[i].name+"</option>";
                    }
                    $("#feed-select").html(out);
                    
                    $.ajax({ url: path+"feed/set.json", data: "id="+feedid+"&fields="+JSON.stringify({'tag':feedtag}), async: true, success: function(data){} });
                    
                }
                arg = feedid;


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
            $("#type-input").hide();
            $("#type-feed").hide();
            
            if (processlist_ui.processlist[processid][1]==0) $("#type-value").show();
            if (processlist_ui.processlist[processid][1]==1) $("#type-input").show();
            if (processlist_ui.processlist[processid][1]==2) 
            {
                $("#type-feed").show();
                processlist_ui.showfeedoptions(processid);
            }
            if (processlist_ui.processlist[processid][1]==4) $("#type-value").show();
            $("#description").html(process_info[processid]);
        });

        $('#processlist-ui #feed-select').change(function() {
            var feedid = $("#feed-select").val();
            
            if (feedid!=-1) {
                $("#feed-name").hide();
                $("#feed-interval").hide();
                $("#feed-engine").hide();
                $(".feed-engine-label").hide();
            } else {
                $("#feed-name").show();
                $("#feed-interval").show();   
                $("#feed-engine").show();
                $(".feed-engine-label").show();
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

            // Select the first feed engine in the engines array by default
            $("#feed-engine").val(engines[0]);

            // If there's only one feed engine to choose from then dont show feed engine selector
            if (engines.length==1) {
                $("#feed-engine, .feed-engine-label").hide(); 
            } else {
                $("#feed-engine, .feed-engine-label").show();
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
    
    'drawinline': function (processliststr) { 

      if (!processliststr) return "";
      
      var processPairs = processliststr.split(",");
      console.log(processPairs);
      var out = "";

      for (var z in processPairs)
      {
        var keyvalue = processPairs[z].split(":");

        var key = parseInt(keyvalue[0]);
        var type = "";
        var color = "";

        switch(key)
        {
          case 1:
            key = 'log'; type = 2; break;
          case 2:  
            key = 'x'; type = 0; break;
          case 3:  
            key = '+'; type = 0; break;
          case 4:    
            key = 'kwh'; type = 2; break;
          case 5:  
            key = 'kwhd'; type = 2; break;
          case 6:
            key = 'x inp'; type = 1; break;
          case 7:
            key = 'ontime'; type = 2; break;
          case 8:
            key = 'kwhinckwhd'; type = 2; break;
          case 9:
            key = 'kwhkwhd'; type = 2; break;
          case 10:  
            key = 'update'; type = 2; break;
          case 11: 
            key = '+ inp'; type = 1; break;
          case 12:
            key = '/ inp'; type = 1; break;
          case 13:
            key = 'phaseshift'; type =2; break;
          case 14:
            key = 'accumulate'; type = 2; break;
          case 15:
            key = 'rate'; type = 2; break;
          case 16:
            key = 'hist'; type = 2; break;
          case 17:  
            key = 'average'; type = 2; break;
          case 18:
            key = 'flux'; type = 2; break;
          case 19:
            key = 'pwrgain'; type = 2; break;
          case 20:
            key = 'pulsdiff'; type = 2; break;
          case 21:
            key = 'kwhpwr'; type = 2; break;
          case 22:
            key = '- inp'; type = 1; break;
          case 23:
            key = 'kwhkwhd'; type = 2; break;
          case 24:
            key = '> 0'; type = 3; break;
          case 25:
            key = '< 0'; type = 3; break;
          case 26:
            key = 'unsign'; type = 3; break;
          case 27:
            key = 'max'; type = 2; break;
          case 28:
            key = 'min'; type = 2; break;
        }  

        value = keyvalue[1];
        
        switch(type)
        {
          case 0:
            type = 'value: '; color = 'important';
            break;
          case 1:
            type = 'input: '; color = 'warning';
            break;
          case 2:
            type = 'feed: '; color = 'info';
            break;
          case 3:
            type = ''; color = 'important';
            value = ''; // Argument type is NONE, we don't mind the value
            break;
        }

        if (type == 'feed: ') { 
          out += "<a href='"+path+"vis/auto?feedid="+value+"'<span class='label label-"+color+"' title='"+type+value+"' style='cursor:pointer'>"+key+"</span></a> "; 
        } else {
          out += "<span class='label label-"+color+"' title='"+type+value+"' style='cursor:default'>"+key+"</span> ";
        }
        
      }
      
      return out;
    }    
    
}
