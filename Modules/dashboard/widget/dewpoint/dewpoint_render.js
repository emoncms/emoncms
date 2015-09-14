/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

   Part of the OpenEnergyMonitor project: http://openenergymonitor.org

   Author: Nuno Chaveiro nchaveiro(a)gmail.com
   If you have any questions please get in touch, try the forums here:  http://openenergymonitor.org/emon/forum
 */

// Convenience function for shoving things into the widget object
// I'm not sure about calling optionKey "optionKey", but I don't want to just use "options" (because that's what this whole function returns), and it's confusing enough as it is.
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
    widget["options"    ].push(optionKey);
    widget["optionstype"].push(optionType);
    widget["optionsname"].push(optionName);
    widget["optionshint"].push(optionHint);
    widget["optionsdata"].push(optionData);
}

function dewpoint_widgetlist()
{
    var widgets =
    {
        "dewpoint":
        {
            "offsetx":-40,"offsety":-10,"width":80,"height":20,
            "menu":"Widgets",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };
    
    var tempDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [0,    "ºC"],
        [1,    "ºF"]
    ];
    
    var decimalsDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [-1,   "Automatic"],
        [0,    "0"],
        [1,    "1"],
        [2,    "2"],
        [3,    "3"],
        [4,    "4"],
        [5,    "5"],
        [6,    "6"]
    ];
    
    addOption(widgets["dewpoint"], "feedhumid", "feedid",  _Tr("Humidity"),    _Tr("Relative humidity in %"),          []);
    addOption(widgets["dewpoint"], "feedtemp",  "feedid",  _Tr("Temperature"), _Tr("Temperature feed"),                []);
    addOption(widgets["dewpoint"], "temptype",  "dropbox", _Tr("Temp unit"),   _Tr("Units of the choosen temp feed"),  tempDropBoxOptions);
    addOption(widgets["dewpoint"], "decimals",  "dropbox", _Tr("Decimals"),    _Tr("Decimals to show"),                decimalsDropBoxOptions);
    return widgets;
}


function dewpoint_init(){
	$('.dewpoint').css({
		'font-weight': 'bold',
		'font-size': '16px',
//		'padding-top': '20px',
		'text-align': 'center',
		'color': '#4444CC'
	});
    dewpoint_draw();
}

function dewpoint_slowupdate() { dewpoint_draw();}

function dewpoint_fastupdate() { }

function dewpoint_draw()
{
  $('.dewpoint').each(function(index)
  {
    var feedtemp = $(this).attr("feedtemp");
    if (associd[feedtemp] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var temp = associd[feedtemp]['value'] * 1;
    if (temp==undefined) temp = 0;
    if (isNaN(temp))  temp = 0;
    
    var temptype = $(this).attr("temptype");
    if (temptype==undefined) temptype = 0;

    var feedhumid = $(this).attr("feedhumid");
    if (associd[feedhumid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var humid = associd[feedhumid]['value'] * 1;
    if (humid==undefined) humid = 0;
    if (isNaN(humid))  humid = 0;

    var decimals = $(this).attr("decimals");
    if (decimals==undefined) decimals = -1;

    if (temptype == 1) { 
    temp = (temp - 32) * (5 / 9); // Fahrenheit to celsius
    }
    val = dewPoint(humid,temp);
    if (temptype == 1) {
    val = (val * 9/5 + 32) ; // Celsius to Fahrenheit
    unit = "ºF";
    } else {
    unit = "ºC";
    }

    if (decimals<0)
    {
    if (val>=100)
      val = val.toFixed(0);
    else if (val>=10)
      val = val.toFixed(1);
    else if (val<=-100)
      val = val.toFixed(0);
    else if (val<=-10)
      val = val.toFixed(1);
    else
      val = val.toFixed(2);
    }
    else 
    {
      val = val.toFixed(decimals);
    }

    $(this).html(val + unit);

  });
}



/**
 http://www.e-lab.de/downloads/DOCs/SHT11appnote2.pdf
 Compute dewPoint for given relative humidity RH[%] and temperature T[Deg.C].
 returns : Dew Point Temperaure [0C]
*/
function dewPoint(RH,T) {
  var H = ((Math.log(RH)/Math.LN10)-2)/0.4343 + (17.62*T)/(243.12+T); 
  var dp = 243.12*H/(17.62-H);     // this is the dew point in Celsius
  return dp;
}