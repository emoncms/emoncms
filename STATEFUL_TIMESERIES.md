# Making a PHPTimeSeries feed Stateful

The TimeSeries feeds in EmonCMS contain datapoints at irregular time intervals. When used
for graphing the values of those feeds at times within these intervals is usually 'null'
(unless by chance a datapoint exists _within_ the interval). However when such a
TimeSeries represents the _state_ of a system or a tariff cost the value at any point in
time is the most recent datapoint, even if that falls before the interval.

## New Virtual Feed processes

To help solve these two related problems we introduce four new Virtual Feed processes:

* `state`: this takes a time series feed and interpolates values both forwards and
  backwards in time.
* `cost`: this takes a fixed interval feed and multiplies it by a time series using
  interpolated values as in the `state` process.
* `daily_charge`: this takes a timeseries representing *daily* standing charges and
  calculates the amount due corresponding to the interval requested.
* `sdelta`: this takes a fixed interval feed and converts the values to deltas. Equivalent
  to the 'delta' option in the graphing module but usable in calculations.

### Limitation

At time of writing there is a limitation in that the value of a time series feed using any
of the `state`, `cost` or `daily_charge` processes is taken at the start of the requested
interval, so if the feed changes during the interval that will not be reflected.

# Using the new Virtual Feeds processes

Here are some use-cases for these new processes:

## Showing State on a Graph

TimeSeries EmonCMS feeds that represent the state of a device (such as ON/OFF for an
airsource heatpump or underfloor heating valve) can be directly represented on an EmonCMS
graph by choosing the 'Step' graph type, which shows a straight line between the points.
However this has two issues:

* the step is created only between two datapoints and doesn't extend past the last datapoint, and
* the value cannot be used in a calculation: it's purely for graphing

To solve this create a new Virtual Feed using the 'state' feed processor. That can be
graphed directly and used as part of a virtual feed processing pipeline.

## Calculating Consumption Costs using Tariffs

In order to look at the overall cost of energy used the tariffs in force at the time of
the consumption need to be known. These tariffs change from time to time and stay in force
until the next update. As such they are best represented as a TimeSeries feed and not a
Fixed Interval feed. However when the EmonCMS graph module processes such a feed there are
no values other than at the points of change and so it's impossible to graph a cost by
multiplying the tariff by the consumption: there will only be values when the tariff
changes.

So you need to convert the tariff feed into a stateful feed and multiply that by the
consumption. This can be done using the virtual feed process list `state(tariff feed), x
sfeed (consumption feed)`. Note that you can't use the process list `sfeed(consumption
feed), x sfeed(stateful tariff feed) as a virtual feed cannot reference other virtual
feeds.

Alternatively the process `cost` (Cost Multiplier) performs the stateful transformation directly on the
tariff feed and then does the multiplication leading to: `sfeed(consumption feed), cost(tariff feed)`

### The delta problem

Typically if you have cumulative energy feeds (in kWh) then you use the delta function in
the graph module to get the consumption per interval.

However if you create the cost feeds described above and then use the delta option to
graph the cost per interval it does not calculate correctly all of the time, the exception
being when an interval contains a tariff change. In this case the delta usually has a
significant error as (somewhat obviously) the calculation `(Cn - Cn-1) * Tn-1` is only equal
to `Cn * Tn - Cn-1 * Tn-1` when `Tn == Tn-1`, ie. for intervals not containing a tariff
change.

To mitigate this problem use the`sdelta` processor to convert a feed into a delta within
the virtual feed and immediately follow it with a `cost` multiplication process:
`sdelta(consumpion feed), cost(tariff feed)`.

## Calculating Standing Charges

Standing charges are different from tariffs in that they are fixed for a specific time
period. Typically an energy provider will charge a fixed amount per day for supplying the
energy, over and above the consumption cost.

The `standing` virtual feed process takes the values from the previous process in the list and
multiplies them by the fraction that the current interval represents of the standing time
period. An example is:

    `state(Standing Charge TimeSeries), standing`

# Future Enhancements

In the above virtual feed process lists There will often be an error when a tariff change
occurs during an interval as the cost calculation for such an interval is based on the
tariff at the start. This is most apparent when graphing larger time intervals. Fixing
this requires significant changes to the current virtual pipeline and is left for future
enhancements.


Nick Townsend, January 2026
