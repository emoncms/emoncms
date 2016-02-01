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
                rescale: true // rescale y-axis after toggle
            }
        },
        toggle = function ( el, plot, datasets ) {
            var cell,
                row,
                index,
                isCell = el.is("td");
            if ( isCell || (el.parents("td").length) ) {
                cell = ( isCell ? el : el.parents("td") );
                row = cell.parent();
                index = row.index();

                // Check if the line is already hidden and toggle accordingly
		if ( datasets[index].hiddenData === undefined ) {
                    datasets[index].hiddenData = datasets[index].data;
                    datasets[index].data = [];
                    hideLegendEntry(row);
                } else {
                    datasets[index].data = datasets[index].hiddenData;
                    delete datasets[index].hiddenData;
                    restoreLegendEntry(row);
                }

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
            if ( plot.getOptions().toggle.rescale ) {
                plot.setupGrid();
            }
            plot.draw();
        },
        setupLegend = function ( plot ) {
            plot.hooks.legendInserted.push(function ( plot, legend ) {
                var cells = legend.find("td"),
                    datasets = plot.getData();

                // Update the legend if there are hidden series
                for ( var i = 0; i < cells.length; i += 2 ) {
		    if ( datasets[i / 2].hiddenData !== undefined ) {
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
            plot.hooks.processOptions.push(function ( plot, options ) {
                if ( options.legend.toggle ) {
                    setupLegend(plot);
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
