/*

  table.js is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
 
*/

var customtablefields = {

    'icon':
    {
        'draw': function(row,field)
        {
            if (table.data[row][field] == true) return "<i class='"+table.fields[field].trueicon+"' type='input' ></i>";
            if (table.data[row][field] == false) return "<i class='"+table.fields[field].falseicon+"' type='input' ></i>";
        },

        'event': function()
        {
            // Event code for clickable switch state icon's
            $(table.element).on('click', 'i[type=input]', function() {
                var row = $(this).parent().attr('row');
                var field = $(this).parent().attr('field');
                table.data[row][field] = !table.data[row][field];

                var fields = {};
                fields[field] = table.data[row][field];

                $(table.element).trigger("onSave",[table.data[row]['id'],fields]);

                if (table.data[row][field]) $(this).attr('class', table.fields[field].trueicon); else $(this).attr('class', table.fields[field].falseicon);
            });
        }
    },

    'updated':
    {
        'draw': function (row,field) { return list_format_updated(table.data[row][field]) }
    },

    'value':
    {
        'draw': function (row,field) { return list_format_value(table.data[row][field]) }
    },

    'processlist':
    {
        'draw': function (row,field) { 

          var processlist = table.data[row][field];
          if (!processlist) return "";
          
          var processPairs = processlist.split(",");

          var out = "";

          for (z in processPairs)
          {
            var keyvalue = processPairs[z].split(":");

            var key = keyvalue[0];
            var type = "";
            var color = "";

            if (key==1) { key = 'log'; type = 2;}  
            if (key==2) { key = 'x'; type = 0;}  
            if (key==3) { key = '+'; type = 0;}  
            if (key==4) { key = 'kwh'; type = 2;}  
            if (key==5) { key = 'kwhd'; type = 2;}  
            if (key==6) { key = 'x inp'; type = 1;}
            if (key==7) { key = 'ontime'; type = 2;}  
            if (key==8) { key = 'kwhinckwhd'; type = 2;}  
            if (key==9) { key = 'kwhkwhd'; type = 2;}  
            if (key==10) { key = 'update'; type = 2;} 
            if (key==11) { key = '+ inp'; type = 1;} 
            if (key==12) { key = '/ inp'; type = 1;} 
            if (key==13) { key = 'phaseshift'; }
            if (key==14) { key = 'accumulate'; type = 2;}  
            if (key==15) { key = 'rate'; type = 2;}  
            if (key==16) { key = 'hist'; type = 2;}  
            if (key==17) { key = 'average'; type = 2;}  
            if (key==18) { key = 'flux'; type = 2;}  
            if (key==19) { key = 'pwrgain'; type = 2;}  
            if (key==20) { key = 'pulsdiff'; type = 2;}  
            if (key==21) { key = 'kwhpwr'; type = 2;}  
            if (key==22) { key = '- inp'; type = 1;} 
            if (key==23) { key = 'kwhkwhd'; type = 2;}  

            if (type == 0) { type = 'value: '; color = 'important';}
            if (type == 1) { type = 'input: '; color = 'warning';}
            if (type == 2) { type = 'feed: '; color = 'info';}

            out += "<span class='label label-"+color+"' title='"+type+keyvalue[1]+"'>"+key+"</span> ";
          }
          
          return out;
        }
    },

    'iconlink':
    {
        'draw': function (row,field) { 
          var icon = 'icon-eye-open'; if (table.fields[field].icon) icon = table.fields[field].icon;
          return "<a href='"+table.fields[field].link+table.data[row]['id']+"' ><i class='"+icon+"' ></i></a>" 
        }
    },
}


// Calculate and color updated time
function list_format_updated(time)
{
  var now = (new Date()).getTime();
  var update = (new Date(time)).getTime();
  var lastupdate = (now-update)/1000;

  var secs = (now-update)/1000;
  var mins = secs/60;
  var hour = secs/3600

  var updated = secs.toFixed(0)+"s ago";
  if (secs>180) updated = mins.toFixed(0)+" mins ago";
  if (secs>(3600*2)) updated = hour.toFixed(0)+" hours ago";
  if (hour>24) updated = "inactive";

  var color = "rgb(255,125,20)";
  if (secs<60) color = "rgb(240,180,20)";
  if (secs<25) color = "rgb(50,200,50)";

  return "<span style='color:"+color+";'>"+updated+"</span>";
}

// Format value dynamically 
function list_format_value(value)
{
  if (value>10) value = (1*value).toFixed(1);
  if (value>100) value = (1*value).toFixed(0);
  if (value<10) value = (1*value).toFixed(2);
  return value;
}

