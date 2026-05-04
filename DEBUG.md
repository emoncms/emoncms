# Debugging in EmonCMS

It's important when testing updated code to see what's going on with the data. The current
logging mechanism provides a way of inserting INFO, WARNING or ERROR messages which works
well. However it's desirable to have the ability to insert high frequency but low-overhead
debugging messages into the code permanently, which can then be enabled individually at
the file level via configuration changes. To do this the existing EmonLogger code was
enhanced in a way that does not affect the existing logging.

## Creating debug log entries

The function `debug` was introduced. It is a variadic argument function that inspects each
argument and converts it into a suitable string, concatenating the strings into the final
debug message. It can be used identically to the existing log functions, for example:

    $this->log->debug("I am here looking at the lastvalue: $lastvalue");

The drawback with this is that the string message must be interpolated every time this
function is called, even if debugging is disabled. This isn't too serious for a simple
inclusion, but if the expression is more complex, say reducing an array, then it can be a
significant overhead. The solution is lazy evaluation by passing this as follows:

    $this->log->debug("I am here looking at the lastvalue: ",$lastvalue);

In this case the interpolation is only done if debugging is enabled. As an added bonus you
can pass more complex structures and the debug code will turn them into a string form. The
maximum length for arrays is configurable allowing even large data items to feature in the
debug log in an abbreviated form. This is often good enough.

## Configuring Debug Logging

There are two configurable parameters in `settings.ini`:

    ; List the files (without extension) in which you want debugging to be activated
    debug = ; PHPFina PHPTimeSeries VirtualFeed feed_model process_model process_processlist
    ; Maximum number of array elements to dump
    debug_maxlen = 10
