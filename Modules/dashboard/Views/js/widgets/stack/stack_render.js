
function stack_widgetlist()
{
  var widgets = {
    "stack": 
    {
      "offsetx":-150,"offsety":-200,"width":300,"height":400,
      "menu":"Widgets",
      "options":["test"]
    }
  }
  return widgets;
}

function stack_init()
{
  setup_widget_canvas('stack');
  stack_draw();
}

function stack_draw()
{
  $('.stack').each(function(index) {

    var stacks = [];

    stacks[0] = {'name':"Average", 'stack':[], 'height':99};
    stacks[0]['stack'][0] = {'kwhd':9, 'color':0, 'name':"Electric" };
    stacks[0]['stack'][1] = {'kwhd':45, 'color':0, 'name':"Heating" }; 
    stacks[0]['stack'][2] = {'kwhd':46, 'color':0, 'name':"Transport" };

    var id = "can-"+$(this).attr("id");
    draw_stacks(stacks,id,300,400);
  });
}

function stack_slowupdate()
{
}

function stack_fastupdate()
{
}


