/*

  table.js is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
 
*/

var table = {

    'data':0,
    'groupshow':{},

    'eventsadded':false,
    'deletedata':true,

    'sortfield':null,
    'sortorder':null,
    'sortable':true,
    
    'groupprefix':"",
     
    'draw':function()
    {
        /*if (table.data && table.sortable) {
          table.data.sort(function(a,b) {
            if(a[table.sortfield]<b[table.sortfield]) return -1;
            if(a[table.sortfield]>b[table.sortfield]) return 1;
            return 0;
          });
        }
        */
        
        if (table.data && table.sortable && table.sortfield) 
        {
            table.data.sort(function(a,b) {
                var x=a[table.sortfield];
                var y=b[table.sortfield];
                if (x===null)x=Number.POSITIVE_INFINITY;
                if (y===null)y=Number.POSITIVE_INFINITY;
                if ((x==true) || (x== false)) x==false? x=0:x=1;
                if ((y==true) || (y== false)) y==false? y=0:y=1;
                if ($.isNumeric(x) && $.isNumeric(y)){
                    var numa=parseFloat(x);
                    var numb=parseFloat(y);
                    
                    if (table.sortorder==1){
                        return numa-numb;
                    } else {
                        return numb-numa;
                    }

                } else {
                     if (typeof x == 'string') x=x.toUpperCase().replace(" ", "");
                     if (typeof y == 'string') y=y.toUpperCase().replace(" ", "");
                }

                if (table.sortorder==1){
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
        for (row in table.data)
        {
            var group = table.data[row][table.groupby];
            if (!group) group = 'NoGroup';
            if (!groups[group]) {groups[group] = ""; group_num++;}
            groups[group] += table.draw_row(row);
        }

        var html = "";
        for (group in groups) 
        {
            // Minimized group persistance, see lines: 4,92,93
            var visible = '', symbol ='<i class="icon-minus-sign"></i>'; 
            if (table.groupshow[group]==undefined) table.groupshow[group]=true;
            if (table.groupshow[group]==false) {symbol = '<i class="icon-plus-sign"></i>'; visible = "display:none";}

            if (group_num>1) {
              html += "<tr><th colspan='2'><a class='MINMAX' group='"+group+"' >"+symbol+"</a> "+table.groupprefix+group+"</th>";
              var count = 0; for (field in table.fields) count++;   // Calculate amount of padding required
              for (i=1; i<count-1; i++) html += "<th></th>";          // Add th padding
              html += "</tr>";
            }

            html += "<tbody id='"+group+"' style='"+visible+"'><tr>";
            for (field in table.fields)
            {
              var title = field; if (table.fields[field].title!=undefined) title = table.fields[field].title;
              html += "<th><a type='sort' field='"+field+"'>"+title+"</a></th>";
            }
            html += "</tr>";
            html += groups[group];
            html += "</tbody>";
        }

        $(table.element).html("<table class='table table-hover'>"+html+"</table>");

        if (table.eventsadded==false) {table.add_events(); table.eventsadded = true}
        
        $(table.element).trigger("onDraw");
    },

    'draw_row': function(row)
    {
        var html = "<tr uid='"+row+"' >";
        for (field in table.fields) html += "<td row='"+row+"' field='"+field+"' >"+table.fieldtypes[table.fields[field].type].draw(row,field)+"</td>";
        html += "</tr>";
        return html;
    },
        
    'update':function(row,field,value)
    {
        table.data[row][field] = value;
        var type = table.fields[field].type;
        if(typeof table.fieldtypes[type].draw === 'function') {
          $("[row="+row+"][field="+field+"]").html(table.fieldtypes[type].draw(row,field));
        }
    },
  
    'remove':function(row)
    {
        table.data.splice(row,1);
        $("tr[uid="+row+"]").remove();
    },

    'sort':function(field,dir)
    {
        if (table.sortfield == field) {
            table.sortorder = -table.sortorder;
        } else {
            table.sortorder = 1;
        }
        table.sortfield = field;
        // table.sortorder = dir;
        table.draw();
    },

   'add_events':function()
    {
        // Event: minimise or maximise group
        $(table.element).on('click', '.MINMAX', function() {
            var group = $(this).attr('group');
            var state = table.groupshow[group];
            if (state == true) { $("#"+group).hide(); $(this).html('<i class="icon-plus-sign"></i>'); table.groupshow[group] = false; }
            if (state == false) { $("#"+group).show(); $(this).html('<i class="icon-minus-sign"></i>'); table.groupshow[group] = true; }
        });

        // Event: sort by field
        $(table.element).on('click', 'a[type=sort]', function() {
            var field = $(this).attr('field');
            table.sort(field,1);
            console.log(field);
        });

        // Event: delete row
        $(table.element).on('click', 'a[type=delete]', function() {
            if (table.deletedata) table.remove( $(this).attr('row') );
            $(table.element).trigger("onDelete",[$(this).attr('uid'),$(this).attr('row')]);
        });

        // Event: inline edit
        $(table.element).on('click', 'a[type=edit]', function() {
            var mode = $(this).attr('mode');
            var row = $(this).attr('row');
            var uid = $(this).attr('uid');

            // Trigger events
            if (mode=='edit') $(table.element).trigger("onEdit");

            var fields_to_update = {};

            for (field in table.fields) 
            {
                var type = table.fields[field].type;

                if (mode == 'edit' && typeof table.fieldtypes[type].edit === 'function') {
                    $("[row="+row+"][field="+field+"]").html(table.fieldtypes[type].edit(row,field));
                }

                if (mode == 'save' && typeof table.fieldtypes[type].save === 'function') {
                  var value = table.fieldtypes[type].save(row,field);
                  if (table.data[row][field] != value) fields_to_update[field] = value;	// only update db if value has changed
                  table.update(row,field,value); 	// but update html table because this reverts back from <input>		
                }
            }

            // Call onSave event only if there are fields to be saved
            if (mode == 'save' && !$.isEmptyObject(fields_to_update))
            {
              $(table.element).trigger("onSave",[uid,fields_to_update]);
              if (fields_to_update[table.groupby]!=undefined) table.draw();
            }

            if (mode == 'edit') {$(this).attr('mode','save'); $(this).html("<i class='icon-ok' ></i>");}
            if (mode == 'save') {$(this).attr('mode','edit'); $(this).html("<i class='icon-pencil' ></i>");}
        });

        // Check if events have been defined for field types.
        for (i in table.fieldtypes)
        {
            if (typeof table.fieldtypes[i].event === 'function') table.fieldtypes[i].event();
        }
    },

    /*

    Field type space
 
    */
  
    'fieldtypes':
    {
        'fixed':
        {
            'draw': function (row,field) { return table.data[row][field] }
        },

        'text':
        {
            'draw': function (row,field) { return table.data[row][field] },
            'edit': function (row,field) { return "<input type='text' style='width:120px' value='"+table.data[row][field]+"' / >" },
            'save': function (row,field) { return $("[row="+row+"][field="+field+"] input").val() },
        },

        'textlink':
        {
            'draw': function (row,field) { return "<a href='"+table.fields[field].link+table.data[row]['id']+"' >"+table.data[row][field]+"</a>" },
            'edit': function (row,field) { return "<input type='text' style='width:120px' value='"+table.data[row][field]+"' / >" },
            'save': function (row,field) { return $("[row="+row+"][field="+field+"] input").val() },
        },

        'select':
        {
            'draw': function (row,field) { return table.fields[field].options[table.data[row][field]] },
            'edit': function (row,field) { 
                var options = "";
                for (option in table.fields[field].options) 
                {
                  var selected = ''; if (option==table.data[row][field]) selected = 'selected';
                  options += "<option value='"+option+"' "+selected+" >"+table.fields[field].options[option]+"</option>";
                }
                return "<select style='width:120px'>"+options+"</select>";
            },
            'save': function (row,field) { return $("[row="+row+"][field="+field+"] select").val() },
        },
        
        'fixedselect':
        {
            'draw': function (row,field) { return table.fields[field].options[table.data[row][field]] }
        },

        'checkbox':
        {
            'draw': function (row,field) { return table.data[row][field] },
            'edit': function (row,field) { return "<input type='checkbox'>" },
            'save': function (row,field) { return $("[row="+row+"][field="+field+"] input").prop('checked') },
        },

        'delete':
        {
            'draw': function (row,field) { return "<a type='delete' row='"+row+"' uid='"+table.data[row]['id']+"' ><i class='icon-trash' ></i></a>"; }
        },

        'edit':
        {
            'draw': function (row,field) { return "<a type='edit' row='"+row+"' uid='"+table.data[row]['id']+"' mode='edit'><i class='icon-pencil' ></i></a>"; }
        },
    }
}


