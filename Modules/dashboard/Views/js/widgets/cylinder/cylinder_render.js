/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */


function cylinder_widgetlist()
{
  var widgets = {
    "cylinder":
    {
      "offsetx":-80,"offsety":-165,"width":160,"height":330,
      "menu":"Widgets",
      "options":["topfeed","botfeed"],
      "optionstype":["feed","feed"],
      "optionsname":[_Tr("Feed"),_Tr("Feed")],
      "optionshint":[_Tr("Top feed value"),_Tr("Bottom feed value")]
    }
  }
  return widgets;
}

function cylinder_init()
{
  setup_widget_canvas('cylinder');
}

function cylinder_draw()
{
  $('.cylinder').each(function(index)
  {
    var cyl_top = assoc[$(this).attr("topfeed")]*1;
    var cyl_bot = assoc[$(this).attr("botfeed")]*1;

    var id = "can-"+$(this).attr("id");
    draw_cylinder(widgetcanvas[id],cyl_bot,cyl_top,$(this).width(),$(this).height());
  });
}

function cylinder_slowupdate()
{
  cylinder_draw();
}

function cylinder_fastupdate()
{
}

  function draw_cylinder(ctx,cyl_bot,cyl_top,width,height)
  {

    // console.log("Draw cylinder");
    if (!ctx) console.log("No CTX");
    if (!ctx) return;

    //var width = 168;
    var midx = width / 2;
    var cyl_width = width - 8;
    var cyl_left = midx - (cyl_width/2);
    var top_pos = midx;
    var bh = 28;
    var bot_pos = top_pos + 6 * bh;

    ctx.clearRect(0,0,width,500);
    cyl_top = cyl_top || 0;
    cyl_bot = cyl_bot || 0;
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 8;

    var diff = 1*cyl_top - 1*cyl_bot;
    var step_diff = -diff / 5;
    var step_temp = cyl_top;

    ctx.fillStyle = get_color(step_temp);
    ctx.beginPath();
    ctx.arc(midx,top_pos,cyl_width/2,Math.PI,0,false);
    ctx.closePath();
    ctx.fill();

    var y = top_pos;
    ctx.fillStyle = get_color(step_temp); step_temp += step_diff;
    ctx.fillRect(cyl_left,y,cyl_width,bh); y+=bh;
    ctx.fillStyle = get_color(step_temp); step_temp += step_diff;
    ctx.fillRect(cyl_left,y,cyl_width,bh); y+=bh;
    ctx.fillStyle = get_color(step_temp); step_temp += step_diff;
    ctx.fillRect(cyl_left,y,cyl_width,bh); y+=bh;
    ctx.fillStyle = get_color(step_temp); step_temp += step_diff;
    ctx.fillRect(cyl_left,y,cyl_width,bh); y+=bh;
    ctx.fillStyle = get_color(step_temp); step_temp += step_diff;
    ctx.fillRect(cyl_left,y,cyl_width,bh); y+=bh;
    ctx.fillStyle = get_color(step_temp);
    ctx.fillRect(cyl_left,y,cyl_width,bh); y+=bh;

    ctx.fillStyle = get_color(step_temp);
    ctx.beginPath();
    ctx.arc(midx,bot_pos,cyl_width/2,0,Math.PI,false);
    ctx.closePath();
    ctx.fill();

    ctx.beginPath();
    ctx.arc(midx,top_pos,cyl_width/2,Math.PI,0,false);
    ctx.arc(midx,bot_pos,cyl_width/2,0,Math.PI,false);

    ctx.closePath();
    ctx.stroke();

    ctx.fillStyle = "#fff";
    ctx.textAlign    = "center";
    ctx.font = "bold "+((width/168)*30)+"px arial";
    ctx.fillText(cyl_top.toFixed(1)+"C",midx,top_pos);
    ctx.fillText(cyl_bot.toFixed(1)+"C",midx,bot_pos+15);
  }

  function get_color(temperature)
  {
    var red = (32+(temperature*3.95)).toFixed(0);
    var green = 40;
    var blue = (191-(temperature*3.65)).toFixed(0);
    return "rgb("+red+","+green+","+blue+")";
  }



