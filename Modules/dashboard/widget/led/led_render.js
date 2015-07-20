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

function led_widgetlist()
{
  var widgets = {
    "led":
    {
      "offsetx":-40,"offsety":-40,"width":80,"height":80,
      "menu":"Widgets",
      "options":["feed"],
      "optionstype":["feed"],
      "optionsname":[_Tr("Feed")],
      "optionshint":[_Tr("Feed value")]
    }
  }
  return widgets;
}

function led_init()
{
  setup_widget_canvas('led');
}

function led_draw()
{
  $('.led').each(function(index)
  {
    var feed = $(this).attr("feed");
    var val = assoc[feed];
    var id = "can-"+$(this).attr("id");
    if (browserVersion < 9)
      draw_led_ie8(widgetcanvas[id], val);
    else
      draw_led(widgetcanvas[id], val);
  });
}

function led_slowupdate()
{
  led_draw();
}

function led_fastupdate()
{
}

  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

function draw_led(circle,status)
{
    if (!circle) return;
  circle.clearRect(0,0,80,80);

  var radgrad = circle.createRadialGradient(40,40,0,40,40,20);

if (status==0) {                   // red
  radgrad.addColorStop(0, '#F75D59');
  radgrad.addColorStop(0.9, '#C11B17');
} else if (status>0 && status <=1) {            // green
  radgrad.addColorStop(0, '#A7D30C');
  radgrad.addColorStop(0.9, '#019F62');
} else if (status>1 && status <=2) {           // grey
  radgrad.addColorStop(0, '#736F6E');
  radgrad.addColorStop(0.9, '#4A4344');
} else if (status>2 && status <=3) { 		  //Blue
  radgrad.addColorStop(0, '#00C9FF');
  radgrad.addColorStop(0.9, '#00B5E2');
} else if (status>3 && status <=4) {		  // Purple
  radgrad.addColorStop(0, '#FF5F98');
  radgrad.addColorStop(0.9, '#FF0188');
} else if (status>4 && status <=5)   {         // yellow
  radgrad.addColorStop(0, '#F4F201');
  radgrad.addColorStop(0.9, '#E4C700');
} else {					  // Black
  radgrad.addColorStop(0, '#000000');
  radgrad.addColorStop(0.9, '#000000');
}

  radgrad.addColorStop(1, 'rgba(1,159,98,0)');
  // draw shapes
 circle.fillStyle = radgrad;
 circle.fillRect(20,20,60,60);


}



function draw_led_ie8(circle,status)
{
    if (!circle) return;

  if (status==0) {			// red
    circle.fillStyle = "#C11B17";
  } else if (status==1) {			// green
    circle.fillStyle = "#019F62";
  } else if (status==2) {			// grey
    circle.fillStyle = "#4A4344";
  } else if (status==3) {			//Blue
    circle.fillStyle = "#00B5E2";
  } else if (status ==4) {		// Purple
    circle.fillStyle = "#FF0188";
  } else if (status==5)  {		// yellow
    circle.fillStyle = "#E4C700";
  } else {				// Black
    circle.fillStyle = "#000000";
  }

  circle.beginPath();
  circle.arc(25,25,20, 0,Math.PI * 2,false);
  circle.closePath();
  circle.fill()
}

  function draw_binary_led(circle,status)
  {
    if (!circle) return;
    circle.clearRect(0,0,80,80);

  var radgrad = circle.createRadialGradient(40,40,0,40,40,20);

if (status==0) {                               // red
  radgrad.addColorStop(0, '#F75D59');
  radgrad.addColorStop(0.9, '#C11B17');
} else {                                       // green
  radgrad.addColorStop(0, '#A7D30C');
  radgrad.addColorStop(0.9, '#019F62');
}

  radgrad.addColorStop(1, 'rgba(1,159,98,0)');
  // draw shapes
 circle.fillStyle = radgrad;
 circle.fillRect(20,20,60,60);


}

function draw_binary_led_ie8(circle,status)
{
    if (!circle) return;

  if (status==0) {			// red
    circle.fillStyle = "#C11B17";
  } else {			// green
    circle.fillStyle = "#019F62";
  }

  circle.beginPath();
  circle.arc(25,25,20, 0,Math.PI * 2,false);
  circle.closePath();
  circle.fill()
}


