function dial_widgetlist()
{
  var widgets = {
    "dial":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":["feed","max","scale","units","type"],
      "optionstype":["feed","value","value","value","value"]
    }
  }
  return widgets;
}

function dial_init()
{
  setup_widget_canvas('dial');
}

function dial_draw()
{
  $('.dial').each(function(index)
  {
    var feed = $(this).attr("feed");
    var val = curve_value(feed,dialrate);
    // ONLY UPDATE ON CHANGE
    if ((val * 1).toFixed(2) != (assoc[feed] * 1).toFixed(2) || redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      draw_gauge(widgetcanvas[id],0,0,$(this).width(),$(this).height(),val.toFixed(1)*scale, $(this).attr("max"), $(this).attr("units"),$(this).attr("type"));
    }
  });
}

function dial_slowupdate()
{

}

function dial_fastupdate()
{
  dial_draw();
}



  function draw_gauge(ctx,x,y,width,height,position,maxvalue,units,type)
  {
    if (!ctx) return; 
    maxvalue = 1 * maxvalue || 3000;
    units = units || "";
    var size = 0; if (width<height) size = width/2; else size = height/2;
    size = size - (size*0.058/2);
    x = width/2; y = height/2;

    ctx.clearRect(0,0,200,200);

    if (!position) position = 0;

    var offset = 0;
    var segment = ["#c0e392","#9dc965","#87c03f","#70ac21","#378d42","#046b34"];

    var type = type || 0;

    if (type == 0)
    {
      if (position<0) position = 0;
    }
    else if (type == 1)
    {
      offset = -0.75;
      segment = ["#e61703","#ff6254","#ffa29a","#70ac21","#378d42","#046b34"];
    }
    else if (type == 2)
    {
      segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
    }
    else if (type == 3)
    {
      offset = -0.75;
      segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
    }
    else if (type == 4)
    {
      segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
    }
    else if (type == 5)
    {
      offset = -0.75;
      segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
    }
    else if (type == 6)
    {
      offset = -0.75;
      segment = ["#f46722","#f8a01b","#87c03f","#87c03f","#f8a01b","#f46722"];
    }
    else if (type == 7)
    {
      offset = 0;
      segment = ["#a7cbe2","#68b7eb","#0d97f3","#0f81d0","#0c6dae","#08578e"];
    }
    else if (type == 8)  //temperature dial blue-red, first segment blue should mean below freezing C
    {
      offset = -0.25;
      segment = ["#b7beff","#ffd9d9","#ffbebe","#ff9c9c","#ff6e6e","#ff3d3d"];
    }
    else if (type == 9)  //temperature dial blue-red, first segment blue should mean below freezing C
    {
      offset = 0;
      segment = ["#e94937","#da4130","#c43626","#ad2b1c","#992113","#86170a"];
    }

    if (position>maxvalue) position = maxvalue;
    var a = 1.75 - ((position/maxvalue) * 1.5) + offset;

  var c=3*0.785;
  var width = 0.785; 
  var pos = 0; 
  var inner = size * 0.48;

  // Segments
  for (z in segment)
  {
    ctx.fillStyle = segment[z];
    ctx.beginPath();
    ctx.arc(x,y,size,c+pos,c+pos+width+0.01,false);
    ctx.lineTo(x,y); 
    ctx.closePath();
    ctx.fill();
    pos += width;
  }
  pos -= width;
  ctx.lineWidth = (size*0.058).toFixed(0);
  pos += width;
  ctx.strokeStyle = "#fff";
  ctx.beginPath();
  ctx.arc(x,y,size,c,c+pos,false);
  ctx.lineTo(x,y); 
  ctx.closePath();
  ctx.stroke();

  ctx.fillStyle = "#666867";
  ctx.beginPath();
  ctx.arc(x,y,inner,0,Math.PI*2,true);
  ctx.closePath();
  ctx.fill();

  ctx.lineWidth = (size*0.052).toFixed(0);
  //---------------------------------------------------------------
  ctx.beginPath();
  ctx.moveTo(x+Math.sin(Math.PI*a-0.2)*inner,y+Math.cos(Math.PI*a-0.2)*inner); 
  ctx.lineTo(x+Math.sin(Math.PI*a)*size,y+Math.cos(Math.PI*a)*size); 
  ctx.lineTo(x+Math.sin(Math.PI*a+0.2)*inner,y+Math.cos(Math.PI*a+0.2)*inner); 
  ctx.arc(x,y,inner,1-(Math.PI*a-0.2),1-(Math.PI*a+5.4),true);
  ctx.closePath();
  ctx.fill();
  ctx.stroke();
  
  //---------------------------------------------------------------

  ctx.fillStyle = "#fff";
  ctx.textAlign    = "center";
  ctx.font = "bold "+(size*0.28)+"px arial";
  if (position>10) position = position.toFixed(0); else position = position.toFixed(1);
  ctx.fillText(position+units,x,y+(size*0.125));

  }


