/*
  table.js is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/
var customtablefields = {
  'icon': {
    'draw': function(row,field) {
      if (table.data[row][field] == true) return "<i class='"+table.fields[field].trueicon+"' type='input' style='cursor:pointer'></i>";
      if (table.data[row][field] == false) return "<i class='"+table.fields[field].falseicon+"' type='input' style='cursor:pointer'></i>";
    },

    'event': function() {
      // Event code for clickable switch state icon's
      $(table.element).on('click', 'i[type=input]', function() {
        var row = $(this).parent().attr('row');
        var field = $(this).parent().attr('field');
        if (!table.data[row]['#READ_ONLY#']) {
          table.data[row][field] = !table.data[row][field];

          var fields = {};
          fields[field] = table.data[row][field];

          $(table.element).trigger("onSave",[table.data[row]['id'],fields]);
          if (table.data[row][field]) $(this).attr('class', table.fields[field].trueicon); else $(this).attr('class', table.fields[field].falseicon);
          table.draw();
        }
      });
    }
  },

  'updated': {
    'draw': function (row,field) { return list_format_updated(table.data[row][field]) }
  },

  'value': {
    'draw': function (row,field) { return list_format_value(table.data[row][field]) }
  },

  'processlist': {
    'draw': function (row,field) { 
      var processlist = table.data[row][field];
      if (processlist_ui != undefined) return processlist_ui.drawpreview(processlist);
      else return "";
    }
  },

  'iconlink': {
    'draw': function (row,field) { 
      var icon = 'icon-eye-open'; if (table.fields[field].icon) icon = table.fields[field].icon;
      return "<a href='"+table.fields[field].link+table.data[row]['id']+"' ><i class='"+icon+"' ></i></a>" 
    }
  },

  'iconbasic': {
    'draw': function(row,field)
    {
      return "<i class='"+table.fields[field].icon+"' type='icon' row='"+row+"' style='cursor:pointer'></i>";
    }
  },

  'hinteditable': {
    'draw': function (row,field) { return "…";},
    'edit': function (row,field) { return "<input type='text' value='"+table.data[row][field]+"' / >" },
    'save': function (row,field) { return $("[row="+row+"][field="+field+"] input").val() }
  },

  'iconconfig': {
    'draw': function(row,field)
    {
      return table.data[row]['#NO_CONFIG#'] ? "" : "<i class='"+table.fields[field].icon+"' type='icon' row='"+row+"' style='cursor:pointer'></i>";
    }
  },

  'size': {
    'draw': function (row,field) { return list_format_size(table.data[row][field]); }
  },

  'group-iconbasic': {
    'draw': function(group,rows,field)
    {
      return "<i class='"+table.groupfields[field].icon+"' type='icon' group='"+group+"' rows='"+rows+"' style='cursor:pointer'></i>";
    }
  },

  'group-size': {
    'draw': function(group,rows,field)
    {
      var sum = 0;
      for (i in rows) {
        var row=rows[i];
        if ($.isNumeric(table.data[row][field])) {  
          sum = sum + (1*table.data[row][field]); 
        }
      }
      return list_format_size(sum);
    }
  },

  'group-updated': {
    'draw': function(group,rows,field)
    {
      var lastupdate = 0;
      for (i in rows) {
        var row=rows[i];
        if ($.isNumeric(table.data[row][field])) {
          var update = (1*table.data[row][field]);
          if (update > lastupdate) lastupdate = update;
        }
      }
      return list_format_updated(lastupdate);
    }
  },

  'group-processlist': {
    'draw': function(group,rows,field)
    {
      var out = "";
      for (i in rows) {
        var row=rows[i];
        var processlist = table.data[row][field];
        if (processlist_ui != undefined) out+= processlist_ui.group_drawerror(processlist);
        if (out != "") return out;
      }
      return out;
    }
  }
}


// Calculate and color updated time
function list_format_updated(time){
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
function list_format_value(value){
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

function list_format_size(bytes){
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