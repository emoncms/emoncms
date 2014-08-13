/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/. */

/*
 * Plugin to hide series in flot graphs.
 *
 * To activate, set legend.hideable to true in the flot options object.
 * To hide one or more series by default, set legend.hidden to an array of
 * label strings.
 *
 * At the moment, this only works with line and point graphs.
 *
 * Example:
 *
 *     var plotdata = [
 *         {
 *             data: [[1, 1], [2, 1], [3, 3], [4, 2], [5, 5]],
 *             label: "graph 1"
 *         },
 *         {
 *             data: [[1, 0], [2, 1], [3, 0], [4, 4], [5, 3]],
 *             label: "graph 2"
 *         }
 *     ];
 *
 *     plot = $.plot($("#placeholder"), plotdata, {
 *        series: {
 *             points: { show: true },
 *             lines: { show: true }
 *         },
 *         legend: {
 *             hideable: true,
 *             hidden: ["graph 1", "graph 2"]
 *         }
 *     });
 *
 */
(function ($) {
    var options = { };

    function init(plot) {
        var drawnOnce = false;

        function findPlotSeries(label) {
            var plotdata = plot.getData();
            for (var i = 0; i < plotdata.length; i++) {
                if (plotdata[i].label == label) {
                    return plotdata[i];
                }
            }
            return null;
        }

        function plotLabelClicked(label, mouseOut) {
            var series = findPlotSeries(label);
            if (!series) {
                return;
            }

            var options = plot.getOptions();
            var switchedOff = false;

            if (typeof series.points.oldShow === "undefined") {
                series.points.oldShow = false;
            }
            if (typeof series.lines.oldShow === "undefined") {
                series.lines.oldShow = false;
            }

            if (series.points.show && !series.points.oldShow) {
                series.points.show = false;
                series.points.oldShow = true;
                switchedOff = true;
            }
            if (series.lines.show && !series.lines.oldShow) {
                series.lines.show = false;
                series.lines.oldShow = true;
                switchedOff = true;
            }

            if (switchedOff) {
                series.oldColor = series.color;
                series.color = "#fff";
                setHidden(options, label, true);
            } else {
                var switchedOn = false;

                if (!series.points.show && series.points.oldShow) {
                    series.points.show = true;
                    series.points.oldShow = false;
                    switchedOn = true;
                }
                if (!series.lines.show && series.lines.oldShow) {
            	    series.lines.show = true;
                    series.lines.oldShow = false;
                    switchedOn = true;
                }

                if (switchedOn) {
            	    series.color = series.oldColor;
                    setHidden(options, label, false);
            	}
            }

            // HACK: Reset the data, triggering recalculation of graph bounds
            plot.setData(plot.getData());

            plot.setupGrid();
            plot.draw();
        }

        function setHidden(options, label, hide) {
            // Record state to a new variable in the legend option object.
            if (!options.legend.hidden) {
                options.legend.hidden = [];
            }

            var pos = options.legend.hidden.indexOf(label);

            if (hide) {
                if (pos < 0) {
                    options.legend.hidden.push(label);
                }
            } else {
                if (pos > -1) {
                    options.legend.hidden.splice(pos, 1);
                }
            }
        }

        function setHideAction(elem) {
            elem.mouseenter(function() { $(this).css("cursor", "pointer"); })
                .mouseleave(function() { $(this).css("cursor", "default"); })
                .unbind("click").click(function() {
                    plotLabelClicked($(this).parent().text());
                });
        }

        function plotLabelHandlers(plot) {
            var options = plot.getOptions();

            if (!options.legend.hideable) {
                return;
            }

            var p = plot.getPlaceholder();

            setHideAction(p.find(".graphlabel"));
            setHideAction(p.find(".legendColorBox"));

            if (!drawnOnce) {
                drawnOnce = true;
                if (options.legend.hidden) {
                    for (var i = 0; i < options.legend.hidden.length; i++) {
                        plotLabelClicked(options.legend.hidden[i], true);
                    }
                }
            }
        }

        function checkOptions(plot, options) {
            if (!options.legend.hideable) {
                return;
            }

            options.legend.labelFormatter = function(label, series) {
                return '<span class="graphlabel">' + label + '</span>';
            };
        }

        function hideDatapointsIfNecessary(plot, s, datapoints) {
            var options = plot.getOptions();

            if (!options.legend.hideable) {
                return;
            }

            if (options.legend.hidden &&
                options.legend.hidden.indexOf(s.label) > -1) {
                var off = false;

                if (s.points.show) {
                    s.points.show = false;
                    s.points.oldShow = true;
                    off = true;
                }
                if (s.lines.show) {
                    s.lines.show = false;
                    s.lines.oldShow = true;
                    off = true;
                }

                if (off) {
                    s.oldColor = s.color;
                    s.color = "#fff";
                }
            }

            if (!s.points.show && !s.lines.show) {
                s.datapoints.format = [ null, null ];
            }
        }

        plot.hooks.processOptions.push(checkOptions);

        plot.hooks.draw.push(function (plot, ctx) {
            plotLabelHandlers(plot);
        });

        plot.hooks.processDatapoints.push(hideDatapointsIfNecessary);
    }

    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'hiddenGraphs',
        version: '1.1'
    });

})(jQuery);
