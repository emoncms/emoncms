# Development notes

Notes taken while developing listjs with the view of turning it into a how to build it from scratch type documentation.

## Html

Writing the above table view in html looks like this, I have placed attributes *field* and *type* in the html to make jquery selection easy:

    <table class="table table-hover">

        <tr field="gravatar">
          <td type="name" class="muted" style="width:150px;">gravatar</td>
          <td type="value">
            <img style="border: 1px solid #ccc; padding:2px;" src="http://www.gravatar.com/avatar/92ea3e67eb54ddea8a0abbee3b238076">
          </td>
          <td type="edit" mode="edit"><i class="icon-pencil" style="display:none"></i></td>
        </tr>

        <tr field="name" >
          <td type="name" class="muted" style="width:150px;">name</td>
          <td type="value">Trystan Lea</td>
          <td type="edit" mode="edit"><i class="icon-pencil" style="display:none"></i></td>
        </tr>

        <tr field="location" >
          <td type="name" class="muted" style="width:150px;">location</td>
          <td type="value">North Wales</td>
          <td type="edit" mode="edit"><i class="icon-pencil" style="display:none"></i></td>
        </tr>

        <tr field="timezone">
          <td type="name" class="muted" style="width:150px;">timezone</td>
          <td type="value">UTC +2:00</td>
          <td type="edit" mode="edit"><i class="icon-pencil" style="display:none"></i></td>
        </tr>
        <tr field="language" >
          <td type="name" class="muted" style="width:150px;">language</td>
          <td type="value">English</td>
          <td type="edit" mode="edit"><i class="icon-pencil" style="display:none"></i></td>
        </tr>
        <tr field="bio" >
          <td type="name" class="muted" style="width:150px;">bio</td>
          <td type="value"></td>
          <td type="edit" mode="edit"><i class="icon-pencil" style="display:none"></i></td>
        </tr>

    </table>

We can use jquery selectors to edit the above html:

    $("tr[field="+field+"] td[type=name]").html(field);
    $("tr[field="+field+"] td[type=value]").html(data[field]);

The data object that stores all the data to make this looks like this:

    var data = { 
        gravatar: 'trystan.lea@googlemail.com', 
        name:'Trystan Lea', 
        location: 'North Wales', 
        language:'English', 
        bio:'' 
    };

The first thing to notice is that generating the html from the data object should be pretty straight forward which would allow us to avoid writing all the html out:

    var html = '<table class="table table-hover">';
    for (field in data)
    {
      html += '<tr field="'+field+'">';
      html += '  <td type="name" class="muted" style="width:150px;">'+field+'</td>';
      html += '  <td type="value">'+data[field]+'</td>';
      html += '  <td type="edit" mode="edit"><i class="icon-pencil" style="display:none"></i></td>';
      html += '</tr>';
    }
    html += '</table>';
    $("#table").html(html);

As you can see the gravatar is not displayed using the above code, the user email is just inserted straight without conversion and so instead of just outputing 

    html += '  <td type="value">'+data[field]+'</td>';

we need a way of outputing different things depending on the field type.

The idea that was adopted for table.js was to create a fieldtypes data object that handled what to show on draw, edit and save for each field type.

Here's the draw actions for each datatype we need for the profile view:

    var fieldtypes = {

        'text':
        {
          'draw':function(value) {return value}
        },

        'timezone':
        {
          'draw':function(value) { if (value>=0) sign = '+'; return "UTC "+sign+value+":00"; }
        },
 
        'gravatar':
        {
          'draw':function(value) { return "<img style='border: 1px solid #ccc; padding:2px;' src='http://www.gravatar.com/avatar/"+CryptoJS.MD5(value)+"'/ >" }
        }

    };

We can then in place of:

    html += '  <td type="value">'+data[field]+'</td>';

call 

    html += '  <td type="value">'+fieldtypes[fields[field].type].draw(data[field])+'</td>';

We also need a defenition of the type of each field

    var fields = {
      'gravatar':{'type':'gravatar'},
      'name':{'type':'text'},
      'location':{'type':'text'},
      'timezone':{'type':'timezone'},
      'language':{'type':'text'},
      'bio':{'type':'text'}
    }

We are now ready to implement editing:

### Hover show edit icon

A neat little visual feature:

    // Show edit button only on hover
    $("tr").hover(
      function() {
        $(this).find("td:last > i").show();
      },
      function() {
        $(this).find("td:last > i").hide();
      }
    );

Next we need to create an event that fires when a particular field's edit button is pressed:

    $("td[type=edit]").click(function() {
        var field = $(this).parent().attr('field');
        // Do on edit stuff here to field: field
    });

Lets start by just showing a text box for each option:

    $("tr[field="+field+"] td[type=value]").html("<input type='text' value='"+data[field]+"' / >");

Lets now make use of our field types object and create an edit type for each field type. The timezone edit type requires a drop down select box of all the timezone options.

First we tell our on edit event to get the edit html from the fieldtypes object:

    $("tr[field="+field+"] td[type=value]").html(fieldtypes[fields[field].type].edit(data[field]));

