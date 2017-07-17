/*
  table.js is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Part of the OpenEnergyMonitor project: http://openenergymonitor.org
  2016-12-20 - Expanded tables by : Nuno Chaveiro  nchaveiro(a)gmail.com  
*/
var table = {
  'data':[],
  'groupshow':{},

  'eventsadded':false,
  'deletedata':true,

  'sortfield':null,
  'sortorder':null,
  'sortable':true,

  'groupprefix':"",

  'timeServerLocalOffset':0, // offset of server to client time in ms

  'expand':{ default:
                      {'fields':[],
                       'data':[],
                       'groupshow':{},
                       'sortfield':null,
                       'sortorder':null,
                       'sortable':true,
                       'groupprefix':"",
                       'expanded':false
                      }
  },

  'expandByField':"id",

  'draw':function() {
      var html = table.draw_internal(table,"root");
      $(table.element).html("<table class='table table-hover'>"+html+"</table>");
      if (table.eventsadded==false) {table.add_events(); table.eventsadded = true}
      $(table.element).trigger("onDraw");
  },

  'draw_internal':function(t,child_row) {
    /*if (t.data && t.sortable) {
      t.data.sort(function(a,b) {
      if(a[t.sortfield]<b[t.sortfield]) return -1;
      if(a[t.sortfield]>b[t.sortfield]) return 1;
      return 0;
      });
    }*/

    if (t.data && t.sortable && t.sortfield) {
      t.data.sort(function(a,b) {
        var x=a[t.sortfield];
        var y=b[t.sortfield];
        if (x===null)x=Number.POSITIVE_INFINITY;
        if (y===null)y=Number.POSITIVE_INFINITY;
        if ((x==true) || (x== false)) x==false? x=0:x=1;
        if ((y==true) || (y== false)) y==false? y=0:y=1;
        if ($.isNumeric(x) && $.isNumeric(y)){
          var numa=parseFloat(x);
          var numb=parseFloat(y);

          if (t.sortorder==1){
            return numa-numb;
          } else {
            return numb-numa;
          }

        } else {
           if (typeof x == 'string') x=x.toUpperCase().replace(" ", "");
           if (typeof y == 'string') y=y.toUpperCase().replace(" ", "");
        }

        if (t.sortorder==1){
          if(x<y) return -1;
          if(x>y) return 1;
          return 0;
        } else{
          if(x>y) return -1;
          if(x<y) return 1;
          return 0;
        }
      });
    }

    var group_num = 0;
    var groups = {};
    for (row in t.data) {
      var group = t.data[row][t.groupby];
      if (!group) group = 'NoGroup';
      if (!groups[group]) {groups[group] = {}; groups[group]['ui_rows'] = ""; groups[group]['rows_id'] = []; group_num++;}
      groups[group]['ui_rows'] += this.draw_row(t,row,child_row);

      groups[group]['rows_id'].push(row);

	  // Draw expands
      if (t.expand != undefined && t.expandByField != undefined && t.expand[t.data[row][t.expandByField]] != undefined && t.expand[t.data[row][t.expandByField]].expanded) {
            var countFields = 0; for (field in table.fields) countFields++; // Calculate amount of padding required
            // Draw expand placeholder
            var html_expand_place = "<tr uid='"+child_row+"_"+row+"' class='expanded'><td colspan='"+countFields+"'>";
            html_expand_place += "<table class='table table-hover expanded'>" + this.draw_internal(t.expand[t.data[row][t.expandByField]],t.data[row][t.expandByField]) + "</table>"
            html_expand_place += '<\/td></tr>';
            groups[group]['ui_rows'] += html_expand_place;
      }
    }

    var html = "";
    for (group in groups) {
      var visible = '';
      htmlg = "";
      if (group_num>1) {
        var symbol ='<i class="MINMAX icon-minus-sign" child_row="'+child_row+'" group="'+group+'" style="cursor:pointer"></i>'; 
        if (t.groupshow[group]==undefined) t.groupshow[group]=false; // default is collapsed
        if (t.groupshow[group]==false) {symbol = '<i class="MINMAX icon-plus-sign" child_row="'+child_row+'" group="'+group+'" style="cursor:pointer"></i>'; visible = "display:none";}
        htmlg += "<tr><th colspan='3'>"+symbol+" <a class='MINMAX' child_row='"+child_row+"' group='"+group+"' style='cursor:pointer'>"+t.groupprefix+group+"</a></th>";
        var countFields = 0; for (field in t.fields) countFields++; // Calculate amount of padding required
        if (t.groupfields == undefined) {
          for (i=2; i<countFields-1; i++) htmlg += "<th></th>"; // Add th padding
        } else {
          for (fieldg in t.groupfields) htmlg += "<th group='"+group+"' fieldg='"+fieldg+"' >"+this.fieldtypes[t.groupfields[fieldg].type].draw(t,group,groups[group]['rows_id'],fieldg)+"</th>";
        }
        htmlg += "</tr>";
      }
      html += htmlg;

      html += "<tbody id='"+group+"' style='"+visible+"'><tr>";
      for (field in t.fields)
      {
        var title = field; if (t.fields[field].title!=undefined) title = t.fields[field].title;
        html += "<th><a type='sort' child_row='"+child_row+"' field='"+field+"' style='cursor:pointer'>"+title+"</a></th>";
      }
      html += "</tr>";
      html += groups[group]['ui_rows'];
      html += "</tbody>";
    }
    return html;
  },

  'draw_row': function(t,row,child_row) {
    var html = "<tr uid='"+child_row+"_"+row+"'>";
    for (field in t.fields) html += "<td row='"+row+"' child_row='"+child_row+"' field='"+field+"' >"+this.fieldtypes[t.fields[field].type].draw(t,row,child_row,field)+"</td>";
    html += "</tr>";
    return html;
  },

  'update':function(row,child_row,field,value) {
    if (child_row == "root") { t=table; }
    else { t=table.expand[child_row]; }
    t.data[row][field] = value;
    var type = t.fields[field].type;
    if(typeof this.fieldtypes[type].draw === 'function') {
      $("[row='"+row+"'][child_row='"+child_row+"'][field='"+field+"']").html(this.fieldtypes[type].draw(t,row,child_row,field));
    }
  },

  'remove':function(row,child_row) {
    if (child_row == undefined || child_row == "root") { t=table; }
    else { t=table.expand[child_row];  }
    t.data.splice(row,1);
    $("tr[uid='"+child_row+"_"+row+"']").remove();
  },

  'sort':function(t,field,dir) {
    if (t.sortfield == field) {
      t.sortorder = -t.sortorder;
    } else {
      t.sortorder = 1;
    }
    t.sortfield = field;
    // t.sortorder = dir;
    this.draw();
  },

   'add_events':function() {
    // Event: minimise or maximise group
    $(table.element).on('click touchend', '.MINMAX', function(e) {
      e.stopPropagation();
      e.preventDefault();
      var $me=$(this);
      if ($me.data('clicked')){
        $me.data('clicked', false); // reset
        if ($me.data('alreadyclickedTimeout')) clearTimeout($me.data('alreadyclickedTimeout')); // prevent this from happening

        // Do what needs to happen on double click. 
        var group = $(this).attr('group');
        var child_row = $(this).attr('child_row');
        if (child_row == "root") { t=table; }
        else { t=table.expand[child_row];  }
        var state = t.groupshow[group];
        for (gs in t.groupshow) { t.groupshow[gs] = !state; }
        table.draw();
      } else {
        $me.data('clicked', true);
        var alreadyclickedTimeout=setTimeout(function(){
        $me.data('clicked', false); // reset when it happens

        // Do what needs to happen on single click. Use $me instead of $(this) because $(this) is  no longer the element
         var group = $me.attr('group');
         var child_row = $me.attr('child_row');
         if (child_row == "root") { t=table; }
         else { t=table.expand[child_row];  }
         var state = t.groupshow[group];
         t.groupshow[group] = !state;
         table.draw();
        },250); // dblclick tolerance
        $me.data('alreadyclickedTimeout', alreadyclickedTimeout); // store this id to clear if necessary
      }
    });

    // Event: sort by field
    $(table.element).on('click', 'a[type=sort]', function() {
      var field = $(this).attr('field');
      var child_row = $(this).attr('child_row');
      if (child_row == "root") { t=table; }
      else { t=table.expand[child_row];  }
      table.sort(t,field,1);
      console.log(field);
    });

    // Event: delete row
    $(table.element).on('click', 'a[type=delete]', function() {
      var child_row = $(this).attr('child_row');
      if (child_row == "root") { t=table; }
      else { t=table.expand[child_row];  }
      if (table.deletedata) table.remove($(this).attr('row'), $(this).attr('child_row') );
      if (child_row == "root") {
        $(table.element).trigger("onDelete",[$(this).attr('uid'),$(this).attr('row'), $(this).attr('child_row')]); // Only called for root table (to keep compatibility)
      }
      $(table.element).trigger("onDeleteExpand",[$(this).attr('uid'),$(this).attr('row'), $(this).attr('child_row')]);  // If your code has an expand table use this instead of onDelete
    });

    // Event: inline edit
    $(table.element).on('click', 'a[type=edit]', function() {
      var mode = $(this).attr('mode');
      var row = $(this).attr('row');
      var child_row = $(this).attr('child_row');
      var uid = $(this).attr('uid');
      if (child_row == "root") { t=table; }
      else { t=table.expand[child_row];  }

      // Trigger events
      if (mode=='edit') $(table.element).trigger("onEdit");

      var fields_to_update = {};

      for (field in t.fields) {
        var type = t.fields[field].type;

        if (mode == 'edit' && typeof table.fieldtypes[type].edit === 'function') {
          $("[row='"+row+"'][child_row='"+child_row+"'][field='"+field+"']").html(table.fieldtypes[type].edit(t,row,child_row,field));
        }
        if (mode == 'save' && typeof table.fieldtypes[type].save === 'function') {
          var value = table.fieldtypes[type].save(t,row,child_row,field);
          if (t.data[row][field] != value) fields_to_update[field] = value; // only update db if value has changed
          table.update(row,child_row,field,value);  // but update html table because this reverts back from <input>   
        }
      }

      // Call onSave event only if there are fields to be saved
      if (mode == 'save' && !$.isEmptyObject(fields_to_update)) {
        if (child_row == "root") {
            $(table.element).trigger("onSave",[uid,fields_to_update]); // Only called for root table (to keep compatibility)
        }
        $(table.element).trigger("onSaveExpand",[uid,fields_to_update,row,child_row]);  // If your code has an expand table use this instead on onSave
        if (fields_to_update[t.groupby]!=undefined) t.draw();
      }

      if (mode == 'edit') {$(this).attr('mode','save'); $(this).html("<i class='icon-ok' style='cursor:pointer'></i>");}
      if (mode == 'save') {$(this).attr('mode','edit'); $(this).html("<i class='icon-pencil' style='cursor:pointer'></i>"); $(table.element).trigger("onResume");}
    });


    // Event: inline expand
    $(table.element).on('click', 'a[type=expand]', function() {
      var mode = $(this).attr('mode');
      var row = $(this).attr('row');
      var uid = $(this).attr('uid');
      var child_row = $(this).attr('child_row');
      if (child_row == "root") { t=table; }
      else { t=table.expand[child_row];  }

      if ( t.expand[uid] == undefined) {
          t.expand[uid]= $.extend(true, [], table.expand["default"]); // clone from default
          t.expand[uid].expanded = true;
      }

      var tr = $(this).closest('tr');

      if (mode == 'close') {
        t.expand[uid].expanded = false;
        tr.next().remove();
        $(table.element).trigger("onClose",[uid,row,child_row]);
        $(this).attr('mode','expand');
        $(this).html("<i class='icon-chevron-down' style='cursor:pointer'></i>");
      }
      else if (mode == 'expand') {
        t.expand[uid].expanded = true;
        var fields_count = 0;
        for (field in table.fields) { fields_count++; }
        tr.after('<tr row="'+row+'" child_row="'+child_row+'" class="expanded"><td colspan="'+fields_count+'">loading...<\/td></tr>');
        $(table.element).trigger("onExpand",[uid,row,child_row]); 
        $(this).attr('mode','close'); 
        $(this).html("<i class='icon-chevron-up' style='cursor:pointer'></i>"); 
      }
    });

    // Check if events have been defined for field types.
    for (i in this.fieldtypes) {
      if (typeof this.fieldtypes[i].event === 'function') this.fieldtypes[i].event();
    }
  },

  // Field type space
  'fieldtypes': {
    'fixed': {
      'draw': function (t,row,child_row,field) { return t.data[row][field] }
    },

    'text': {
      'draw': function (t,row,child_row,field) { return t.data[row][field] },
      'edit': function (t,row,child_row,field) { return "<input type='text' value='"+t.data[row][field]+"' / >" },
      'save': function (t,row,child_row,field) { return $("[row='"+row+"'][child_row='"+child_row+"'][field='"+field+"'] input").val() },
    },

    'textlink': {
      'draw': function (t,row,child_row,field) { return "<a href='"+t.fields[field].link+t.data[row]['id']+"' >"+t.data[row][field]+"</a>" },
      'edit': function (t,row,child_row,field) { return "<input type='text' style='width:120px' value='"+t.data[row][field]+"' / >" },
      'save': function (t,row,child_row,field) { return $("[row='"+row+"'][child_row="+child_row+"][field='"+field+"'] input").val() },
    },

    'select': {
      'draw': function (t,row,child_row,field) { return t.fields[field].options[t.data[row][field]] },
      'edit': function (t,row,child_row,field) {
        var options = "";
        for (option in t.fields[field].options) {
          var selected = ''; if (option==t.data[row][field]) selected = 'selected';
          options += "<option value='"+option+"' "+selected+" >"+t.fields[field].options[option]+"</option>";
        }
        return "<select style='width:120px'>"+options+"</select>";
      },
      'save': function (t,row,child_row,field) { return $("[row='"+row+"'][child_row='"+child_row+"'][field='"+field+"'] select").val() },
    },

    'fixedselect': {
      'draw': function (t,row,child_row,field) { return t.fields[field].options[t.data[row][field]] }
    },

    'checkbox': {
      'draw': function (t,row,child_row,field) { return t.data[row][field] },
      'edit': function (t,row,child_row,field) { return "<input type='checkbox'>" },
      'save': function (t,row,child_row,field) { return $("[row='"+row+"'][child_row='"+child_row+"'][field='"+field+"'] input").prop('checked') },
    },
    
    'multiselect': {
      'draw': function (t,row,child_row,field) { return "<input type='checkbox'>" },
    },

    'delete': {
      'draw': function (t,row,child_row,field) { return t.data[row]['#READ_ONLY#'] ? "" : "<a type='delete' row='"+row+"' child_row='"+child_row+"' uid='"+t.data[row]['id']+"' ><i class='icon-trash' style='cursor:pointer'></i></a>"; }
    },

    'edit': {
      'draw': function (t,row,child_row,field) { return t.data[row]['#READ_ONLY#'] ? "" : "<a type='edit' row='"+row+"' child_row='"+child_row+"' uid='"+t.data[row]['id']+"' mode='edit'><i class='icon-pencil' style='cursor:pointer'></i></a>"; },
    },
    
    'expand': {
      'draw': function (t,row,child_row,field) { return "<a type='expand' row='"+row+"' child_row='"+child_row+"' uid='"+t.data[row][t.expandByField]+"' mode='" + (t.expand[t.data[row][t.expandByField]] == undefined || !t.expand[t.data[row][t.expandByField]].expanded ? "expand":"close") + "'><i class='" + (t.expand[t.data[row][t.expandByField]] == undefined || !t.expand[t.data[row][t.expandByField]].expanded ? "icon-chevron-down":"icon-chevron-up") + "' style='cursor:pointer'></i></a>"; },
    },
    
    'blank': {
      'draw': function (t,row,child_row,field) { return ""; }
    }
  }
}
