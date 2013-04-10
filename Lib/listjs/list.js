/*

  list.js is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
 
*/

var list = {

    'data':{},
    'fields':{},
    'element':"#table",

    'init':function()
    {
        var html = '<table class="table table-hover">';
        for (field in list.fields)
        {
          html += '<tr field="'+field+'">';
          html += '  <td type="name" class="muted" style="width:150px;">'+list.fields[field].title+'</td>';
          html += '  <td type="value">'+list.fieldtypes[list.fields[field].type].draw(list.data[field])+'</td>';
          html += '  <td type="edit" action="edit"><i class="icon-pencil" style="display:none"></i></td>';
          html += '</tr>';
        }
        html += '</table>';
        $(list.element).html(html);

        $(list.element+" td[type=edit]").click(function() {
            var action = $(this).attr('action');
            var field = $(this).parent().attr('field');

            if (action=='edit')
            {
              $(list.element+" tr[field="+field+"] td[type=value]").html(list.fieldtypes[list.fields[field].type].edit(field,list.data[field]));
              $(this).html("<a>Save</a>");
              $(this).attr('action','save');
            }

            if (action=='save')
            {
              list.data[field] = list.fieldtypes[list.fields[field].type].save(field);
              $(list.element+" tr[field="+field+"] td[type=value]").html(list.fieldtypes[list.fields[field].type].draw(list.data[field]));
              $(this).html("<i class='icon-pencil' style='display:none'></i>");
              $(this).attr('action','edit');
              $(list.element).trigger("onSave",[]);
            }
        });

        // Show edit button only on hover
        $(list.element+" tr").hover(
          function() {
            $(this).find("td:last > i").show();
          },
          function() {
            $(this).find("td:last > i").hide();
          }
        );
    },

    'fieldtypes': 
    {
        'text':
        {
          'draw':function(value) { return value},
          'edit':function(field,value) { return "<input type='text' value='"+value+"' / >" },
          'save':function(field) { return $(list.element+' tr[field='+field+'] td[type=value] input').val();}
        },

        'select':
        {
          'draw':function(value) { return value },
          'edit':function(field,value) 
          {
            var options = '';
            for (i in list.fields[field].options)
            {
              var selected = ""; if (list.fields[field].options[i] == value) selected = 'selected';
              options += "<option value="+list.fields[field].options[i]+" "+selected+">"+list.fields[field].options[i]+"</option>";
            }
            return "<select>"+options+"</select>";
          },
          'save':function(field) { return $(list.element+' tr[field='+field+'] td[type=value] select').val();}
        },

        'timezone':
        {
          'draw':function(value) { var sign = ''; if (value>=0) sign = '+'; return "UTC "+sign+value+":00"; },
          'edit':function(field,value) 
          {
            var options = '';
            for (var i=-12; i<=14; i++) { 
              var selected = ""; if (value == i) selected = 'selected';
              var sign = ''; if (i>=0) sign = '+';
              options += "<option value="+i+" "+selected+">UTC "+sign+i+":00</option>";
            }
            return "<select>"+options+"</select>";
          },
          'save':function(field) { return $(list.element+' tr[field='+field+'] td[type=value] select').val();}
        },
 
        'gravatar':
        {
          'draw':function(value) { return "<img style='border: 1px solid #ccc; padding:2px;' src='http://www.gravatar.com/avatar/"+CryptoJS.MD5(value)+"'/ >" },
          'edit':function(field,value) { return "<input type='text' value='"+value+"' / >" },
          'save':function(field) { return $(list.element+' tr[field='+field+'] td[type=value] input').val();}
        }
    }
}
