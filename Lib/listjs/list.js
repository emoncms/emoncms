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
    'timezones':{},

    'init':function()
    {
        var table = $('<table class="table table-hover" />'),
            tr;
        for (field in list.fields) {
            tr = $("<tr />").attr("field", field);
            tr.append('  <td type="name" class="muted" style="width:150px;">'+list.fields[field].title+'</td>');
            tr.append('  <td type="value">'+(list.fieldtypes[list.fields[field].type].draw(list.data[field])||'N/A')+'</td>');
            tr.append('  <td type="edit" action="edit"><i class="icon-pencil" style="display:none"></i></td>');
            table.append(tr);
        }
        $(list.element).html(table);

        $(list.element+" td[type=edit]").click(function() {
            var action = $(this).attr('action');
            var field = $(this).parent().attr('field');

            if (action=='edit')
            {
              $(list.element+" tr[field="+field+"] td[type=value]").html(list.fieldtypes[list.fields[field].type].edit(field,list.data[field]));
              $(this).html("<a>Save</a>").attr('action','save');
            }

            if (action=='save')
            {
              list.data[field] = list.fieldtypes[list.fields[field].type].save(field);
              $(list.element+" tr[field="+field+"] td[type=value]").html(list.fieldtypes[list.fields[field].type].draw(list.data[field]));
              $(this).html("<i class='icon-pencil' style='display:none'></i>").attr('action','edit');
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
          'draw':function(value) { return value; },
          'edit':function(field,value) { return "<input type='text' value='"+(value||'')+"' / >"; },
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

        'language':
        {
          'draw':function(value) { 
            for (i in list.fields['language'].options)
            {
              if (list.fields['language'].options[i] == value) return list.fields['language'].label[i];
            } 
          },
          'edit':function(field,value) 
          {
            var options = '';
            for (i in list.fields[field].options)
            {
              var selected = ""; if (list.fields[field].options[i] == value) selected = 'selected';
              options += "<option value="+list.fields[field].options[i]+" "+selected+">"+list.fields[field].label[i]+"</option>";
            }
            return "<select>"+options+"</select>";
          },
          'save':function(field) { return $(list.element+' tr[field='+field+'] td[type=value] select').val();}
        },

        'timezone':
        {
          'draw':function(value) 
          { 
            return value;
          },
          'edit':function(field,value) 
          {
            var select = $('<select />'),
                selectedIndex = null;
                
            for (i in list.timezones) {
              var tz = list.timezones[i];
              var selected = ""; 
              if (value == tz.id) {
                selected = 'selected';
                selectedIndex = tz.id;
              }
              select.append("<option value="+tz.id+" "+selected+">"+tz.id+" ("+tz.gmt_offset_text+")</option>");
            }
            //If no selected index were set, then default to UTC
            if ( selectedIndex === null ) {
                select.find("option[value='UTC']").attr('selected', 'selected');
            }
            return select.wrap('<p>').parent().html();  //return HTML-string
          },
          'save':function(field) { return $(list.element+' tr[field='+field+'] td[type=value] select').val();}
        },
 
        'gravatar':
        {
          'draw':function(value) { return "<img style='border: 1px solid #ccc; padding:2px;' src='//www.gravatar.com/avatar/"+CryptoJS.MD5(value)+"'/ >" },
          'edit':function(field,value) { return "<input type='text' value='"+value+"' / >" },
          'save':function(field) { return $(list.element+' tr[field='+field+'] td[type=value] input').val();}
        }
    }
}
