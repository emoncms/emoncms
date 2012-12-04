function stack_widgetlist()
{
  var widgets = {
    "stack": 
    {
      "offsetx":-50,"offsety":-200,"width":100,"height":440,
      "menu":"Widgets"
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
    var id = "can-"+$(this).attr("id");
    draw_stacks(stacks,id,300,460);
  });
}

function stack_slowupdate()
{
}

function stack_fastupdate()
{
}


