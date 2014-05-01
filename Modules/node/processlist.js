
var processlist_ui =
{
    nodes: [],
    variableprocesslist: [],
    variableid: 0,
    nodeid: 10,
    
    processlist: [],
    feedlist:[],
    inputlist:[],
    
    enable_mysql_all: false,


    'init':function()
    {
    
        if (this.nodes[this.nodeid].decoder.variables[this.variableid].processlist==undefined) this.nodes[this.nodeid].decoder.variables[this.variableid].processlist = "";
        this.variableprocesslist = this.decode(this.nodes[this.nodeid].decoder.variables[this.variableid].processlist);
    
        // DRAW PROCESS SELECTOR

        var processgroups = [];
        var i = 0;
        for (z in this.processlist)
        {
            i++;
            var group = this.processlist[z][5];
            if (group!="Deleted") {
                if (!processgroups[group]) processgroups[group] = []
                this.processlist[z]['id'] = i;
                processgroups[group].push(this.processlist[z]);
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

        // Inputlist
        var out = "";
        for (i in processlist_ui.inputlist) {
          var input = processlist_ui.inputlist[i];
          out += "<option value="+input.id+">Node "+input.nodeid+":"+input.name+" "+input.description+"</option>";
        }
        $("#input-select").html(out);

        // Feedlist
        var out = "<option value=-1>CREATE NEW:</option>";
        for (i in processlist_ui.feedlist) {
          var feed = processlist_ui.feedlist[i];
          out += "<option value="+feed.id+">"+feed.name+"</option>";
        }
        $("#feed-select").html(out);
        
        $("#type-value").hide();
        $("#type-input").hide();
        $("#type-feed").hide();

        $("#type-feed").show();
        $("#description").html(process_info[1]);
        
        processlist_ui.showfeedoptions(1);
    },

    'draw':function()
    {
        var i = 0;
        var out="";
        
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
        $("#processlist-ui").on('change',"#feed-engine",function(){
            var engine = $(this).val();
            $("#feed-interval").hide();
            if (engine==6 || engine==5 || engine==4 || engine==1) $("#feed-interval").show();
        });

        $("#processlist-ui").on('click','#process-add',function() 
        {
            console.log("Process_add");
            var processid = $('#process-select').val();
            var process = processlist_ui.processlist[processid];
            var arg = '';
            
            // Type: value (scale, offset)
            if (process[1]==0) arg = $("#value-input").val();
            
            // Type: input (* / + - by input)
            if (process[1]==1) arg = $("#input-select").val();
            
            // Type: feed
            if (process[1]==2)
            {
                var feedid = $("#feed-select").val();
              
                if (feedid==-1) 
                {
                    var feedname = $('#feed-name').val();
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
                    
                    processlist_ui.feedlist = feed.list_assoc();
                }
                arg = feedid;


            }
            
            //if (arg!="") 
            //{
                console.log(processid+" "+arg);
                processlist_ui.variableprocesslist.push([processid,arg]);
                processlist_ui.nodes[processlist_ui.nodeid].decoder.variables[processlist_ui.variableid].processlist = processlist_ui.encode(processlist_ui.variableprocesslist);
                node.setdecoder(processlist_ui.nodeid,processlist_ui.nodes[processlist_ui.nodeid].decoder);
                
                //if (result.success == false) {
                //    alert(data.message);
                //    return false;
                //}
                processlist_ui.draw();
            //}
        });

        $("#processlist-ui").on('change','#process-select',function() {
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
            $("#description").html(process_info[processid]);
        });

        $("#processlist-ui").on('change','#feed-select',function() {
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

        $('#process-table').on('click', '.delete-process', function() {
            processlist_ui.variableprocesslist.splice($(this).attr('processid'),1);
            processlist_ui.nodes[processlist_ui.nodeid].decoder.variables[processlist_ui.variableid].processlist = processlist_ui.encode(processlist_ui.variableprocesslist);
            node.setdecoder(processlist_ui.nodeid,processlist_ui.nodes[processlist_ui.nodeid].decoder);
            
            processlist_ui.draw();
        });

        $('#process-table').on('click', '.move-process', function() {

            var curpos = parseInt($(this).attr('processid'));
            var moveby = parseInt($(this).attr('moveby'));
            var newpos = curpos + moveby;
            if (newpos>=0 && newpos<processlist_ui.variableprocesslist.length)
            { 
                processlist_ui.variableprocesslist = processlist_ui.array_move(processlist_ui.variableprocesslist,curpos,newpos);
            }

            processlist_ui.nodes[processlist_ui.nodeid].decoder.variables[processlist_ui.variableid].processlist = processlist_ui.encode(processlist_ui.variableprocesslist);
            node.setdecoder(processlist_ui.nodeid,processlist_ui.nodes[processlist_ui.nodeid].decoder);
            processlist_ui.draw();
        });

    },
    
    'showfeedoptions':function(processid)
    {
        var prc = processlist_ui.processlist[processid][2];
        var engines = processlist_ui.processlist[processid][6];   // 5:PHPFINA, 6:PHPFIWA
        var datatype = processlist_ui.processlist[processid][4]; // 1:REALTIME, 2:DAILY, 3:HISTOGRAM
        
        //processlist_ui.enable_mysql_all = true;
        
        if (processlist_ui.enable_mysql_all) {
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
