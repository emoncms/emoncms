var process_info = {

    '1':"<p><b>Log to feed:</b> This processor logs the current selected input to a timeseries feed which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>Feed engine:</b> The fixed interval with averaging (PHPFIWA) feed engine is the recommended engine to use for logging power, temperature, humidity, voltage and current data. In addition to storing the full resolution data it produces a series of downsampled averaged layers which gives a more accurate representation of the data when viewing the data over a large time range.</p><p><b>Feed interval:</b> When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p><p><b>SD Card use:</b> For SD Card use however its recommended to use the fixed interval without averaging feed engine (PHPFINA) as the number of disk writes per datapoint is much less (although could still be improved), lengthening SD card life.",
    
    '2':"Scale input by value given. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list",
    
    '3':"Offset input by value given. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list",

    '4':"Convert a power value in Watts to a cumulative and ever rising kWh timeseries plot",

    '5':"Convert a power value in Watts to a feed that contains an entry for the total energy used each day (kWh/d)",

    '6':"This multiplies the current selected input with another input as selected from the dropdown menu. The result is passed back for further processing by the next processor in the input processing list.",
    
    '12':"This divides the current selected input with another input as selected from the dropdown menu. The result is passed back for further processing by the next processor in the input processing list.",
    
    '11':"This adds the selected input from the dropdown menu to the current input. The result is passed back for further processing by the next processor in the input processing list.",
    
    '22':"This subtracts the selected input from the dropdown menu from the current input. The result is passed back for further processing by the next processor in the input processing list.",   
    
    '7':"Counts the amount of time that an input is high in each day and logs the result to a feed. Created for counting the number of hours a solar hot water pump is on each day",
    
    '34':"To be used in conjunction with an emontx sending total watt hours elapsed to emoncms. This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 25kW.<br><b>Requires redis installed to work</b>"
}


