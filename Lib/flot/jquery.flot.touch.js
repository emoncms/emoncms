/* jquery.flot.touch 3
Plugin for Flot version 0.8.3+.
Allows to use touch for pan / zoom.

nchaveiro(at)gmail(dot)com
Copyright (c) 2015 Licensed under the MIT license.
*/

(function($) {

    function init(plot) {
        var isPanning = false;
        var isZooming = false;
        var lastTouchPosition = { x: -1, y: -1 };
        var lastTouchDistance = 0;
        var relativeOffset = { x: 0, y: 0};
        var relativeScale = 1.0;
        var scaleOrigin = { x: 50, y: 50 };
        var lastRedraw= new Date().getTime();
        var eventdelayTouchEnded;

        function pan(delta) {
            var placeholder = plot.getPlaceholder();
            var options = plot.getOptions();

            relativeOffset.x -= delta.x;
            relativeOffset.y -= delta.y;

            if (!options.touch.css) {
                return; // no css updates
            }

            switch (options.touch.pan.toLowerCase()) {
                case 'x':
                    placeholder.css('transform', 'translateX(' + relativeOffset.x + 'px)');
                    break;
                case 'y':
                    placeholder.css('transform', 'translateY(' + relativeOffset.y + 'px)');
                    break;
                default:
                    placeholder.css('transform', 'translate(' + relativeOffset.x + 'px,' + relativeOffset.y + 'px)');
                    break;
            }
        }

        function scale(delta) {
            var placeholder = plot.getPlaceholder();
            var options = plot.getOptions();

            relativeScale *= 1 + (delta / 100);

            if (!options.touch.css) {
                return; // no css updates
            }

            switch (options.touch.scale.toLowerCase()) {
                case 'x':
                    placeholder.css('transform', 'scaleX(' + relativeScale + ')');
                    break;
                case 'y':
                    placeholder.css('transform', 'scaleY(' + relativeScale + ')');
                    break;
                default:
                    placeholder.css('transform', 'scale(' + relativeScale + ')');
                    break;
            }
        }

        function processOptions(plot, options) {
            var placeholder = plot.getPlaceholder();
            var options = plot.getOptions();
            
            if (options.touch.autoWidth) {
                placeholder.css('width', '100%');
            }

            if (options.touch.autoHeight) {
                var placeholderParent = placeholder.parent();
                var height = 0;

                placeholderParent.siblings().each(function() {
                    height -= $(this).outerHeight();
                });

                height -= parseInt(placeholderParent.css('padding-top'), 10);
                height -= parseInt(placeholderParent.css('padding-bottom'), 10);
                height += window.innerHeight;

                placeholder.css('height', (height <= 0) ? 100 : height + 'px');
            }
        }

        function bindEvents(plot, eventHolder) {
            var placeholder = plot.getPlaceholder();
            var options = plot.getOptions();
            
            if (options.touch.css) {
                placeholder.parent('div').css({'overflow': 'hidden'});
            }

            placeholder.bind('touchstart', function(evt) {
                clearTimeout(eventdelayTouchEnded); // cancel pending event
                var touches = evt.originalEvent.touches;
                var placeholder = plot.getPlaceholder();
                var options = plot.getOptions();

                // remember initial axis dimensions
                $.each(plot.getAxes(), function(index, axis) {
                    if (axis.direction === options.touch.scale.toLowerCase() || options.touch.scale.toLowerCase() == 'xy') {
                        axis.touch = {
                            min: axis.min,
                            max: axis.max,
                        }
                    }
                });

                if (touches.length === 1) {
                    isPanning = true;
                    lastTouchPosition = {
                        x: touches[0].pageX,
                        y: touches[0].pageY
                    };
                    lastTouchDistance = 0;
                }
                else if (touches.length === 2) {
                    isZooming = true;
                    lastTouchPosition = {
                        x: (touches[0].pageX + touches[1].pageX) / 2,
                        y: (touches[0].pageY + touches[1].pageY) / 2
                    };
                    lastTouchDistance = Math.sqrt(Math.pow(touches[1].pageX - touches[0].pageX, 2) + Math.pow(touches[1].pageY - touches[0].pageY, 2));
                }

                var offset = placeholder.offset();
                var rect = {
                    x: offset.left,
                    y: offset.top,
                    width: placeholder.width(),
                    height: placeholder.height()
                };
                var normalizedTouchPosition = {
                    x: lastTouchPosition.x,
                    y: lastTouchPosition.y
                };

                if (normalizedTouchPosition.x < rect.x) {
                    normalizedTouchPosition.x = rect.x;
                }
                else if (normalizedTouchPosition.x > rect.x + rect.width) {
                    normalizedTouchPosition.x = rect.x + rect.width;
                }

                if (normalizedTouchPosition.y < rect.y) {
                    normalizedTouchPosition.y = rect.y;
                }
                else if (normalizedTouchPosition.y > rect.y + rect.height) {
                    normalizedTouchPosition.y = rect.y + rect.height;
                }

                scaleOrigin = {
                    x: Math.round((normalizedTouchPosition.x / rect.width) * 100),
                    y: Math.round((normalizedTouchPosition.y / rect.height) * 100)
                };
                
                if (options.touch.css) {
                    placeholder.css('transform-origin', scaleOrigin.x + '% ' + scaleOrigin.y + '%');
                }
                
                placeholder.trigger("touchstarted", [ normalizedTouchPosition ]);
                // return false to prevent touch scrolling.
                return false;
            });

            placeholder.bind('touchmove', function(evt) {
                var options = plot.getOptions();
                var touches = evt.originalEvent.touches;
                var position, distance, delta;

                if (isPanning && touches.length === 1) {
                    position = {
                        x: touches[0].pageX,
                        y: touches[0].pageY
                    };
                    delta = {
                        x: lastTouchPosition.x - position.x,
                        y: lastTouchPosition.y - position.y
                    };

                    // transform via the delta
                    pan(delta);

                    lastTouchPosition = position;
                    lastTouchDistance = 0;
                }
                else if (isZooming && touches.length === 2) {
                    distance = Math.sqrt(Math.pow(touches[1].pageX - touches[0].pageX, 2) + Math.pow(touches[1].pageY - touches[0].pageY, 2));
                    position = {
                        x: (touches[0].pageX + touches[1].pageX) / 2,
                        y: (touches[0].pageY + touches[1].pageY) / 2
                    };
                    delta = distance - lastTouchDistance;

                    // scale via the delta
                    scale(delta);

                    lastTouchPosition = position;
                    lastTouchDistance = distance;
                }
                
                if (!options.touch.css) {  // no css updates
                    var now = new Date().getTime(),
                    framedelay = now - lastRedraw; // ms for each update
                    if (framedelay > 50) {
                        lastRedraw = now;
                        window.requestAnimationFrame(redraw);
                    }
                } 
            });

            placeholder.bind('touchend', function(evt) {
                var placeholder = plot.getPlaceholder();
                var options = plot.getOptions();

                redraw();
        
                isPanning = false;
                isZooming = false;
                lastTouchPosition = { x: -1, y: -1 };
                lastTouchDistance = 0;
                relativeOffset = { x: 0, y: 0 };
                relativeScale = 1.0;
                scaleOrigin = { x: 50, y: 50 };
                
                if (options.touch.css) {
                    placeholder.css({
                        'transform': 'translate(' + relativeOffset.x + 'px,' + relativeOffset.y + 'px) scale(' + relativeScale + ')',
                        'transform-origin': scaleOrigin.x + '% ' + scaleOrigin.y + '%'
                    });
                }
                
                var r = {};
                c1 = { x: 0, y: 0};
                c2 = { x: plot.width(), y: plot.height()};
                $.each(plot.getAxes(), function (name, axis) {
                    if (axis.used) {
                        var p1 = axis.c2p(c1[axis.direction]), p2 = axis.c2p(c2[axis.direction]); 
                        r[name] = { from: Math.min(p1, p2), to: Math.max(p1, p2) };
                    }
                });

                eventdelayTouchEnded = setTimeout(function(){ placeholder.trigger("touchended", [ r ]); }, options.touch.delayTouchEnded);
            });

        }

        function redraw() {
            var options = plot.getOptions();
            updateAxesMinMax();

            if (typeof options.callback == 'function') {
                options.callback();
            }
            else {
                plot.setupGrid();
                plot.draw();
            }
        }

        
        function updateAxesMinMax() {
            var options = plot.getOptions();
            
            // Apply the pan.
            if (relativeOffset.x !== 0 || relativeOffset.y !== 0) {
                $.each(plot.getAxes(), function(index, axis) {
                    if (axis.direction === options.touch.pan.toLowerCase() || options.touch.pan.toLowerCase() == 'xy') {
                        var min = axis.c2p(axis.p2c(axis.touch.min) - relativeOffset[axis.direction]);
                        var max = axis.c2p(axis.p2c(axis.touch.max) - relativeOffset[axis.direction]);

                        axis.options.min = min;
                        axis.options.max = max;
                    }
                });
            }

            // Apply the scale.
            if (relativeScale !== 1.0) {
                var width = plot.width();
                var height = plot.height();
                var scaleOriginPixel = {
                        x: Math.round((scaleOrigin.x / 100) * width),
                        y: Math.round((scaleOrigin.y / 100) * height)
                    };
                var range = {
                        x: {
                            min: scaleOriginPixel.x - (scaleOrigin.x / 100) * width / relativeScale,
                            max: scaleOriginPixel.x + (1 - (scaleOrigin.x / 100)) * width / relativeScale
                        },
                        y: {
                            min: scaleOriginPixel.y - (scaleOrigin.y / 100) * height / relativeScale,
                            max: scaleOriginPixel.y + (1 - (scaleOrigin.y / 100)) * height / relativeScale
                        }
                    };

                $.each(plot.getAxes(), function(index, axis) {
                    if (axis.direction === options.touch.scale.toLowerCase() || options.touch.scale.toLowerCase() == 'xy') {
                        var min = axis.c2p(range[axis.direction].min);
                        var max = axis.c2p(range[axis.direction].max);

                        if (min > max) {
                            var temp = min;
                            min = max;
                            max = temp;
                        }

                        axis.options.min = min;
                        axis.options.max = max;
                    }
                });
            }
        }


        
        function processDatapoints(plot, series, datapoints) {
            if (window.devicePixelRatio) {
                var placeholder = plot.getPlaceholder();
                placeholder.children('canvas').each(function(index, canvas) {
                    var context = canvas.getContext('2d');
                    var width = $(canvas).attr('width');
                    var height = $(canvas).attr('height');

                    $(canvas).attr('width', width * window.devicePixelRatio);
                    $(canvas).attr('height', height * window.devicePixelRatio);
                    $(canvas).css('width', width + 'px');
                    $(canvas).css('height', height + 'px');

                    context.scale(window.devicePixelRatio, window.devicePixelRatio);
                });
            }
        }
        
        function shutdown(plot, eventHolder) {
            var placeholder = plot.getPlaceholder();
            placeholder.unbind('touchstart').unbind('touchmove').unbind('touchend');
        }

        plot.hooks.processOptions.push(processOptions);
        plot.hooks.bindEvents.push(bindEvents);
        //plot.hooks.processDatapoints.push(processDatapoints); // For retina, slow on android
        plot.hooks.shutdown.push(shutdown);
    }

    $.plot.plugins.push({
        init: init,
        options: {
            touch: {
                pan: 'xy',              // what axis pan work
                scale: 'xy',            // what axis zoom work
                css: false,             // use css instead of redraw the graph (ugly!)
                autoWidth: false,
                autoHeight: false,
                delayTouchEnded: 500,  // delay in ms before touchended event is fired if no more touches
                callback: null,         // other plot draw callback
            }
        },
        name: 'touch',
        version: '3.0'
    });
})(jQuery);
