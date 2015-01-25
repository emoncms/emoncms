var process_info = {

    '1':"<p><b>Log to feed:</b> This processor logs the current selected input to a timeseries feed which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>Feed engine:</b> The fixed interval with averaging (PHPFIWA) feed engine is the recommended engine to use for logging power, temperature, humidity, voltage and current data. In addition to storing the full resolution data it produces a series of downsampled averaged layers which gives a more accurate representation of the data when viewing the data over a large time range.</p><p><b>Feed interval:</b> When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p>",
    
    '2':"Scale input by value given. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list",
    
    '3':"Offset input by value given. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list",

    '4':"Convert a power value in Watts to a cumulative and ever rising kWh timeseries plot",

    '5':"Convert a power value in Watts to a feed that contains an entry for the total energy used each day (kWh/d)",

    '6':"This multiplies the current value with the value from other input as selected from the input list. The result is passed back for further processing by the next processor in the input processing list.",
    
    '12':"This divides the current value with the value from other input as selected from the input list. The result is passed back for further processing by the next processor in the input processing list.",
    
    '11':"This adds the current value with the value from other input as selected from the input list. The result is passed back for further processing by the next processor in the input processing list.",
    
    '22':"This subtracts the current value with the value from other input as selected from the input list. The result is passed back for further processing by the next processor in the input processing list.",
    
    '14':"Output feed accumulates by input value",  

    '15':"Output feed is the difference between the current value and the last",   
    
    '7':"Counts the amount of time that an input is high in each day and logs the result to a feed. Created for counting the number of hours a solar hot water pump is on each day",
    
    '34':"To be used in conjunction with an emontx sending total watt hours elapsed to emoncms. This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 25kW.<br><b>Requires redis installed to work</b>",
    
    '21':"Convert accumulating kWh to instantaneous power",
    
    '10':"Updates or inserts value on the specified time (given by the JSON time parameter from the API) of the specified feed",
    
    '24':"Negative input values are zeroed for further processing by the next processor in the input processing list",
    
    '25':"Positive input values are zeroed for further processing by the next processor in the input processing list",
    
    '35':"Publish to the specified MQTT topic",
    
    '36':"A NULL value is passed back for further processing by the next processor in the input processing list.<br>Usefull for conditional process to work on.",
    '37':"The original value, unchanged by any process, is passed back for further processing by the next processor in the input processing list.",
    
    '38':"<p>Validates if input time is NOT in range of schedule. If NOT in schedule, value is ZEROed. Value is passed for further processing by the next processor in the input processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same input processing list</p>",
    '39':"<p>Validates if input time is NOT in range of schedule. If NOT in schedule, value is NULLed. Value is passed for further processing by the next processor in the input processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same input processing list</p>",
    '40':"<p>Validates if input time is in range of schedule. If in schedule, value is ZEROed. Value is passed for further processing by the next processor in the input processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same input processing list</p>",
    '41':"<p>Validates if input time is in range of schedule. If in schedule, value is NULLed. Value is passed for further processing by the next processor in the input processing list.</p><p>You can use this to get a feed for each of the multi-rate tariff rate your provider gives. Add the 'Reset to Original' process before this process to log the input value to a different feed for each schedule on the same input processing list</p>",
    
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
    
    '52':"<p>Jumps the process execution to the specified position.</p><p><b>Warning</b><br>If you're not carefull you can create a goto loop on the process list.<br>When a loop occours, the API input of new data will appear to lock until the server php times out with an error.</p>"
    
}
        