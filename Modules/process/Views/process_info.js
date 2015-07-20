var process_info = {

    '1':"<p><b>Log to feed:</b> This processor logs to a timeseries feed which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>Feed engine:</b> The fixed interval with averaging (PHPFIWA) feed engine is the recommended engine to use for logging power, temperature, humidity, voltage and current data. In addition to storing the full resolution data it produces a series of downsampled averaged layers which gives a more accurate representation of the data when viewing the data over a large time range.</p><p><b>Feed interval:</b> When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p>",
    
    '2':"Scale current value by given value. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the processing list",
    
    '3':"Offset current value by given value. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the processing list",

    '4':"Convert a power value in Watts to a cumulative and ever rising kWh timeseries plot",

    '5':"Convert a power value in Watts to a feed that contains an entry for the total energy used each day (kWh/d)",

    '6':"Multiplies the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.",
    
    '12':"Divides the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.",
    
    '11':"Adds the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.",
    
    '22':"Subtracts the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.",
    
    '14':"Output feed accumulates by input value",  

    '15':"Output feed is the difference between the current value and the last",   
    
    '7':"Counts the amount of time that an input is high in each day and logs the result to a feed. Created for counting the number of hours a solar hot water pump is on each day",
    
    '34':"To be used in conjunction with an emontx sending total watt hours elapsed to emoncms. This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 25kW.<br><b>Requires redis installed to work</b>",
    
    '21':"Convert accumulating kWh to instantaneous power",
    
    '10':"Updates or inserts daily value on the specified time (given by the JSON time parameter from the API) of the specified feed",
    
    '24':"Negative values are zeroed for further processing by the next processor in the processing list",
    
    '25':"Positive values are zeroed for further processing by the next processor in the processing list",

    '27':"Maximal daily value. Upserts on the selected daily feed the highest value reached each day",
    '28':"Minimal daily value. Upserts on the selected daily feed the lowest value reached each day",

    '33':"The value '0' is passed back for further processing by the next processor in the processing list.",

    '35':"Publish to the specified MQTT topic",
    
    '36':"A NULL value is passed back for further processing by the next processor in the processing list.<br>Usefull for conditional process to work on.",
    '37':"The original value, unchanged by any process, is passed back for further processing by the next processor in the processing list.",
    
    '38':"<p>Validates if time is NOT in range of schedule. If NOT in schedule, value is ZEROed. Value is passed for further processing by the next processor in the processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>",
    '39':"<p>Validates if time is NOT in range of schedule. If NOT in schedule, value is NULLed. Value is passed for further processing by the next processor in the processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>",
    '40':"<p>Validates if time is in range of schedule. If in schedule, value is ZEROed. Value is passed for further processing by the next processor in the processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>",
    '41':"<p>Validates if time is in range of schedule. If in schedule, value is NULLed. Value is passed for further processing by the next processor in the processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same processing list</p>",
    
    '42':"If value from last process is ZERO, process execution will skip execution of next process in list",
    '43':"If value from last process is NOT ZERO, process execution will skip execution of next process in list",
    '44':"If value from last process is NULL, process execution will skip execution of next process in list",
    '45':"If value from last process is NOT NULL, process execution will skip execution of next process in list",

    '46':"If value from last process is greater than the specified value, process execution will skip execution of next process in list",
    '47':"If value from last process is greater or equal to the specified value, process execution will skip execution of next process in list",
    '48':"If value from last process is lower than the specified value, process execution will skip execution of next process in list",
    '49':"If value from last process is lower or equal to the specified value, process execution will skip execution of next process in list",
    '50':"If value from last process is equal to the specified value, process execution will skip execution of next process in list",
    '51':"If value from last process is NOT equal to the specified value, process execution will skip execution of next process in list",
    
    '52':"<p>Jumps the process execution to the specified position.</p><p><b>Warning</b><br>If you're not carefull you can create a goto loop on the process list.<br>When a loop occours, the API will appear to lock until the server php times out with an error.</p>",
	
    '53':"<p><b>Source Feed:</b><br>Virtual feeds should use this processor as the first one in the process list. It sources data from the selected feed.<br>The sourced value is passed back for further processing by the next processor in the processing list.<br>You can then add other processors to apply logic on the passed value for post-processing calculations in realtime.</p><p>Note: This virtual feed process list is executed on visualizations request that use this virtual feed.</p>"
    
}
        