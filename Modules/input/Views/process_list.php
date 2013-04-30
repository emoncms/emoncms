<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/
  
global $path, $session;

?>

<div style="float:right;"><a href="../api"><?php echo _("Input API Help") ?></a></div>

<h2><?php echo _('Input configuration:   '); ?><?php echo $inputid; ?></h2>
<p><?php echo _('Input processes are executed sequentially with the result being passed back for further processing by the next processor in the input processing list.'); ?></p>

<div id='inputprocesslist'></div>

    <form action="">
<table class='catlist'>
    <tr><td style='width:15%;'><?php echo _("New"); ?></td>
      <td style='width:35%;'>
      <input type="hidden" name="inputid" value="<?php echo $inputid; ?>">
      <select class="processSelect" name="type" id="type">
        <?php for ($i=1; $i<=count($processlist); $i++) { ?>
        <option value="<?php echo $i; ?>"><?php echo $processlist[$i][0]; ?></option>
        <?php } ?>
      </select></td>
      <td style='width:40%;'><div id="newProcessArgField"></div></td>
    </tr>
    <tr>
      <td></td>
      <td></td>
      <td><input type="submit" value="<?php echo _('add'); ?>" class="button06" id="submit_add" style="width:100px;"/></td>
    </tr>
  </table>
  </form>
  <?php //} ?>

  <form action="<?php echo $path; ?>input/process/reset" method="get">
    <input type="hidden" name="inputid" value="<?php echo $inputid; ?>">
    <input type="submit" value="<?php echo _('Reset process list?'); ?>" class="btn btn-danger"/>
  </form>
  <hr/>

  <?php $name = 'noname'; ?>

  <?php $message = "<h2>" . _("Are you sure you want to delete input: ") . $name . "?</h2>"; ?>

  <form action="<?php echo $path; ?>input/delete" method="get">
    <input type="hidden" name="id" value="<?php echo $inputid; ?>">
    <input type="submit" value="<?php echo _('Delete input?'); ?>" class="btn btn-danger"/>
  </form>

<script type="text/javascript">

var path = "<?php echo $path; ?>";

var processlist = <?php echo json_encode($processlist); ?>;
var feedlist = <?php echo json_encode($feedlist); ?>;
var inputlist = <?php echo json_encode($inputlist); ?>;

function delete_process(inputid, processid)
{
  $.ajax({
    url: path+"input/process/delete.json?inputid="+inputid+"&processid="+processid,
    dataType: 'json',
    success: location.reload()
  })
}

function move_process(inputid, processid, upordown)
{
  $.ajax({
    url: path+"input/process/move.json?inputid="+inputid+"&processid="+processid+"&moveby="+upordown,
    dataType: 'json',
    success: location.reload()
  })  
}

function update_list()
{
  console.log(path+"input/process/list.json?inputid=<?php echo $inputid; ?>");
  $.ajax({
      url: path+"input/process/list.json?inputid=<?php echo $inputid; ?>",
      dataType: 'json',
      async: false,
      success: function(data)
      {
        inputprocesslist = data;
        console.log(inputprocesslist);

        var i = 0;

        var out="<table class='table table-hover'><tr><th style='width:10%;'></th><th style='width:5%;'><?php echo _('Order'); ?></th><th style='width:35%;'><?php echo _('Process'); ?></th><th style='width:40%;'><?php echo _('Arg'); ?></th><th><?php echo _('Actions'); ?></th></tr>";

        for (z in inputprocesslist)
        {
          i++;
          out += '<tr>';                             
          out += '<td>';                   
          
          if (i > 1) {
            out += '<a href="#" title="<?php echo _('Move up'); ?>" onclick="move_process(<?php echo $inputid; ?>,'+i+',-1)" ><i class="icon-arrow-up"></i></a>';           
          } 

          if (i < inputprocesslist.length) {
            out += '<a href="#" title="<?php echo _('Move up'); ?>" onclick="move_process(<?php echo $inputid; ?>,'+i+',1)" ><i class="icon-arrow-down"></i></a>';            
          }

          out += "</td><td>"+i+"</td><td>"+inputprocesslist[z][0]+"</td><td>"+inputprocesslist[z][1]+"</td>";
          out += "<td>";
          out += '<a href="#" title="<?php echo _('Delete'); ?>" onclick="delete_process(<?php echo $inputid; ?>,'+i+')" ><i class="icon-trash"></i></a>';
          out += "</td>";
        }
        
        if (inputprocesslist.length==0) {
          out += "</table><table class='catlist'><tr class='d0' ><td><?php echo _('You have no processes defined'); ?></td></tr>";
        }
        out +="</table>";
        $('#inputprocesslist').html(out);
      }
  });
}
  
function generate_process_arg_box()
{
  var process_id = $('select[name="type"]').val();
  var process = processlist[process_id];

  var out = "";
  if (process[1]==0) // Process type is multiply input by value or apply an offset - the argument is a value
  {
      out += "<input type='text' name='arg' class='processArgBox' id='arg' style='width:100px;'/ >";
  }

  if (process[1]==1) // Process type is multiply, divide by input or add another input - argument type is input 
  {
      out +='<select class="processArgBox" name="arg" id="arg" onChange="update_process_arg_box()" style="width:140px;">'
      for (i in inputlist) out += '<option value="'+inputlist[i].id+'">'+inputlist[i].nodeid+":"+inputlist[i].name+'</option>';
      out +='</select>';
  }

  if (process[1]==2) // Argument type is a feed to log to, or output as a kwhd feed and so on.
  {
      out +='<select class="processArgBox" name="arg" id="arg" onChange="update_process_arg_box()" style="width:140px;">'
      out += '<option value="-1"><?php echo _("CREATE NEW:"); ?></option>';
      for (i in feedlist) out += '<option value="'+feedlist[i].id+'">'+feedlist[i].name+'</option>';
      out +='</select>';
  }

  $('#newProcessArgField').html(out);

  update_process_arg_box();
}

<?php // Add or remove newfeedname text box (for new feed name) if Create New feed is selected ?>
function update_process_arg_box()
{
  if ($('.processArgBox').val() == -1) {
    $('#newProcessArgField').append('<input type="text" name="newfeedname" class="processArgBox2" style="width:100px;" id="newfeedname"/ >');
  }
  else {
    $('#newfeedname').remove();
  }
}

function process_add() {
  var datastring = '?inputid='+<?php echo $inputid; ?>+'&processid='+$('select#type').val()+'&arg='+$('#arg').val();

  if ($('#arg').val() == -1) {
    datastring += '&newfeedname='+$('#newfeedname').val();
    if ($('#newfeedname').val() == '') {
      alert('ERROR: You must enter a feed name!');
      return false;
    }
  }



  $.ajax({
    url: path+"input/process/add.json"+datastring,
    dataType: 'json',
    async: false,
    success: function(data)
    {
      if (data.success == false) alert(data.message);
      update_list();
    }
  });

  return true;
}

$('#submit_add').click(function() {
  if (!process_add()) return false;
  generate_process_arg_box();
  return false;
});

$('.processSelect').change(function() {
  generate_process_arg_box();
});

$('.processArgBox').change(function() {
  update_process_arg_box();
});

$(document).ready(function() {
  update_list();
  generate_process_arg_box();
}); 

</script>
