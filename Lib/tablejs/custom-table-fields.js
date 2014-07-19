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
              case 29:
                key = '+ feed'; type = 4; break;
              case 30:
                key = '- feed'; type = 4; break;
              case 31:
                key = 'x feed'; type = 4; break;
              case 32:
                key = '/ feed'; type = 4; break;
              case 33:
                key = '= 0'; type = 3; break;
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
              case 4:
                type = 'feed: '; color = 'warning';
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
    },

    'iconlink':
    {
        'draw': function (row,field) { 
          var icon = 'icon-eye-open'; if (table.fields[field].icon) icon = table.fields[field].icon;
          return "<a href='"+table.fields[field].link+table.data[row]['id']+"' ><i class='"+icon+"' ></i></a>" 
        }
    },

    'iconbasic':
    {
        'draw': function(row,field)
        {
            return "<i class='"+table.fields[field].icon+"' type='icon' row='"+row+"' style='cursor:pointer'></i>";
        }
    }
}


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

  var updated = secs.toFixed(0)+"s ago";
  if (secs>180) updated = mins.toFixed(0)+" mins ago";
  if (secs>(3600*2)) updated = hour.toFixed(0)+" hours ago";
  if (hour>24) updated = "inactive";

  var color = "rgb(255,125,20)";
  if (secs<25) color = "rgb(50,200,50)"
  else if (secs<60) color = "rgb(240,180,20)"; 

  return "<span style='color:"+color+";'>"+updated+"</span>";
}

// Format value dynamically 
function list_format_value(value)
{
  if (value>=10) value = (1*value).toFixed(1);
  if (value>=100) value = (1*value).toFixed(0);
  if (value<10) value = (1*value).toFixed(2);
  if (value<=-10) value = (1*value).toFixed(1);
  if (value<=-100) value = (1*value).toFixed(0);
  return value;
}
