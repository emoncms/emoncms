/*

    Allows series to be toggled using their entries in the chart legend.
    Supports series groups.

    TODO:
     * Disable visual feedback (usually so dev can implement their own)

    History:
    0.3 - 7 Mar 2013 -> jamesinc  https://github.com/jamesinc/flot/commit/20f899cf4257f91860068f6ecae9871d6d00cde3
    0.5 - 1 Feb 2015 -> software-monkey refactor

 */

(function ( $ ) {
    var options = {
            legend: {
                toggle: false // set to true in plot options to enable
            },
            toggle: {
                scale: "visible" // scale y-axis to only "visible" series or "all" series
            }
        },
        toggle = function ( el, plot, datasets ) {
            var cell,
                row,
                index,
                isCell = el.is("td"),
                toggleState = $(plot.getPlaceholder()).data("togglestate");
            if ( isCell || (el.parents("td").length) ) {
                cell = ( isCell ? el : el.parents("td") );
                row = cell.parent();
                index = row.index();

                // Check if the line is already hidden and toggle accordingly
		if ( toggleState.has(index) ) {
                    datasets[index].data = datasets[index].hiddenData;
                    delete datasets[index].hiddenData;
                    toggleState['delete'](index); // workaround for https://github.com/yui/yuicompressor/issues/122
                    restoreLegendEntry(row);
                } else {
                    datasets[index].hiddenData = datasets[index].data;
                    datasets[index].data = [];
                    toggleState.add(index);
                    hideLegendEntry(row);
                }

                $(plot.getPlaceholder()).data("togglestate", toggleState);

                redraw(plot, datasets);
            }
        },
        hideLegendEntry = function ( entry ) {
            entry.data("legendopacity", entry.css("opacity"));
            entry.css("opacity", "0.40");
        },
        restoreLegendEntry = function ( entry ) {
            entry.css("opacity", entry.data("legendopacity"));
        },
        redraw = function ( plot, datasets ) {
            plot.setData(datasets);
            if ( plot.getOptions().toggle.scale == "visible" ) {
                plot.setupGrid();
            }
            plot.draw();
        },
        setupToggle = function ( plot ) {
            plot.hooks.drawSeries.push(function ( plot, canvascontext, series ) {
                var datasets = plot.getData(),
                    toggleState = $(plot.getPlaceholder()).data("togglestate"),
                    changed = false;

                for ( var i = 0; i < datasets.length; i++ ) {
                    // Keep hidden series hidden when graph is redrawn (e.g. from zooming)
                    if ( toggleState.has(i) && datasets[i].hiddenData === undefined ) {
                        datasets[i].hiddenData = datasets[i].data;
                        datasets[i].data = [];
                        changed = true;
                    }
                }

                if ( changed ) {
                    redraw(plot, datasets);
                }
            });

            plot.hooks.legendInserted.push(function ( plot, legend ) {
                var cells = legend.find("td"),
                    datasets = plot.getData(),
                    toggleState = $(plot.getPlaceholder()).data("togglestate");

                // Update the legend if there are hidden series
                for ( var i = 0; i < cells.length; i += 2 ) {
                    if ( toggleState.has(i / 2) ) {
                        hideLegendEntry($(cells[i]).parent());
                    }
                }

                legend
                    .unbind("click.flot")
                    .bind("selectstart", function ( e ) {
                        e.preventDefault();
                        return false;

                    })
                    .bind("click.flot", function ( e ) {
                        toggle($(e.target), plot, datasets);

                    })
                    .find("td").css("cursor", "pointer");
            });
        },
        init = function ( plot ) {
            var toggleState = $(plot.getPlaceholder()).data("togglestate");

            if ( typeof toggleState === 'undefined' ) {
                toggleState = new Set();
                $(plot.getPlaceholder()).data("togglestate", toggleState);
            }

            plot.hooks.processOptions.push(function ( plot, options ) {
                if ( options.legend.toggle ) {
                    setupToggle(plot);
                }
            });
        };

    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'toggleLegend',
        version: '0.5'
    });

}(jQuery));