and then we add edit types to the field types object:

    var fieldtypes = {

        'text':
        {
          'draw':function(value) {return value},
          'edit':function(value) { return "<input type='text' value='"+value+"' / >" }
        },

        'timezone':
        {
          'draw':function(value) { if (value>=0) sign = '+'; return "UTC "+sign+value+":00"; },
          'edit':function(value) 
          {
            var options = '';
            for (var i=-12; i<=14; i++) { 
              var selected = ""; if (value == i) selected = 'selected';
              if (i>=0) sign = '+';
              options += "<option value="+i+" "+selected+">UTC "+sign+i+":00</option>";
            }
            return "<select>"+options+"</select>";
          }
        },
 
        'gravatar':
        {
          'draw':function(value) { return "<img style='border: 1px solid #ccc; padding:2px;' src='http://www.gravatar.com/avatar/"+CryptoJS.MD5(value)+"'/ >" },
          'edit':function(value) { return "<input type='text' value='"+value+"' / >" }
        }

    };

## Saving fields

Adding a save button, we want a save button to appear when the edit button has been clicked and the field is in edit mode.

The idea is that the td type edit has a action attribute that we toggle to indicate the action that should happen when we next click on td:

    $("td[type=edit]").click(function() {
        var action = $(this).attr('action');
        var field = $(this).parent().attr('field');

        if (action=='edit')
        {
          $("tr[field="+field+"] td[type=value]").html(fieldtypes[fields[field].type].edit(data[field]));
          $(this).html("<a>Save</a>");
          $(this).attr('action','save');
        }
    });

As in the case of draw and edit were going to use the fieldtype again to determine how to fetch the data from the html for saving:

    var fieldtypes = {

        'text':
        {
          'draw':function(value) { return value},
          'edit':function(value) { return "<input type='text' value='"+value+"' / >" },
          'save':function(field) { return $('tr[field='+field+'] td[type=value] input').val();}
        },

        'timezone':
        {
          'draw':function(value) { if (value>=0) sign = '+'; return "UTC "+sign+value+":00"; },
          'edit':function(value) 
          {
            var options = '';
            for (var i=-12; i<=14; i++) { 
              var selected = ""; if (value == i) selected = 'selected';
              if (i>=0) sign = '+';
              options += "<option value="+i+" "+selected+">UTC "+sign+i+":00</option>";
            }
            return "<select>"+options+"</select>";
          },
          'save':function(field) { return $('tr[field='+field+'] td[type=value] select').val();}
        },
 
        'gravatar':
        {
          'draw':function(value) { return "<img style='border: 1px solid #ccc; padding:2px;' src='http://www.gravatar.com/avatar/"+CryptoJS.MD5(value)+"'/ >" },
          'edit':function(value) { return "<input type='text' value='"+value+"' / >" },
          'save':function(field) { return $('tr[field='+field+'] td[type=value] input').val();}
        }

    };

and then for our save action:

    $("td[type=edit]").click(function() {
        var action = $(this).attr('action');
        var field = $(this).parent().attr('field');

        if (action=='edit')
        {
          $("tr[field="+field+"] td[type=value]").html(fieldtypes[fields[field].type].edit(data[field]));
          $(this).html("<a>Save</a>");
          $(this).attr('action','save');
        }

        if (action=='save')
        {
          data[field] = fieldtypes[fields[field].type].save(field);
          $("tr[field="+field+"] td[type=value]").html(fieldtypes[fields[field].type].draw(data[field]));
          $(this).html("<i class='icon-pencil' style='display:none'></i>");
          $(this).attr('action','edit');
        }
    });

The only thing that's left now is to get the data from the server and save it, but before we do that lets package up the above code in a nice library. Server side interface is separate from the list gui.

## Complete list.js:

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
              html += '  <td type="name" class="muted" style="width:150px;">'+field+'</td>';
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
              'draw':function(value) { if (value>=0) sign = '+'; return "UTC "+sign+value+":00"; },
              'edit':function(field,value) 
              {
                var options = '';
                for (var i=-12; i<=14; i++) { 
                  var selected = ""; if (value == i) selected = 'selected';
                  if (i>=0) sign = '+';
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

## Using it

    <?php global $path; ?>

    <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/user/profile/md5.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/user/list.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js"></script>
    <div class="row">

      <div class="span4">
       <h3>My Account</h3>
      </div>


      <div class="span8">
        <h3>My Profile</h3>
        <div id="table"></div>
      </div>

    </div>

    <script>

        var path = "<?php echo $path; ?>";

        list.data = user.get();

        list.fields = {
          'gravatar':{'type':'gravatar'},
          'name':{'type':'text'},
          'location':{'type':'text'},
          'timezone':{'type':'timezone'},
          'language':{'type':'select', 'options':['English','French','Spanish','German']},
          'bio':{'type':'text'}
        }

        list.init();

        $("#table").bind("onSave", function(e){
          user.set(list.data);
        });
    </script>
