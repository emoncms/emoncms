/*
  table.js is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Part of the OpenEnergyMonitor project: http://openenergymonitor.org
  2016-12-20 - Expanded tables by : Nuno Chaveiro  nchaveiro(a)gmail.com  
*/
var customtablefields = {
  'icon': {
    'draw': function(t,row,child_row,field) {
      if (t.data[row][field] == true) return "<i class='"+t.fields[field].trueicon+"' type='input' style='cursor:pointer'></i>";
      if (t.data[row][field] == false) return "<i class='"+t.fields[field].falseicon+"' type='input' style='cursor:pointer'></i>";
    },

    'event': function() {
      // Event code for clickable switch state icon's
      $(table.element).on('click', 'i[type=input]', function() {
        var row = $(this).parent().attr('row');
        var field = $(this).parent().attr('field');
        var t = table;
        if (!t.data[row]['#READ_ONLY#']) {
          var val = t.data[row][field];
          if (typeof val === "boolean") {
            t.data[row][field] = !val;
          } else {
            //boolean conversion and negate
            var boolVal;
            if(typeof val === "number"){
                boolVal = val === 0 ? false : true;
            } else if (typeof val === "string") {
                boolVal = (val == "0" || val == "false") ? false : true;
            } else {
                //neither bool nor number nor string
                //"strange" value
                boolVal = false;
            }
            t.data[row][field] = !boolVal;  
          }

          var fields = {};
          fields[field] = t.data[row][field];

          $(table.element).trigger("onSave",[t.data[row]['id'],fields]);
          if (t.data[row][field]) $(this).attr('class', t.fields[field].trueicon); else $(this).attr('class', t.fields[field].falseicon);
          t.draw();
        }
      });
    }
  },

  'updated': {
    'draw': function (t,row,child_row,field) { return list_format_updated(t.data[row][field]) }
  },

  'value': {
    'draw': function (t,row,child_row,field) { return list_format_value(t.data[row][field]) }
  },

  'processlist': {
    'draw': function (t,row,child_row,field) {
      var processlist = t.data[row][field];
      if (processlist_ui != undefined) return processlist_ui.drawpreview(processlist);
      else return "";
    }
  },

  'iconlink': {
    'draw': function (t,row,child_row,field) {
      var icon = 'icon-eye-open'; if (t.fields[field].icon) icon = t.fields[field].icon;
      return "<a href='"+t.fields[field].link+t.data[row]['id']+"' ><i class='"+icon+"' ></i></a>" 
    }
  },

  'iconbasic': {
    'draw': function(t,row,child_row,field) {
      return "<i class='"+t.fields[field].icon+"' type='icon' row='"+row+"' child_row='"+child_row+"' style='cursor:pointer'></i>";
    }
  },

  'hinteditable': {
    'draw': function (t,row,child_row,field) { return "…";},
    'edit': function (t,row,child_row,field) { return "<input type='text' value='"+t.data[row][field]+"' / >" },
    'save': function (t,row,child_row,field) { return $("[row='"+row+"'][child_row='"+child_row+"'][field='"+field+"'] input").val() }
  },

  'iconconfig': {
    'draw': function(t,row,child_row,field) {
      return t.data[row]['#NO_CONFIG#'] ? "" : "<i class='"+t.fields[field].icon+"' type='icon' row='"+row+"' child_row='"+child_row+"' style='cursor:pointer'></i>";
    }
  },

  'size': {
    'draw': function (t,row,child_row,field) { return list_format_size(t.data[row][field]); }
  },

  'group-iconbasic': {
    'draw': function(t,group,rows,field)
    {
      return "<i class='"+t.groupfields[field].icon+"' type='icon' group='"+group+"' rows='"+rows+"' style='cursor:pointer'></i>";
    }
  },

  'group-size': {
    'draw': function(t,group,rows,field) {
      var sum = 0;
      for (i in rows) {
        var row=rows[i];
        if ($.isNumeric(t.data[row][field])) {
          sum = sum + (1*t.data[row][field]); 
        }
      }
      return list_format_size(sum);
    }
  },

  'group-updated': {
    'draw': function(t,group,rows,field) {
      var lastupdate = 0;
      for (i in rows) {
        var row=rows[i];
        if ($.isNumeric(t.data[row][field])) {
          var update = (1*t.data[row][field]);
          if (update > lastupdate) lastupdate = update;
        }
      }
      return list_format_updated(lastupdate);
    }
  },

  'group-processlist': {
    'draw': function(t,group,rows,field) {
      var out = "";
      for (i in rows) {
        var row=rows[i];
        var processlist = t.data[row][field];
        if (processlist_ui != undefined) out+= processlist_ui.group_drawerror(processlist);
        if (out != "") return out;
      }
      return out;
    }
  }
}


// Calculate and color updated time
function list_format_updated(time) {
  time = time * 1000;
  var servertime = (new Date()).getTime() - table.timeServerLocalOffset;
  var update = (new Date(time)).getTime();

  var secs = (servertime-update)/1000;
  var mins = secs/60;
  var hour = secs/3600;
  var day = hour/24;

  var updated = secs.toFixed(0) + "s";
  if ((update == 0) || (!$.isNumeric(secs))) updated = "n/a";
  else if (secs< 0) updated = secs.toFixed(0) + "s"; // update time ahead of server date is signal of slow network
  else if (secs.toFixed(0) == 0) updated = "now";
  else if (day>7) updated = "inactive";
  else if (day>2) updated = day.toFixed(1)+" days";
  else if (hour>2) updated = hour.toFixed(0)+" hrs";
  else if (secs>180) updated = mins.toFixed(0)+" mins";

  secs = Math.abs(secs);
  var color = "rgb(255,0,0)";
  if (secs<25) color = "rgb(50,200,50)"
  else if (secs<60) color = "rgb(240,180,20)"; 
  else if (secs<(3600*2)) color = "rgb(255,125,20)"

  return "<span style='color:"+color+";'>"+updated+"</span>";
}

// Format value dynamically 
function list_format_value(value) {
  if (value == null) return 'NULL';
  value = parseFloat(value);
  if (value>=1000) value = parseFloat((value).toFixed(0));
  else if (value>=100) value = parseFloat((value).toFixed(1));
  else if (value>=10) value = parseFloat((value).toFixed(2));
  else if (value<=-1000) value = parseFloat((value).toFixed(0));
  else if (value<=-100) value = parseFloat((value).toFixed(1));
  else if (value<10) value = parseFloat((value).toFixed(2));
  return value;
}

function list_format_size(bytes) {
  if (!$.isNumeric(bytes)) {
    return "n/a";
  } else if (bytes<1024) {
    return bytes+"B";
  } else if (bytes<1024*100) {
    return (bytes/1024).toFixed(1)+"KB";
  } else if (bytes<1024*1024) {
    return Math.round(bytes/1024)+"KB";
  } else if (bytes<=1024*1024*1024) {
    return Math.round(bytes/(1024*1024))+"MB";
  } else {
    return (bytes/(1024*1024*1024)).toFixed(1)+"GB";
  }
}
