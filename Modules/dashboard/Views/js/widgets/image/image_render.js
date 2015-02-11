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
 
function image_widgetlist(){
	var widgets = {};
		for (i = 0; i < imageArray.length; i++) { 
			var widgetname = imageArray[i].substring(imageArray[i].lastIndexOf("/")+1,imageArray[i].length-4);
			widgets[widgetname] = 
				{ "offsetx":0,"offsety":0,"width":300,"height":300,
				  "menu":"Image",
				  "options":["html"],
				  "optionstype":["html"],
				  "optionsname":["html"],
				  "optionshint":["html"],
				  "html": ["<img src="+path+imageArray[i]+" style='width: 100%; height: 100%'/>"],
				  }

	}
		
	  return widgets;
	}

	function image_init()
	{
	}


	function image_slowupdate()
	{
	  // Are these supposed to be empty?
	}

	function image_fastupdate()
	{

	}


