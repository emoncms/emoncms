/*! @kevinburke/flot v5.1.0 | MIT License | https://github.com/kevinburke/flot */
var Flot = (function (exports) {
    'use strict';

    var browser = {
        getPageXY: function (e) {
            var doc = document.documentElement,
                pageX = e.clientX + (window.pageXOffset || doc.scrollLeft) - (doc.clientLeft || 0),
                pageY = e.clientY + (window.pageYOffset || doc.scrollTop) - (doc.clientTop || 0);
            return { X: pageX, Y: pageY };
        },

        getPixelRatio: function(context) {
            var devicePixelRatio = window.devicePixelRatio || 1,
                backingStoreRatio =
                context.webkitBackingStorePixelRatio ||
                context.mozBackingStorePixelRatio ||
                context.msBackingStorePixelRatio ||
                context.oBackingStorePixelRatio ||
                context.backingStorePixelRatio || 1;
            return devicePixelRatio / backingStoreRatio;
        },

        isSafari: function() {
            var top = window.top;
            if (!top) return false;
            return /constructor/i.test(top.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!top['safari'] || (typeof top.safari !== 'undefined' && top.safari.pushNotification));
        },

        isMobileSafari: function() {
            return navigator.userAgent.match(/(iPod|iPhone|iPad)/) && navigator.userAgent.match(/AppleWebKit/);
        },

        isOpera: function() {
            return (!!window.opr && !!window.opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
        },

        isFirefox: function() {
            return typeof InstallTrigger !== 'undefined';
        },

        isIE: function() {
            return /*@cc_on!@*/!!document.documentMode;
        },

        isEdge: function() {
            return !browser.isIE() && !!window.StyleMedia;
        },

        isChrome: function() {
            return !!window.chrome && !!window.chrome.webstore;
        },

        isBlink: function() {
            return (browser.isChrome() || browser.isOpera()) && !!window.CSS;
        }
    };

    // Vanilla replacements for jQuery utility functions used throughout flot.

    function isPlainObject(value) {
        if (!value || Object.prototype.toString.call(value) !== '[object Object]') {
            return false;
        }

        var prototype = Object.getPrototypeOf(value);
        return prototype === Object.prototype || prototype === null;
    }

    function cloneDeepValue(value) {
        if (Array.isArray(value)) {
            return value.map(cloneDeepValue);
        }

        if (isPlainObject(value)) {
            var copy = {};
            var keys = Object.keys(value);
            for (var i = 0; i < keys.length; i++) {
                var key = keys[i];
                var nestedValue = value[key];
                if (nestedValue !== undefined) {
                    copy[key] = cloneDeepValue(nestedValue);
                }
            }
            return copy;
        }

        return value;
    }

    // Deep-extend target with one or more source objects. When `deep` is true,
    // nested objects are recursively merged rather than replaced. Arrays are
    // replaced, not concatenated (matching $.extend behavior that flot relies on).
    function extend(deep, target) {
        var sources = Array.prototype.slice.call(arguments, 2);
        if (typeof deep !== 'boolean') {
            sources.unshift(target);
            target = deep;
            deep = false;
        }

        for (var i = 0; i < sources.length; i++) {
            var src = sources[i];
            if (src == null) continue;
            var keys = Object.keys(src);
            for (var k = 0; k < keys.length; k++) {
                var key = keys[k];
                var val = src[key];
                if (val === undefined) {
                    continue;
                }
                if (deep && Array.isArray(val)) {
                    target[key] = cloneDeepValue(val);
                    continue;
                }
                if (deep && isPlainObject(val)) {
                    if (typeof target[key] !== 'object' || target[key] == null) {
                        target[key] = {};
                    }
                    extend(true, target[key], val);
                } else {
                    target[key] = val;
                }
            }
        }

        return target;
    }

    // Get inner width of an element (content area, no padding/border/scrollbar).
    function width(el) {
        return el.clientWidth;
    }

    // Get inner height of an element.
    function height(el) {
        return el.clientHeight;
    }

    // Get or set a CSS property on an element.
    function css(el, prop, val) {
        if (val !== undefined) {
            el.style[prop] = typeof val === 'number' ? val + 'px' : val;
            return;
        }
        return getComputedStyle(el)[prop];
    }

    // Store or retrieve arbitrary data on an element.
    var dataStore = new WeakMap();

    function data(el, key, val) {
        var store = dataStore.get(el);
        if (!store) {
            store = {};
            dataStore.set(el, store);
        }
        if (val !== undefined) {
            store[key] = val;
            return;
        }
        return store[key];
    }

    function removeData(el, key) {
        var store = dataStore.get(el);
        if (store) {
            {
                delete store[key];
            }
        }
    }

    // Trigger a custom event on an element. Extra args are passed as the
    // event's `detail` property (an array). For jQuery adapter compatibility,
    // the adapter re-dispatches these as jQuery events so $(el).on() works.
    function trigger(el, type, args) {
        var event = new CustomEvent(type, {
            detail: args || [],
            bubbles: true,
            cancelable: true
        });
        el.dispatchEvent(event);
        return event;
    }

    // Bind an event listener, tracking it so unbindAll can remove it later.
    var listenerStore = new WeakMap();

    function bind(el, type, handler) {
        el.addEventListener(type, handler);
        var listeners = listenerStore.get(el);
        if (!listeners) {
            listeners = [];
            listenerStore.set(el, listeners);
        }
        listeners.push({ type: type, handler: handler });
    }

    function unbind(el, type, handler) {
        if (type && handler) {
            el.removeEventListener(type, handler);
            var listeners = listenerStore.get(el);
            if (listeners) {
                listenerStore.set(el, listeners.filter(function(l) {
                    return l.type !== type || l.handler !== handler;
                }));
            }
            return;
        }
        // Remove all listeners (optionally filtered by type)
        var listeners = listenerStore.get(el);
        if (listeners) {
            var remaining = [];
            for (var i = 0; i < listeners.length; i++) {
                if (type && listeners[i].type !== type) {
                    remaining.push(listeners[i]);
                } else {
                    el.removeEventListener(listeners[i].type, listeners[i].handler);
                }
            }
            listenerStore.set(el, remaining);
        }
    }

    /** ## jquery.flot.canvaswrapper

    This plugin contains the function for creating and manipulating both the canvas
    layers and svg layers.

    The Canvas object is a wrapper around an HTML5 canvas tag.
    The constructor Canvas(cls, container) takes as parameters cls,
    the list of classes to apply to the canvas adnd the containter,
    element onto which to append the canvas. The canvas operations
    don't work unless the canvas is attached to the DOM.

    ### jquery.canvaswrapper.js API functions
    */


    var Canvas = function(cls, container) {
            var element = container.getElementsByClassName(cls)[0];

            if (!element) {
                element = document.createElement('canvas');
                element.className = cls;
                element.style.direction = 'ltr';
                element.style.position = 'absolute';
                element.style.left = '0px';
                element.style.top = '0px';

                container.appendChild(element);

                // If HTML5 Canvas isn't available, throw

                if (!element.getContext) {
                    throw new Error('Canvas is not available.');
                }
            }

            this.element = element;

            var context = this.context = element.getContext('2d');
            this.pixelRatio = browser.getPixelRatio(context);

            // Size the canvas to match the internal dimensions of its container
            var w = width(container);
            var h = height(container);
            this.resize(w, h);

            // Collection of HTML div layers for text overlaid onto the canvas

            this.SVGContainer = null;
            this.SVG = {};

            // Cache of text fragments and metrics, so we can avoid expensively
            // re-calculating them when the plot is re-rendered in a loop.

            this._textCache = {};
        };

        /**
        - resize(width, height)

         Resizes the canvas to the given dimensions.
         The width represents the new width of the canvas, meanwhile the height
         is the new height of the canvas, both of them in pixels.
        */

        Canvas.prototype.resize = function(width, height) {
            var minSize = 10;
            width = width < minSize ? minSize : width;
            height = height < minSize ? minSize : height;

            var element = this.element,
                context = this.context,
                pixelRatio = this.pixelRatio;

            // Resize the canvas, increasing its density based on the display's
            // pixel ratio; basically giving it more pixels without increasing the
            // size of its element, to take advantage of the fact that retina
            // displays have that many more pixels in the same advertised space.

            // Resizing should reset the state (excanvas seems to be buggy though)

            if (this.width !== width) {
                element.width = width * pixelRatio;
                element.style.width = width + 'px';
                this.width = width;
            }

            if (this.height !== height) {
                element.height = height * pixelRatio;
                element.style.height = height + 'px';
                this.height = height;
            }

            // Save the context, so we can reset in case we get replotted.  The
            // restore ensure that we're really back at the initial state, and
            // should be safe even if we haven't saved the initial state yet.

            context.restore();
            context.save();

            // Scale the coordinate space to match the display density; so even though we
            // may have twice as many pixels, we still want lines and other drawing to
            // appear at the same size; the extra pixels will just make them crisper.

            context.scale(pixelRatio, pixelRatio);
        };

        /**
        - clear()

         Clears the entire canvas area, not including any overlaid HTML text
        */
        Canvas.prototype.clear = function() {
            this.context.clearRect(0, 0, this.width, this.height);
        };

        /**
        - render()

         Finishes rendering the canvas, including managing the text overlay.
        */
        Canvas.prototype.render = function() {
            var cache = this._textCache;

            // For each text layer, add elements marked as active that haven't
            // already been rendered, and remove those that are no longer active.

            for (var layerKey in cache) {
                if (Object.prototype.hasOwnProperty.call(cache, layerKey)) {
                    var layer = this.getSVGLayer(layerKey),
                        layerCache = cache[layerKey];

                    var display = layer.style.display;
                    layer.style.display = 'none';

                    for (var styleKey in layerCache) {
                        if (Object.prototype.hasOwnProperty.call(layerCache, styleKey)) {
                            var styleCache = layerCache[styleKey];
                            for (var key in styleCache) {
                                if (Object.prototype.hasOwnProperty.call(styleCache, key)) {
                                    var val = styleCache[key],
                                        positions = val.positions;

                                    for (var i = 0, position; positions[i]; i++) {
                                        position = positions[i];
                                        if (position.active) {
                                            if (!position.rendered) {
                                                layer.appendChild(position.element);
                                                position.rendered = true;
                                            }
                                        } else {
                                            positions.splice(i--, 1);
                                            if (position.rendered) {
                                                while (position.element.firstChild) {
                                                    position.element.removeChild(position.element.firstChild);
                                                }
                                                position.element.parentNode.removeChild(position.element);
                                            }
                                        }
                                    }

                                    if (positions.length === 0) {
                                        if (val.measured) {
                                            val.measured = false;
                                        } else {
                                            delete styleCache[key];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    layer.style.display = display;
                }
            }
        };

        /**
        - getSVGLayer(classes)

         Creates (if necessary) and returns the SVG overlay container.
         The classes string represents the string of space-separated CSS classes
         used to uniquely identify the text layer. It return the svg-layer div.
        */
        Canvas.prototype.getSVGLayer = function(classes) {
            var layer = this.SVG[classes];

            // Create the SVG layer if it doesn't exist

            if (!layer) {
                // Create the svg layer container, if it doesn't exist

                var svgElement;

                if (!this.SVGContainer) {
                    this.SVGContainer = document.createElement('div');
                    this.SVGContainer.className = 'flot-svg';
                    this.SVGContainer.style.position = 'absolute';
                    this.SVGContainer.style.top = '0px';
                    this.SVGContainer.style.left = '0px';
                    this.SVGContainer.style.height = '100%';
                    this.SVGContainer.style.width = '100%';
                    this.SVGContainer.style.pointerEvents = 'none';
                    this.element.parentNode.appendChild(this.SVGContainer);

                    svgElement = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svgElement.style.width = '100%';
                    svgElement.style.height = '100%';

                    this.SVGContainer.appendChild(svgElement);
                } else {
                    svgElement = this.SVGContainer.firstChild;
                }

                layer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                layer.setAttribute('class', classes);
                layer.style.position = 'absolute';
                layer.style.top = '0px';
                layer.style.left = '0px';
                layer.style.bottom = '0px';
                layer.style.right = '0px';
                svgElement.appendChild(layer);
                this.SVG[classes] = layer;
            }

            return layer;
        };

        /**
        - getTextInfo(layer, text, font, angle, width)

         Creates (if necessary) and returns a text info object.
         The object looks like this:
         ```js
         {
             width //Width of the text's wrapper div.
             height //Height of the text's wrapper div.
             element //The HTML div containing the text.
             positions //Array of positions at which this text is drawn.
          }
          ```
          The positions array contains objects that look like this:
          ```js
          {
             active //Flag indicating whether the text should be visible.
             rendered //Flag indicating whether the text is currently visible.
             element //The HTML div containing the text.
             text //The actual text and is identical with element[0].textContent.
             x //X coordinate at which to draw the text.
             y //Y coordinate at which to draw the text.
          }
          ```
          Each position after the first receives a clone of the original element.
          The idea is that that the width, height, and general 'identity' of the
          text is constant no matter where it is placed; the placements are a
          secondary property.

          Canvas maintains a cache of recently-used text info objects; getTextInfo
          either returns the cached element or creates a new entry.

         The layer parameter is string of space-separated CSS classes uniquely
         identifying the layer containing this text.
         Text is the text string to retrieve info for.
         Font is either a string of space-separated CSS classes or a font-spec object,
         defining the text's font and style.
         Angle is the angle at which to rotate the text, in degrees. Angle is currently unused,
         it will be implemented in the future.
         The last parameter is the Maximum width of the text before it wraps.
         The method returns a text info object.
        */
        Canvas.prototype.getTextInfo = function(layer, text, font, angle, width) {
            var textStyle, layerCache, styleCache, info;

            // Cast the value to a string, in case we were given a number or such

            text = '' + text;

            // If the font is a font-spec object, generate a CSS font definition

            if (typeof font === 'object') {
                textStyle = font.style + ' ' + font.variant + ' ' + font.weight + ' ' + font.size + 'px/' + font.lineHeight + 'px ' + font.family;
            } else {
                textStyle = font;
            }

            // Retrieve (or create) the cache for the text's layer and styles

            layerCache = this._textCache[layer];

            if (layerCache == null) {
                layerCache = this._textCache[layer] = {};
            }

            styleCache = layerCache[textStyle];

            if (styleCache == null) {
                styleCache = layerCache[textStyle] = {};
            }

            var key = generateKey(text);
            info = styleCache[key];

            // If we can't find a matching element in our cache, create a new one

            if (!info) {
                var element = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                if (text.indexOf('<br>') !== -1) {
                    addTspanElements(text, element, -9999);
                } else {
                    var textNode = document.createTextNode(text);
                    element.appendChild(textNode);
                }

                element.style.position = 'absolute';
                element.style.maxWidth = width;
                element.setAttributeNS(null, 'x', -9999);
                element.setAttributeNS(null, 'y', -9999);

                if (typeof font === 'object') {
                    element.style.font = textStyle;
                    element.style.fill = font.color;
                } else if (typeof font === 'string') {
                    element.setAttribute('class', font);
                }

                this.getSVGLayer(layer).appendChild(element);
                var elementRect = element.getBBox();

                info = styleCache[key] = {
                    width: elementRect.width,
                    height: elementRect.height,
                    measured: true,
                    element: element,
                    positions: []
                };

                //remove elements from dom
                while (element.firstChild) {
                    element.removeChild(element.firstChild);
                }
                element.parentNode.removeChild(element);
            }

            info.measured = true;
            return info;
        };

        function updateTransforms (element, transforms) {
            element.transform.baseVal.clear();
            if (transforms) {
                transforms.forEach(function(t) {
                    element.transform.baseVal.appendItem(t);
                });
            }
        }

        /**
        - addText (layer, x, y, text, font, angle, width, halign, valign, transforms)

         Adds a text string to the canvas text overlay.
         The text isn't drawn immediately; it is marked as rendering, which will
         result in its addition to the canvas on the next render pass.

         The layer is string of space-separated CSS classes uniquely
         identifying the layer containing this text.
         X and Y represents the X and Y coordinate at which to draw the text.
         and text is the string to draw
        */
        Canvas.prototype.addText = function(layer, x, y, text, font, angle, width, halign, valign, transforms) {
            var info = this.getTextInfo(layer, text, font, angle, width),
                positions = info.positions;

            // Tweak the div's position to match the text's alignment

            if (halign === 'center') {
                x -= info.width / 2;
            } else if (halign === 'right') {
                x -= info.width;
            }

            if (valign === 'middle') {
                y -= info.height / 2;
            } else if (valign === 'bottom') {
                y -= info.height;
            }

            y += 0.75 * info.height;

            // Determine whether this text already exists at this position.
            // If so, mark it for inclusion in the next render pass.

            for (var i = 0, position; positions[i]; i++) {
                position = positions[i];
                if (position.x === x && position.y === y && position.text === text) {
                    position.active = true;
                    // update the transforms
                    updateTransforms(position.element, transforms);

                    return;
                } else if (position.active === false) {
                    position.active = true;
                    position.text = text;
                    if (text.indexOf('<br>') !== -1) {
                        y -= 0.25 * info.height;
                        addTspanElements(text, position.element, x);
                    } else {
                        position.element.textContent = text;
                    }
                    position.element.setAttributeNS(null, 'x', x);
                    position.element.setAttributeNS(null, 'y', y);
                    position.x = x;
                    position.y = y;
                    // update the transforms
                    updateTransforms(position.element, transforms);

                    return;
                }
            }

            // If the text doesn't exist at this position, create a new entry

            // For the very first position we'll re-use the original element,
            // while for subsequent ones we'll clone it.

            position = {
                active: true,
                rendered: false,
                element: positions.length ? info.element.cloneNode() : info.element,
                text: text,
                x: x,
                y: y
            };

            positions.push(position);

            if (text.indexOf('<br>') !== -1) {
                y -= 0.25 * info.height;
                addTspanElements(text, position.element, x);
            } else {
                position.element.textContent = text;
            }

            // Move the element to its final position within the container
            position.element.setAttributeNS(null, 'x', x);
            position.element.setAttributeNS(null, 'y', y);
            position.element.style.textAlign = halign;
            // update the transforms
            updateTransforms(position.element, transforms);
        };

        var addTspanElements = function(text, element, x) {
            var lines = text.split('<br>'),
                tspan, i, offset;

            for (i = 0; i < lines.length; i++) {
                if (!element.childNodes[i]) {
                    tspan = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
                    element.appendChild(tspan);
                } else {
                    tspan = element.childNodes[i];
                }
                tspan.textContent = lines[i];
                offset = (i === 0 ? 0 : 1) + 'em';
                tspan.setAttributeNS(null, 'dy', offset);
                tspan.setAttributeNS(null, 'x', x);
            }
        };

        /**
        - removeText (layer, x, y, text, font, angle)

          The function removes one or more text strings from the canvas text overlay.
          If no parameters are given, all text within the layer is removed.

          Note that the text is not immediately removed; it is simply marked as
          inactive, which will result in its removal on the next render pass.
          This avoids the performance penalty for 'clear and redraw' behavior,
          where we potentially get rid of all text on a layer, but will likely
          add back most or all of it later, as when redrawing axes, for example.

          The layer is a string of space-separated CSS classes uniquely
          identifying the layer containing this text. The following parameter are
          X and Y coordinate of the text.
          Text is the string to remove, while the font is either a string of space-separated CSS
          classes or a font-spec object, defining the text's font and style.
         */
        Canvas.prototype.removeText = function(layer, x, y, text, font, angle) {
            var info, htmlYCoord;
            if (text == null) {
                var layerCache = this._textCache[layer];
                if (layerCache != null) {
                    for (var styleKey in layerCache) {
                        if (Object.prototype.hasOwnProperty.call(layerCache, styleKey)) {
                            var styleCache = layerCache[styleKey];
                            for (var key in styleCache) {
                                if (Object.prototype.hasOwnProperty.call(styleCache, key)) {
                                    var positions = styleCache[key].positions;
                                    positions.forEach(function(position) {
                                        position.active = false;
                                    });
                                }
                            }
                        }
                    }
                }
            } else {
                info = this.getTextInfo(layer, text, font, angle);
                positions = info.positions;
                positions.forEach(function(position) {
                    htmlYCoord = y + 0.75 * info.height;
                    if (position.x === x && position.y === htmlYCoord && position.text === text) {
                        position.active = false;
                    }
                });
            }
        };

        /**
        - clearCache()

         Clears the cache used to speed up the text size measurements.
         As an (unfortunate) side effect all text within the text Layer is removed.
         Use this function before plot.setupGrid() and plot.draw() if the plot just
         became visible or the styles changed.
        */
        Canvas.prototype.clearCache = function() {
            var cache = this._textCache;
            for (var layerKey in cache) {
                if (Object.prototype.hasOwnProperty.call(cache, layerKey)) {
                    var layer = this.getSVGLayer(layerKey);
                    while (layer.firstChild) {
                        layer.removeChild(layer.firstChild);
                    }
                }
            }
            this._textCache = {};
        };

        function generateKey(text) {
            return text.replace(/0|1|2|3|4|5|6|7|8|9/g, '0');
        }

    /* Plugin for working with colors.
     *
     * Version 1.1.
     *
     * Inspiration from jQuery color animation plugin by John Resig.
     *
     * Released under the MIT license by Ole Laursen, October 2009.
     *
     * Examples:
     *
     *   color.parse("#fff").scale('rgb', 0.25).add('a', -0.5).toString()
     *   var c = color.extract($("#mydiv"), 'background-color');
     *   console.log(c.r, c.g, c.b, c.a);
     *   color.make(100, 50, 25, 0.4).toString() // returns "rgba(100,50,25,0.4)"
     *
     * Note that .scale() and .add() return the same modified object
     * instead of making a new one.
     *
     * V. 1.1: Fix error handling so e.g. parsing an empty string does
     * produce a color rather than just crashing.
     */

    var color = {};

    // construct color object with some convenient chainable helpers
    color.make = function (r, g, b, a) {
        var o = {};
        o.r = r || 0;
        o.g = g || 0;
        o.b = b || 0;
        o.a = a != null ? a : 1;

        o.add = function (c, d) {
            for (var i = 0; i < c.length; ++i) {
                o[c.charAt(i)] += d;
            }

            return o.normalize();
        };

        o.scale = function (c, f) {
            for (var i = 0; i < c.length; ++i) {
                o[c.charAt(i)] *= f;
            }

            return o.normalize();
        };

        o.toString = function () {
            if (o.a >= 1.0) {
                return "rgb(" + [o.r, o.g, o.b].join(",") + ")";
            } else {
                return "rgba(" + [o.r, o.g, o.b, o.a].join(",") + ")";
            }
        };

        o.normalize = function () {
            function clamp(min, value, max) {
                return value < min ? min : (value > max ? max : value);
            }

            o.r = clamp(0, parseInt(o.r), 255);
            o.g = clamp(0, parseInt(o.g), 255);
            o.b = clamp(0, parseInt(o.b), 255);
            o.a = clamp(0, o.a, 1);
            return o;
        };

        o.clone = function () {
            return color.make(o.r, o.b, o.g, o.a);
        };

        return o.normalize();
    };

    // extract CSS color property from element, going up in the DOM
    // if it's "transparent". Takes a raw DOM element.
    color.extract = function (elem, css) {
        var c;

        do {
            var camel = css.replace(/-([a-z])/g, function(_, ch) { return ch.toUpperCase(); });
            c = (elem.style[camel] || getComputedStyle(elem)[css] || '').toLowerCase();
            // keep going until we find an element that has color, or
            // we hit the body or root (have no parent)
            if (c !== '' && c !== 'transparent') {
                break;
            }

            elem = elem.parentElement;
        } while (elem != null && elem.nodeName.toLowerCase() !== "body");

        // catch Safari's way of signalling transparent
        if (c === "rgba(0, 0, 0, 0)") {
            c = "transparent";
        }

        return color.parse(c);
    };

    // parse CSS color string (like "rgb(10, 32, 43)" or "#fff"),
    // returns color object, if parsing failed, you get black (0, 0,
    // 0) out
    color.parse = function (str) {
        var res, m = color.make;

        // Look for rgb(num,num,num)
        res = /rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(str);
        if (res) {
            return m(parseInt(res[1], 10), parseInt(res[2], 10), parseInt(res[3], 10));
        }

        // Look for rgba(num,num,num,num)
        res = /rgba\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(str);
        if (res) {
            return m(parseInt(res[1], 10), parseInt(res[2], 10), parseInt(res[3], 10), parseFloat(res[4]));
        }

        // Look for rgb(num%,num%,num%)
        res = /rgb\(\s*([0-9]+(?:\.[0-9]+)?)%\s*,\s*([0-9]+(?:\.[0-9]+)?)%\s*,\s*([0-9]+(?:\.[0-9]+)?)%\s*\)/.exec(str);
        if (res) {
            return m(parseFloat(res[1]) * 2.55, parseFloat(res[2]) * 2.55, parseFloat(res[3]) * 2.55);
        }

        // Look for rgba(num%,num%,num%,num)
        res = /rgba\(\s*([0-9]+(?:\.[0-9]+)?)%\s*,\s*([0-9]+(?:\.[0-9]+)?)%\s*,\s*([0-9]+(?:\.[0-9]+)?)%\s*,\s*([0-9]+(?:\.[0-9]+)?)\s*\)/.exec(str);
        if (res) {
            return m(parseFloat(res[1]) * 2.55, parseFloat(res[2]) * 2.55, parseFloat(res[3]) * 2.55, parseFloat(res[4]));
        }

        // Look for #a0b1c2
        res = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(str);
        if (res) {
            return m(parseInt(res[1], 16), parseInt(res[2], 16), parseInt(res[3], 16));
        }

        // Look for #fff
        res = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(str);
        if (res) {
            return m(parseInt(res[1] + res[1], 16), parseInt(res[2] + res[2], 16), parseInt(res[3] + res[3], 16));
        }

        // Otherwise, we're most likely dealing with a named color
        var name = (str || '').trim().toLowerCase();
        if (name === "transparent") {
            return m(255, 255, 255, 0);
        } else {
            // default to black
            res = lookupColors[name] || [0, 0, 0];
            return m(res[0], res[1], res[2]);
        }
    };

    var lookupColors = {
        aqua: [0, 255, 255],
        azure: [240, 255, 255],
        beige: [245, 245, 220],
        black: [0, 0, 0],
        blue: [0, 0, 255],
        brown: [165, 42, 42],
        cyan: [0, 255, 255],
        darkblue: [0, 0, 139],
        darkcyan: [0, 139, 139],
        darkgrey: [169, 169, 169],
        darkgreen: [0, 100, 0],
        darkkhaki: [189, 183, 107],
        darkmagenta: [139, 0, 139],
        darkolivegreen: [85, 107, 47],
        darkorange: [255, 140, 0],
        darkorchid: [153, 50, 204],
        darkred: [139, 0, 0],
        darksalmon: [233, 150, 122],
        darkviolet: [148, 0, 211],
        fuchsia: [255, 0, 255],
        gold: [255, 215, 0],
        green: [0, 128, 0],
        indigo: [75, 0, 130],
        khaki: [240, 230, 140],
        lightblue: [173, 216, 230],
        lightcyan: [224, 255, 255],
        lightgreen: [144, 238, 144],
        lightgrey: [211, 211, 211],
        lightpink: [255, 182, 193],
        lightyellow: [255, 255, 224],
        lime: [0, 255, 0],
        magenta: [255, 0, 255],
        maroon: [128, 0, 0],
        navy: [0, 0, 128],
        olive: [128, 128, 0],
        orange: [255, 165, 0],
        pink: [255, 192, 203],
        purple: [128, 0, 128],
        violet: [128, 0, 128],
        red: [255, 0, 0],
        silver: [192, 192, 192],
        white: [255, 255, 255],
        yellow: [255, 255, 0]
    };

    var saturated = {
        saturate: function (a) {
            if (a === Infinity) {
                return Number.MAX_VALUE;
            }

            if (a === -Infinity) {
                return -Number.MAX_VALUE;
            }

            return a;
        },
        delta: function(min, max, noTicks) {
            return ((max - min) / noTicks) === Infinity ? (max / noTicks - min / noTicks) : (max - min) / noTicks
        },
        multiply: function (a, b) {
            return saturated.saturate(a * b);
        },
        // returns c * bInt * a. Beahves properly in the case where c is negative
        // and bInt * a is bigger that Number.MAX_VALUE (Infinity)
        multiplyAdd: function (a, bInt, c) {
            if (isFinite(a * bInt)) {
                return saturated.saturate(a * bInt + c);
            } else {
                var result = c;

                for (var i = 0; i < bInt; i++) {
                    result += a;
                }

                return saturated.saturate(result);
            }
        },
        // round to nearby lower multiple of base
        floorInBase: function(n, base) {
            return base * Math.floor(n / base);
        }
    };

    var uiConstants = {
        SNAPPING_CONSTANT: 20,
        PANHINT_LENGTH_CONSTANT: 10,
        MINOR_TICKS_COUNT_CONSTANT: 4,
        TICK_LENGTH_CONSTANT: 10,
        ZOOM_DISTANCE_MARGIN: 25
    };

    /**
    ## jquery.flot.drawSeries.js

    This plugin is used by flot for drawing lines, plots, bars or area.

    ### Public methods
    */


    function DrawSeries() {
            function plotLine(datapoints, xoffset, yoffset, axisx, axisy, ctx, steps) {
                var points = datapoints.points,
                    ps = datapoints.pointsize,
                    prevx = null,
                    prevy = null;
                var x1 = 0.0,
                    y1 = 0.0,
                    x2 = 0.0,
                    y2 = 0.0,
                    mx = null,
                    my = null,
                    i = 0;

                var initPoints = function (i) {
                    x1 = points[i - ps];
                    y1 = points[i - ps + 1];
                    x2 = points[i];
                    y2 = points[i + 1];
                };

                var handleSteps = function () {
                    if (mx !== null && my !== null) {
                        // if middle point exists, transfer p2 -> p1 and p1 -> mp
                        x2 = x1;
                        y2 = y1;
                        x1 = mx;
                        y1 = my;

                        // 'remove' middle point
                        mx = null;
                        my = null;

                        return true;
                    } else if (y1 !== y2 && x1 !== x2) {
                        // create a middle point
                        y2 = y1;
                        mx = x2;
                        my = y1;
                    }

                    return false;
                };

                var handleYMinClipping = function () {
                    if (y1 <= y2 && y1 < axisy.min) {
                        if (y2 < axisy.min) {
                            // line segment is outside
                            return true;
                        }
                        // compute new intersection point
                        x1 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.min;
                    } else if (y2 <= y1 && y2 < axisy.min) {
                        if (y1 < axisy.min) {
                            return true;
                        }

                        x2 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.min;
                    }
                };

                var handleYMaxClipping = function () {
                    if (y1 >= y2 && y1 > axisy.max) {
                        if (y2 > axisy.max) {
                            return true;
                        }

                        x1 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.max;
                    } else if (y2 >= y1 && y2 > axisy.max) {
                        if (y1 > axisy.max) {
                            return true;
                        }

                        x2 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.max;
                    }
                };

                var handleXMinClipping = function () {
                    if (x1 <= x2 && x1 < axisx.min) {
                        if (x2 < axisx.min) {
                            return true;
                        }

                        y1 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.min;
                    } else if (x2 <= x1 && x2 < axisx.min) {
                        if (x1 < axisx.min) {
                            return true;
                        }

                        y2 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.min;
                    }
                };

                var handleXMaxClipping = function () {
                    if (x1 >= x2 && x1 > axisx.max) {
                        if (x2 > axisx.max) {
                            return true;
                        }

                        y1 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.max;
                    } else if (x2 >= x1 && x2 > axisx.max) {
                        if (x1 > axisx.max) {
                            return true;
                        }

                        y2 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.max;
                    }
                };

                var drawLine = function () {
                    if (x1 !== prevx || y1 !== prevy) {
                        ctx.moveTo(axisx.p2c(x1) + xoffset, axisy.p2c(y1) + yoffset);
                    }

                    prevx = x2;
                    prevy = y2;
                    ctx.lineTo(axisx.p2c(x2) + xoffset, axisy.p2c(y2) + yoffset);
                };

                ctx.beginPath();
                for (i = ps; i < points.length; i += ps) {
                    initPoints(i);

                    if (x1 === null || x2 === null) {
                        mx = null;
                        my = null;
                        continue;
                    }

                    if (isNaN(x1) || isNaN(x2) || isNaN(y1) || isNaN(y2)) {
                        prevx = null;
                        prevy = null;
                        continue;
                    }

                    if (steps) {
                        var hadMiddlePoint = handleSteps();
                        if (hadMiddlePoint) {
                            // Subtract pointsize from i to have current point p1 handled again.
                            i -= ps;
                        }
                    }
                    if (handleYMinClipping()) continue;
                    if (handleYMaxClipping()) continue;
                    if (handleXMinClipping()) continue;
                    if (handleXMaxClipping()) continue;

                    drawLine();
                }

                // Connects last two points in case middle point exists after the loop.
                if (mx !== null && my !== null) {
                    initPoints(i);
                    handleSteps();

                    if (!handleYMinClipping() &&
                        !handleYMaxClipping() &&
                        !handleXMinClipping() &&
                        !handleXMaxClipping()) {
                        drawLine();
                    }
                }

                ctx.stroke();
            }

            function plotLineArea(datapoints, axisx, axisy, fillTowards, ctx, steps) {
                var points = datapoints.points,
                    ps = datapoints.pointsize,
                    bottom = fillTowards > axisy.min ? Math.min(axisy.max, fillTowards) : axisy.min,
                    i = 0,
                    ypos = 1,
                    areaOpen = false,
                    segmentStart = 0,
                    segmentEnd = 0,
                    mx = null,
                    my = null;

                // we process each segment in two turns, first forward
                // direction to sketch out top, then once we hit the
                // end we go backwards to sketch the bottom
                while (true) {
                    if (ps > 0 && i > points.length + ps) {
                        break;
                    }

                    i += ps; // ps is negative if going backwards

                    var x1 = points[i - ps],
                        y1 = points[i - ps + ypos],
                        x2 = points[i],
                        y2 = points[i + ypos];

                    if (ps === -2) {
                        /* going backwards and no value for the bottom provided in the series*/
                        y1 = y2 = bottom;
                    }

                    if (areaOpen) {
                        if (ps > 0 && x1 != null && x2 == null) {
                            // at turning point
                            segmentEnd = i;
                            ps = -ps;
                            ypos = 2;
                            continue;
                        }

                        if (ps < 0 && i === segmentStart + ps) {
                            // done with the reverse sweep
                            ctx.fill();
                            areaOpen = false;
                            ps = -ps;
                            ypos = 1;
                            i = segmentStart = segmentEnd + ps;
                            continue;
                        }
                    }

                    if (x1 == null || x2 == null) {
                        mx = null;
                        my = null;
                        continue;
                    }

                    if (steps) {
                        if (mx !== null && my !== null) {
                            // if middle point exists, transfer p2 -> p1 and p1 -> mp
                            x2 = x1;
                            y2 = y1;
                            x1 = mx;
                            y1 = my;

                            // 'remove' middle point
                            mx = null;
                            my = null;

                            // subtract pointsize from i to have current point p1 handled again
                            i -= ps;
                        } else if (y1 !== y2 && x1 !== x2) {
                            // create a middle point
                            y2 = y1;
                            mx = x2;
                            my = y1;
                        }
                    }

                    // clip x values

                    // clip with xmin
                    if (x1 <= x2 && x1 < axisx.min) {
                        if (x2 < axisx.min) {
                            continue;
                        }

                        y1 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.min;
                    } else if (x2 <= x1 && x2 < axisx.min) {
                        if (x1 < axisx.min) {
                            continue;
                        }

                        y2 = (axisx.min - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.min;
                    }

                    // clip with xmax
                    if (x1 >= x2 && x1 > axisx.max) {
                        if (x2 > axisx.max) {
                            continue;
                        }

                        y1 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x1 = axisx.max;
                    } else if (x2 >= x1 && x2 > axisx.max) {
                        if (x1 > axisx.max) {
                            continue;
                        }

                        y2 = (axisx.max - x1) / (x2 - x1) * (y2 - y1) + y1;
                        x2 = axisx.max;
                    }

                    if (!areaOpen) {
                        // open area
                        ctx.beginPath();
                        ctx.moveTo(axisx.p2c(x1), axisy.p2c(bottom));
                        areaOpen = true;
                    }

                    // now first check the case where both is outside
                    if (y1 >= axisy.max && y2 >= axisy.max) {
                        ctx.lineTo(axisx.p2c(x1), axisy.p2c(axisy.max));
                        ctx.lineTo(axisx.p2c(x2), axisy.p2c(axisy.max));
                        continue;
                    } else if (y1 <= axisy.min && y2 <= axisy.min) {
                        ctx.lineTo(axisx.p2c(x1), axisy.p2c(axisy.min));
                        ctx.lineTo(axisx.p2c(x2), axisy.p2c(axisy.min));
                        continue;
                    }

                    // else it's a bit more complicated, there might
                    // be a flat maxed out rectangle first, then a
                    // triangular cutout or reverse; to find these
                    // keep track of the current x values
                    var x1old = x1,
                        x2old = x2;

                    // clip the y values, without shortcutting, we
                    // go through all cases in turn

                    // clip with ymin
                    if (y1 <= y2 && y1 < axisy.min && y2 >= axisy.min) {
                        x1 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.min;
                    } else if (y2 <= y1 && y2 < axisy.min && y1 >= axisy.min) {
                        x2 = (axisy.min - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.min;
                    }

                    // clip with ymax
                    if (y1 >= y2 && y1 > axisy.max && y2 <= axisy.max) {
                        x1 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y1 = axisy.max;
                    } else if (y2 >= y1 && y2 > axisy.max && y1 <= axisy.max) {
                        x2 = (axisy.max - y1) / (y2 - y1) * (x2 - x1) + x1;
                        y2 = axisy.max;
                    }

                    // if the x value was changed we got a rectangle
                    // to fill
                    if (x1 !== x1old) {
                        ctx.lineTo(axisx.p2c(x1old), axisy.p2c(y1));
                        // it goes to (x1, y1), but we fill that below
                    }

                    // fill triangular section, this sometimes result
                    // in redundant points if (x1, y1) hasn't changed
                    // from previous line to, but we just ignore that
                    ctx.lineTo(axisx.p2c(x1), axisy.p2c(y1));
                    ctx.lineTo(axisx.p2c(x2), axisy.p2c(y2));

                    // fill the other rectangle if it's there
                    if (x2 !== x2old) {
                        ctx.lineTo(axisx.p2c(x2), axisy.p2c(y2));
                        ctx.lineTo(axisx.p2c(x2old), axisy.p2c(y2));
                    }
                }
            }

            /**
            - drawSeriesLines(series, ctx, plotOffset, plotWidth, plotHeight, drawSymbol, getColorOrGradient)

             This function is used for drawing lines or area fill.  In case the series has line decimation function
             attached, before starting to draw, as an optimization the points will first be decimated.

             The series parameter contains the series to be drawn on ctx context. The plotOffset, plotWidth and
             plotHeight are the corresponding parameters of flot used to determine the drawing surface.
             The function getColorOrGradient is used to compute the fill style of lines and area.
            */
            function drawSeriesLines(series, ctx, plotOffset, plotWidth, plotHeight, drawSymbol, getColorOrGradient) {
                ctx.save();
                ctx.translate(plotOffset.left, plotOffset.top);
                ctx.lineJoin = "round";

                if (series.lines.dashes && ctx.setLineDash) {
                    ctx.setLineDash(series.lines.dashes);
                }

                var datapoints = {
                    format: series.datapoints.format,
                    points: series.datapoints.points,
                    pointsize: series.datapoints.pointsize
                };

                if (series.decimate) {
                    datapoints.points = series.decimate(series, series.xaxis.min, series.xaxis.max, plotWidth, series.yaxis.min, series.yaxis.max, plotHeight);
                }

                var lw = series.lines.lineWidth,
                    sw = series.shadowSize;

                // draw shadow as a thick and thin line with transparency
                if (lw > 0 && sw > 0) {
                    var angle = Math.PI / 18;
                    ctx.lineWidth = sw;
                    ctx.strokeStyle = "rgba(0,0,0,0.1)";
                    plotLine(datapoints, Math.sin(angle) * (lw / 2 + sw / 2), Math.cos(angle) * (lw / 2 + sw / 2), series.xaxis, series.yaxis, ctx, series.lines.steps);
                    ctx.lineWidth = sw / 2;
                    plotLine(datapoints, Math.sin(angle) * (lw / 2 + sw / 4), Math.cos(angle) * (lw / 2 + sw / 4), series.xaxis, series.yaxis, ctx, series.lines.steps);
                }

                ctx.lineWidth = lw;
                ctx.strokeStyle = series.color;
                var fillStyle = getFillStyle(series.lines, series.color, 0, plotHeight, getColorOrGradient);
                if (fillStyle) {
                    ctx.fillStyle = fillStyle;
                    plotLineArea(datapoints, series.xaxis, series.yaxis, series.lines.fillTowards || 0, ctx, series.lines.steps);
                }

                if (lw > 0) {
                    plotLine(datapoints, 0, 0, series.xaxis, series.yaxis, ctx, series.lines.steps);
                }

                ctx.restore();
            }

            /**
            - drawSeriesPoints(series, ctx, plotOffset, plotWidth, plotHeight, drawSymbol, getColorOrGradient)

             This function is used for drawing points using a given symbol. In case the series has points decimation
             function attached, before starting to draw, as an optimization the points will first be decimated.

             The series parameter contains the series to be drawn on ctx context. The plotOffset, plotWidth and
             plotHeight are the corresponding parameters of flot used to determine the drawing surface.
             The function drawSymbol is used to compute and draw the symbol chosen for the points.
            */
            function drawSeriesPoints(series, ctx, plotOffset, plotWidth, plotHeight, drawSymbol, getColorOrGradient) {
                function drawCircle(ctx, x, y, radius, shadow, fill) {
                    ctx.moveTo(x + radius, y);
                    ctx.arc(x, y, radius, 0, shadow ? Math.PI : Math.PI * 2, false);
                }
                drawCircle.fill = true;
                function plotPoints(datapoints, radius, fill, offset, shadow, axisx, axisy, drawSymbolFn) {
                    var points = datapoints.points,
                        ps = datapoints.pointsize;

                    ctx.beginPath();
                    for (var i = 0; i < points.length; i += ps) {
                        var x = points[i],
                            y = points[i + 1];
                        if (x == null || x < axisx.min || x > axisx.max || y < axisy.min || y > axisy.max) {
                            continue;
                        }

                        x = axisx.p2c(x);
                        y = axisy.p2c(y) + offset;

                        drawSymbolFn(ctx, x, y, radius, shadow, fill);
                    }
                    if (drawSymbolFn.fill && !shadow) {
                        ctx.fill();
                    }
                    ctx.stroke();
                }

                ctx.save();
                ctx.translate(plotOffset.left, plotOffset.top);

                var datapoints = {
                    format: series.datapoints.format,
                    points: series.datapoints.points,
                    pointsize: series.datapoints.pointsize
                };

                if (series.decimatePoints) {
                    datapoints.points = series.decimatePoints(series, series.xaxis.min, series.xaxis.max, plotWidth, series.yaxis.min, series.yaxis.max, plotHeight);
                }

                var lw = series.points.lineWidth,
                    radius = series.points.radius,
                    symbol = series.points.symbol,
                    drawSymbolFn;

                if (symbol === 'circle') {
                    drawSymbolFn = drawCircle;
                } else if (typeof symbol === 'string' && drawSymbol && drawSymbol[symbol]) {
                    drawSymbolFn = drawSymbol[symbol];
                } else if (typeof drawSymbol === 'function') {
                    drawSymbolFn = drawSymbol;
                }

                // If the user sets the line width to 0, we change it to a very
                // small value. A line width of 0 seems to force the default of 1.

                if (lw === 0) {
                    lw = 0.0001;
                }

                ctx.lineWidth = lw;
                ctx.fillStyle = getFillStyle(series.points, series.color, null, null, getColorOrGradient);
                ctx.strokeStyle = series.color;
                plotPoints(datapoints, radius,
                    true, 0, false,
                    series.xaxis, series.yaxis, drawSymbolFn);
                ctx.restore();
            }

            function drawBar(x, y, b, barLeft, barRight, fillStyleCallback, axisx, axisy, c, horizontal, lineWidth) {
                var left = x + barLeft,
                    right = x + barRight,
                    bottom = b, top = y,
                    drawLeft, drawRight, drawTop, drawBottom = false,
                    tmp;

                drawLeft = drawRight = drawTop = true;

                // in horizontal mode, we start the bar from the left
                // instead of from the bottom so it appears to be
                // horizontal rather than vertical
                if (horizontal) {
                    drawBottom = drawRight = drawTop = true;
                    drawLeft = false;
                    left = b;
                    right = x;
                    top = y + barLeft;
                    bottom = y + barRight;

                    // account for negative bars
                    if (right < left) {
                        tmp = right;
                        right = left;
                        left = tmp;
                        drawLeft = true;
                        drawRight = false;
                    }
                } else {
                    drawLeft = drawRight = drawTop = true;
                    drawBottom = false;
                    left = x + barLeft;
                    right = x + barRight;
                    bottom = b;
                    top = y;

                    // account for negative bars
                    if (top < bottom) {
                        tmp = top;
                        top = bottom;
                        bottom = tmp;
                        drawBottom = true;
                        drawTop = false;
                    }
                }

                // clip
                if (right < axisx.min || left > axisx.max ||
                    top < axisy.min || bottom > axisy.max) {
                    return;
                }

                if (left < axisx.min) {
                    left = axisx.min;
                    drawLeft = false;
                }

                if (right > axisx.max) {
                    right = axisx.max;
                    drawRight = false;
                }

                if (bottom < axisy.min) {
                    bottom = axisy.min;
                    drawBottom = false;
                }

                if (top > axisy.max) {
                    top = axisy.max;
                    drawTop = false;
                }

                left = axisx.p2c(left);
                bottom = axisy.p2c(bottom);
                right = axisx.p2c(right);
                top = axisy.p2c(top);

                // fill the bar
                if (fillStyleCallback) {
                    c.fillStyle = fillStyleCallback(bottom, top);
                    c.fillRect(left, top, right - left, bottom - top);
                }

                // draw outline
                if (lineWidth > 0 && (drawLeft || drawRight || drawTop || drawBottom)) {
                    c.beginPath();

                    // FIXME: inline moveTo is buggy with excanvas
                    c.moveTo(left, bottom);
                    if (drawLeft) {
                        c.lineTo(left, top);
                    } else {
                        c.moveTo(left, top);
                    }

                    if (drawTop) {
                        c.lineTo(right, top);
                    } else {
                        c.moveTo(right, top);
                    }

                    if (drawRight) {
                        c.lineTo(right, bottom);
                    } else {
                        c.moveTo(right, bottom);
                    }

                    if (drawBottom) {
                        c.lineTo(left, bottom);
                    } else {
                        c.moveTo(left, bottom);
                    }

                    c.stroke();
                }
            }

            /**
            - drawSeriesBars(series, ctx, plotOffset, plotWidth, plotHeight, drawSymbol, getColorOrGradient)

             This function is used for drawing series represented as bars. In case the series has decimation
             function attached, before starting to draw, as an optimization the points will first be decimated.

             The series parameter contains the series to be drawn on ctx context. The plotOffset, plotWidth and
             plotHeight are the corresponding parameters of flot used to determine the drawing surface.
             The function getColorOrGradient is used to compute the fill style of bars.
            */
            function drawSeriesBars(series, ctx, plotOffset, plotWidth, plotHeight, drawSymbol, getColorOrGradient) {
                function plotBars(datapoints, barLeft, barRight, fillStyleCallback, axisx, axisy) {
                    var points = datapoints.points,
                        ps = datapoints.pointsize,
                        fillTowards = series.bars.fillTowards || 0,
                        defaultBottom = fillTowards > axisy.min ? Math.min(axisy.max, fillTowards) : axisy.min;

                    for (var i = 0; i < points.length; i += ps) {
                        if (points[i] == null) {
                            continue;
                        }

                        // Use third point as bottom if pointsize is 3
                        var bottom = ps === 3 ? points[i + 2] : defaultBottom;
                        drawBar(points[i], points[i + 1], bottom, barLeft, barRight, fillStyleCallback, axisx, axisy, ctx, series.bars.horizontal, series.bars.lineWidth);
                    }
                }

                ctx.save();
                ctx.translate(plotOffset.left, plotOffset.top);

                var datapoints = {
                    format: series.datapoints.format,
                    points: series.datapoints.points,
                    pointsize: series.datapoints.pointsize
                };

                if (series.decimate) {
                    datapoints.points = series.decimate(series, series.xaxis.min, series.xaxis.max, plotWidth);
                }

                ctx.lineWidth = series.bars.lineWidth;
                ctx.strokeStyle = series.color;

                var barLeft;
                var barWidth = series.bars.barWidth[0] || series.bars.barWidth;
                switch (series.bars.align) {
                    case "left":
                        barLeft = 0;
                        break;
                    case "right":
                        barLeft = -barWidth;
                        break;
                    default:
                        barLeft = -barWidth / 2;
                }

                var fillStyleCallback = series.bars.fill ? function(bottom, top) {
                    return getFillStyle(series.bars, series.color, bottom, top, getColorOrGradient);
                } : null;

                plotBars(datapoints, barLeft, barLeft + barWidth, fillStyleCallback, series.xaxis, series.yaxis);
                ctx.restore();
            }

            function getFillStyle(filloptions, seriesColor, bottom, top, getColorOrGradient) {
                var fill = filloptions.fill;
                if (!fill) {
                    return null;
                }

                if (filloptions.fillColor) {
                    return getColorOrGradient(filloptions.fillColor, bottom, top, seriesColor);
                }

                var c = color.parse(seriesColor);
                c.a = typeof fill === "number" ? fill : 0.4;
                c.normalize();
                return c.toString();
            }

            this.drawSeriesLines = drawSeriesLines;
            this.drawSeriesPoints = drawSeriesPoints;
            this.drawSeriesBars = drawSeriesBars;
            this.drawBar = drawBar;
        }
    var drawSeries = new DrawSeries();

    /* Javascript plotting library for jQuery, version 3.0.0.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    */


        function defaultTickGenerator(axis) {
            var ticks = [],
                start = saturated.saturate(saturated.floorInBase(axis.min, axis.tickSize)),
                i = 0,
                v = Number.NaN,
                prev;

            if (start === -Number.MAX_VALUE) {
                ticks.push(start);
                start = saturated.floorInBase(axis.min + axis.tickSize, axis.tickSize);
            }

            do {
                prev = v;
                //v = start + i * axis.tickSize;
                v = saturated.multiplyAdd(axis.tickSize, i, start);
                ticks.push(v);
                ++i;
            } while (v < axis.max && v !== prev);

            return ticks;
        }

        function defaultTickFormatter(value, axis, precision) {
            var oldTickDecimals = axis.tickDecimals,
                expPosition = ("" + value).indexOf("e");

            if (expPosition !== -1) {
                return expRepTickFormatter(value, axis, precision);
            }

            if (precision > 0) {
                axis.tickDecimals = precision;
            }

            var factor = axis.tickDecimals ? parseFloat('1e' + axis.tickDecimals) : 1,
                formatted = "" + Math.round(value * factor) / factor;

            // If tickDecimals was specified, ensure that we have exactly that
            // much precision; otherwise default to the value's own precision.
            if (axis.tickDecimals != null) {
                var decimal = formatted.indexOf("."),
                    decimalPrecision = decimal === -1 ? 0 : formatted.length - decimal - 1;
                if (decimalPrecision < axis.tickDecimals) {
                    var decimals = ("" + factor).substr(1, axis.tickDecimals - decimalPrecision);
                    formatted = (decimalPrecision ? formatted : formatted + ".") + decimals;
                }
            }

            axis.tickDecimals = oldTickDecimals;
            return formatted;
        }
        function expRepTickFormatter(value, axis, precision) {
            var expPosition = ("" + value).indexOf("e"),
                exponentValue = parseInt(("" + value).substr(expPosition + 1)),
                tenExponent = expPosition !== -1 ? exponentValue : (value > 0 ? Math.floor(Math.log(value) / Math.LN10) : 0),
                roundWith = parseFloat('1e' + tenExponent),
                x = value / roundWith;

            if (precision) {
                var updatedPrecision = recomputePrecision(value, precision);
                return (value / roundWith).toFixed(updatedPrecision) + 'e' + tenExponent;
            }

            if (axis.tickDecimals > 0) {
                return x.toFixed(recomputePrecision(value, axis.tickDecimals)) + 'e' + tenExponent;
            }
            return x.toFixed() + 'e' + tenExponent;
        }

        function recomputePrecision(num, precision) {
            //for numbers close to zero, the precision from flot will be a big number
            //while for big numbers, the precision will be negative
            var log10Value = Math.log(Math.abs(num)) * Math.LOG10E,
                newPrecision = Math.abs(log10Value + precision);

            return newPrecision <= 20 ? Math.floor(newPrecision) : 20;
        }

        ///////////////////////////////////////////////////////////////////////////
        // The top-level container for the entire plot.
        function Plot(placeholder, data_, options_, plugins) {
            // data is on the form:
            //   [ series1, series2 ... ]
            // where series is either just the data as [ [x1, y1], [x2, y2], ... ]
            // or { data: [ [x1, y1], [x2, y2], ... ], label: "some label", ... }

            var series = [],
                options = {
                    // the color theme used for graphs
                    colors: ["#edc240", "#afd8f8", "#cb4b4b", "#4da74d", "#9440ed"],
                    xaxis: {
                        show: null, // null = auto-detect, true = always, false = never
                        position: "bottom", // or "top"
                        mode: null, // null or "time"
                        font: null, // null (derived from CSS in placeholder) or object like { size: 11, lineHeight: 13, style: "italic", weight: "bold", family: "sans-serif", variant: "small-caps" }
                        color: null, // base color, labels, ticks
                        tickColor: null, // possibly different color of ticks, e.g. "rgba(0,0,0,0.15)"
                        transform: null, // null or f: number -> number to transform axis
                        inverseTransform: null, // if transform is set, this should be the inverse function
                        min: null, // min. value to show, null means set automatically
                        max: null, // max. value to show, null means set automatically
                        autoScaleMargin: null, // margin in % to add if autoScale option is on "loose" mode,
                        autoScale: "exact", // Available modes: "none", "loose", "exact", "sliding-window"
                        windowSize: null, // null or number. This is the size of sliding-window.
                        growOnly: null, // grow only, useful for smoother auto-scale, the scales will grow to accomodate data but won't shrink back.
                        ticks: null, // either [1, 3] or [[1, "a"], 3] or (fn: axis info -> ticks) or app. number of ticks for auto-ticks
                        tickFormatter: null, // fn: number -> string
                        showTickLabels: "major", // "none", "endpoints", "major", "all"
                        labelWidth: null, // size of tick labels in pixels
                        labelHeight: null,
                        reserveSpace: null, // whether to reserve space even if axis isn't shown
                        tickLength: null, // size in pixels of major tick marks
                        showMinorTicks: null, // true = show minor tick marks, false = hide minor tick marks
                        showTicks: null, // true = show tick marks, false = hide all tick marks
                        gridLines: null, // true = show grid lines, false = hide grid lines
                        alignTicksWithAxis: null, // axis number or null for no sync
                        tickDecimals: null, // no. of decimals, null means auto
                        tickSize: null, // number or [number, "unit"]
                        minTickSize: null, // number or [number, "unit"]
                        offset: { below: 0, above: 0 }, // the plot drawing offset. this is calculated by the flot.navigate for each axis
                        boxPosition: { centerX: 0, centerY: 0 } //position of the axis on the corresponding axis box
                    },
                    yaxis: {
                        autoScaleMargin: 0.02, // margin in % to add if autoScale option is on "loose" mode
                        autoScale: "loose", // Available modes: "none", "loose", "exact"
                        growOnly: null, // grow only, useful for smoother auto-scale, the scales will grow to accomodate data but won't shrink back.
                        position: "left", // or "right"
                        showTickLabels: "major", // "none", "endpoints", "major", "all"
                        offset: { below: 0, above: 0 }, // the plot drawing offset. this is calculated by the flot.navigate for each axis
                        boxPosition: { centerX: 0, centerY: 0 } //position of the axis on the corresponding axis box
                    },
                    xaxes: [],
                    yaxes: [],
                    series: {
                        points: {
                            show: false,
                            radius: 3,
                            lineWidth: 2, // in pixels
                            fill: true,
                            fillColor: "#ffffff",
                            symbol: 'circle' // or callback
                        },
                        lines: {
                            // we don't put in show: false so we can see
                            // whether lines were actively disabled
                            lineWidth: 1, // in pixels
                            fill: false,
                            fillColor: null,
                            steps: false
                            // Omit 'zero', so we can later default its value to
                            // match that of the 'fill' option.
                        },
                        bars: {
                            show: false,
                            lineWidth: 2, // in pixels
                            // barWidth: number or [number, absolute]
                            // when 'absolute' is false, 'number' is relative to the minimum distance between points for the series
                            // when 'absolute' is true, 'number' is considered to be in units of the x-axis
                            horizontal: false,
                            barWidth: 0.8,
                            fill: true,
                            fillColor: null,
                            align: "left", // "left", "right", or "center"
                            zero: true
                        },
                        shadowSize: 3,
                        highlightColor: null
                    },
                    grid: {
                        show: true,
                        aboveData: false,
                        color: "#545454", // primary color used for outline and labels
                        backgroundColor: null, // null for transparent, else color
                        borderColor: null, // set if different from the grid color
                        tickColor: null, // color for the ticks, e.g. "rgba(0,0,0,0.15)"
                        margin: 0, // distance from the canvas edge to the grid
                        labelMargin: 5, // in pixels
                        axisMargin: 8, // in pixels
                        borderWidth: 1, // in pixels
                        minBorderMargin: null, // in pixels, null means taken from points radius
                        markings: null, // array of ranges or fn: axes -> array of ranges
                        markingsColor: "#f4f4f4",
                        markingsLineWidth: 2,
                        // interactive stuff
                        clickable: false,
                        hoverable: false,
                        autoHighlight: true, // highlight in case mouse is near
                        mouseActiveRadius: 15 // how far the mouse can be away to activate an item
                    },
                    interaction: {
                        redrawOverlayInterval: 1000 / 60 // time between updates, -1 means in same flow
                    },
                    hooks: {}
                },
                surface = null, // the canvas for the plot itself
                overlay = null, // canvas for interactive stuff on top of plot
                eventHolder = null, // DOM element that events should be bound to
                ctx = null,
                octx = null,
                xaxes = [],
                yaxes = [],
                plotOffset = {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                },
                plotWidth = 0,
                plotHeight = 0,
                hooks = {
                    processOptions: [],
                    processRawData: [],
                    processDatapoints: [],
                    processOffset: [],
                    setupGrid: [],
                    adjustSeriesDataRange: [],
                    setRange: [],
                    drawBackground: [],
                    drawSeries: [],
                    drawAxis: [],
                    draw: [],
                    findNearbyItems: [],
                    axisReserveSpace: [],
                    bindEvents: [],
                    drawOverlay: [],
                    resize: [],
                    shutdown: []
                },
                plot = this;

            var eventManager = {};

            // interactive features

            var redrawTimeout = null;

            // public functions
            plot.setData = setData;
            plot.setupGrid = setupGrid;
            plot.draw = draw;
            plot.getPlaceholder = function() {
                return placeholder;
            };
            plot.getCanvas = function() {
                return surface.element;
            };
            plot.getSurface = function() {
                return surface;
            };
            plot.getEventHolder = function() {
                return eventHolder;
            };
            plot.getPlotOffset = function() {
                return plotOffset;
            };
            plot.width = function() {
                return plotWidth;
            };
            plot.height = function() {
                return plotHeight;
            };
            plot.offset = function() {
                var rect = eventHolder.getBoundingClientRect();
                var o = { left: rect.left + window.scrollX, top: rect.top + window.scrollY };
                o.left += plotOffset.left;
                o.top += plotOffset.top;
                return o;
            };
            plot.getData = function() {
                return series;
            };
            plot.getAxes = function() {
                var res = {};
                xaxes.concat(yaxes).forEach(function(axis) {
                    if (axis) {
                        res[axis.direction + (axis.n !== 1 ? axis.n : "") + "axis"] = axis;
                    }
                });
                return res;
            };
            plot.getXAxes = function() {
                return xaxes;
            };
            plot.getYAxes = function() {
                return yaxes;
            };
            plot.c2p = canvasToCartesianAxisCoords;
            plot.p2c = cartesianAxisToCanvasCoords;
            plot.getOptions = function() {
                return options;
            };
            plot.triggerRedrawOverlay = triggerRedrawOverlay;
            plot.pointOffset = function(point) {
                return {
                    left: parseInt(xaxes[axisNumber(point, "x") - 1].p2c(+point.x) + plotOffset.left, 10),
                    top: parseInt(yaxes[axisNumber(point, "y") - 1].p2c(+point.y) + plotOffset.top, 10)
                };
            };
            plot.shutdown = shutdown;
            plot.destroy = function() {
                shutdown();
                removeData(placeholder, "plot");
                placeholder.innerHTML = '';

                series = [];
                options = null;
                surface = null;
                overlay = null;
                eventHolder = null;
                ctx = null;
                octx = null;
                xaxes = [];
                yaxes = [];
                hooks = null;
                plot = null;
            };

            plot.resize = function() {
                var w = width(placeholder),
                    h = height(placeholder);
                surface.resize(w, h);
                overlay.resize(w, h);

                executeHooks(hooks.resize, [w, h]);
            };

            plot.clearTextCache = function () {
                surface.clearCache();
                overlay.clearCache();
            };

            plot.autoScaleAxis = autoScaleAxis;
            plot.computeRangeForDataSeries = computeRangeForDataSeries;
            plot.adjustSeriesDataRange = adjustSeriesDataRange;
            plot.findNearbyItem = findNearbyItem;
            plot.findNearbyItems = findNearbyItems;
            plot.findNearbyInterpolationPoint = findNearbyInterpolationPoint;
            plot.computeValuePrecision = computeValuePrecision;
            plot.computeTickSize = computeTickSize;
            plot.addEventHandler = addEventHandler;

            // public attributes
            plot.hooks = hooks;

            // initialize
            var MINOR_TICKS_COUNT_CONSTANT = uiConstants.MINOR_TICKS_COUNT_CONSTANT;
            var TICK_LENGTH_CONSTANT = uiConstants.TICK_LENGTH_CONSTANT;
            initPlugins();
            setupCanvases();
            parseOptions(options_);
            setData(data_);
            setupGrid(true);
            draw();
            bindEvents();

            function executeHooks(hook, args) {
                args = [plot].concat(args);
                for (var i = 0; i < hook.length; ++i) {
                    hook[i].apply(this, args);
                }
            }

            function initPlugins() {
                // References to key classes, allowing plugins to modify them

                var classes = {
                    Canvas: Canvas
                };

                for (var i = 0; i < plugins.length; ++i) {
                    var p = plugins[i];
                    p.init(plot, classes);
                    if (p.options) {
                        extend(true, options, p.options);
                    }
                }
            }

            function parseOptions(opts) {
                extend(true, options, opts);

                // extend merges arrays, rather than replacing them.  When less
                // colors are provided than the size of the default palette, we
                // end up with those colors plus the remaining defaults, which is
                // not expected behavior; avoid it by replacing them here.

                if (opts && opts.colors) {
                    options.colors = opts.colors;
                }

                if (options.xaxis.color == null) {
                    options.xaxis.color = color.parse(options.grid.color).scale('a', 0.22).toString();
                }

                if (options.yaxis.color == null) {
                    options.yaxis.color = color.parse(options.grid.color).scale('a', 0.22).toString();
                }

                if (options.xaxis.tickColor == null) {
                    // grid.tickColor for back-compatibility
                    options.xaxis.tickColor = options.grid.tickColor || options.xaxis.color;
                }

                if (options.yaxis.tickColor == null) {
                    // grid.tickColor for back-compatibility
                    options.yaxis.tickColor = options.grid.tickColor || options.yaxis.color;
                }

                if (options.grid.borderColor == null) {
                    options.grid.borderColor = options.grid.color;
                }

                if (options.grid.tickColor == null) {
                    options.grid.tickColor = color.parse(options.grid.color).scale('a', 0.22).toString();
                }

                // Fill in defaults for axis options, including any unspecified
                // font-spec fields, if a font-spec was provided.

                // If no x/y axis options were provided, create one of each anyway,
                // since the rest of the code assumes that they exist.

                var i, axisOptions, axisCount,
                    fontSize = css(placeholder, "font-size"),
                    fontSizeDefault = fontSize ? +fontSize.replace("px", "") : 13,
                    fontDefaults = {
                        style: css(placeholder, "font-style"),
                        size: Math.round(0.8 * fontSizeDefault),
                        variant: css(placeholder, "font-variant"),
                        weight: css(placeholder, "font-weight"),
                        family: css(placeholder, "font-family")
                    };

                axisCount = options.xaxes.length || 1;
                for (i = 0; i < axisCount; ++i) {
                    axisOptions = options.xaxes[i];
                    if (axisOptions && !axisOptions.tickColor) {
                        axisOptions.tickColor = axisOptions.color;
                    }

                    axisOptions = extend(true, {}, options.xaxis, axisOptions);
                    options.xaxes[i] = axisOptions;

                    if (axisOptions.font) {
                        axisOptions.font = extend({}, fontDefaults, axisOptions.font);
                        if (!axisOptions.font.color) {
                            axisOptions.font.color = axisOptions.color;
                        }
                        if (!axisOptions.font.lineHeight) {
                            axisOptions.font.lineHeight = Math.round(axisOptions.font.size * 1.15);
                        }
                    }
                }

                axisCount = options.yaxes.length || 1;
                for (i = 0; i < axisCount; ++i) {
                    axisOptions = options.yaxes[i];
                    if (axisOptions && !axisOptions.tickColor) {
                        axisOptions.tickColor = axisOptions.color;
                    }

                    axisOptions = extend(true, {}, options.yaxis, axisOptions);
                    options.yaxes[i] = axisOptions;

                    if (axisOptions.font) {
                        axisOptions.font = extend({}, fontDefaults, axisOptions.font);
                        if (!axisOptions.font.color) {
                            axisOptions.font.color = axisOptions.color;
                        }
                        if (!axisOptions.font.lineHeight) {
                            axisOptions.font.lineHeight = Math.round(axisOptions.font.size * 1.15);
                        }
                    }
                }

                // save options on axes for future reference
                for (i = 0; i < options.xaxes.length; ++i) {
                    getOrCreateAxis(xaxes, i + 1).options = options.xaxes[i];
                }

                for (i = 0; i < options.yaxes.length; ++i) {
                    getOrCreateAxis(yaxes, i + 1).options = options.yaxes[i];
                }

                //process boxPosition options used for axis.box size
                allAxes().forEach(function(axis) {
                    axis.boxPosition = axis.options.boxPosition || {centerX: 0, centerY: 0};
                });

                // add hooks from options
                for (var n in hooks) {
                    if (options.hooks[n] && options.hooks[n].length) {
                        hooks[n] = hooks[n].concat(options.hooks[n]);
                    }
                }

                executeHooks(hooks.processOptions, [options]);
            }

            function setData(d) {
                var oldseries = series;
                series = parseData(d);
                fillInSeriesOptions();
                processData(oldseries);
            }

            function parseData(d) {
                var res = [];
                for (var i = 0; i < d.length; ++i) {
                    var s = extend(true, {}, options.series);

                    if (d[i].data != null) {
                        s.data = d[i].data; // move the data instead of deep-copy
                        delete d[i].data;

                        extend(true, s, d[i]);

                        d[i].data = s.data;
                    } else {
                        s.data = d[i];
                    }

                    res.push(s);
                }

                return res;
            }

            function axisNumber(obj, coord) {
                var a = obj[coord + "axis"];
                if (typeof a === "object") {
                    // if we got a real axis, extract number
                    a = a.n;
                }

                if (typeof a !== "number") {
                    a = 1; // default to first axis
                }

                return a;
            }

            function allAxes() {
                // return flat array without annoying null entries
                return xaxes.concat(yaxes).filter(function(a) {
                    return a;
                });
            }

            // canvas to axis for cartesian axes
            function canvasToCartesianAxisCoords(pos) {
                // return an object with x/y corresponding to all used axes
                var res = {},
                    i, axis;
                for (i = 0; i < xaxes.length; ++i) {
                    axis = xaxes[i];
                    if (axis && axis.used) {
                        res["x" + axis.n] = axis.c2p(pos.left);
                    }
                }

                for (i = 0; i < yaxes.length; ++i) {
                    axis = yaxes[i];
                    if (axis && axis.used) {
                        res["y" + axis.n] = axis.c2p(pos.top);
                    }
                }

                if (res.x1 !== undefined) {
                    res.x = res.x1;
                }

                if (res.y1 !== undefined) {
                    res.y = res.y1;
                }

                return res;
            }

            // axis to canvas for cartesian axes
            function cartesianAxisToCanvasCoords(pos) {
                // get canvas coords from the first pair of x/y found in pos
                var res = {},
                    i, axis, key;

                for (i = 0; i < xaxes.length; ++i) {
                    axis = xaxes[i];
                    if (axis && axis.used) {
                        key = "x" + axis.n;
                        if (pos[key] == null && axis.n === 1) {
                            key = "x";
                        }

                        if (pos[key] != null) {
                            res.left = axis.p2c(pos[key]);
                            break;
                        }
                    }
                }

                for (i = 0; i < yaxes.length; ++i) {
                    axis = yaxes[i];
                    if (axis && axis.used) {
                        key = "y" + axis.n;
                        if (pos[key] == null && axis.n === 1) {
                            key = "y";
                        }

                        if (pos[key] != null) {
                            res.top = axis.p2c(pos[key]);
                            break;
                        }
                    }
                }

                return res;
            }

            function getOrCreateAxis(axes, number) {
                if (!axes[number - 1]) {
                    axes[number - 1] = {
                        n: number, // save the number for future reference
                        direction: axes === xaxes ? "x" : "y",
                        options: extend(true, {}, axes === xaxes ? options.xaxis : options.yaxis)
                    };
                }

                return axes[number - 1];
            }

            function fillInSeriesOptions() {
                var neededColors = series.length,
                    maxIndex = -1,
                    i;

                // Subtract the number of series that already have fixed colors or
                // color indexes from the number that we still need to generate.

                for (i = 0; i < series.length; ++i) {
                    var sc = series[i].color;
                    if (sc != null) {
                        neededColors--;
                        if (typeof sc === "number" && sc > maxIndex) {
                            maxIndex = sc;
                        }
                    }
                }

                // If any of the series have fixed color indexes, then we need to
                // generate at least as many colors as the highest index.

                if (neededColors <= maxIndex) {
                    neededColors = maxIndex + 1;
                }

                // Generate all the colors, using first the option colors and then
                // variations on those colors once they're exhausted.

                var c, colors = [],
                    colorPool = options.colors,
                    colorPoolSize = colorPool.length,
                    variation = 0,
                    definedColors = Math.max(0, series.length - neededColors);

                for (i = 0; i < neededColors; i++) {
                    c = color.parse(colorPool[(definedColors + i) % colorPoolSize] || "#666");

                    // Each time we exhaust the colors in the pool we adjust
                    // a scaling factor used to produce more variations on
                    // those colors. The factor alternates negative/positive
                    // to produce lighter/darker colors.

                    // Reset the variation after every few cycles, or else
                    // it will end up producing only white or black colors.

                    if (i % colorPoolSize === 0 && i) {
                        if (variation >= 0) {
                            if (variation < 0.5) {
                                variation = -variation - 0.2;
                            } else variation = 0;
                        } else variation = -variation;
                    }

                    colors[i] = c.scale('rgb', 1 + variation);
                }

                // Finalize the series options, filling in their colors

                var colori = 0,
                    s;
                for (i = 0; i < series.length; ++i) {
                    s = series[i];

                    // assign colors
                    if (s.color == null) {
                        s.color = colors[colori].toString();
                        ++colori;
                    } else if (typeof s.color === "number") {
                        s.color = colors[s.color].toString();
                    }

                    // turn on lines automatically in case nothing is set
                    if (s.lines.show == null) {
                        var v, show = true;
                        for (v in s) {
                            if (s[v] && s[v].show) {
                                show = false;
                                break;
                            }
                        }

                        if (show) {
                            s.lines.show = true;
                        }
                    }

                    // If nothing was provided for lines.zero, default it to match
                    // lines.fill, since areas by default should extend to zero.

                    if (s.lines.zero == null) {
                        s.lines.zero = !!s.lines.fill;
                    }

                    // setup axes
                    s.xaxis = getOrCreateAxis(xaxes, axisNumber(s, "x"));
                    s.yaxis = getOrCreateAxis(yaxes, axisNumber(s, "y"));
                }
            }

            function processData(prevSeries) {
                var topSentry = Number.POSITIVE_INFINITY,
                    bottomSentry = Number.NEGATIVE_INFINITY,
                    i, j, k, m,
                    s, points, ps, val, f, p,
                    data, format;

                function updateAxis(axis, min, max) {
                    if (min < axis.datamin && min !== -Infinity) {
                        axis.datamin = min;
                    }

                    if (max > axis.datamax && max !== Infinity) {
                        axis.datamax = max;
                    }
                }

                function reusePoints(prevSeries, i) {
                    if (prevSeries && prevSeries[i] && prevSeries[i].datapoints && prevSeries[i].datapoints.points) {
                        return prevSeries[i].datapoints.points;
                    }

                    return [];
                }

                allAxes().forEach(function(axis) {
                    // init axis
                    if (axis.options.growOnly !== true) {
                        axis.datamin = topSentry;
                        axis.datamax = bottomSentry;
                    } else {
                        if (axis.datamin === undefined) {
                            axis.datamin = topSentry;
                        }
                        if (axis.datamax === undefined) {
                            axis.datamax = bottomSentry;
                        }
                    }
                    axis.used = false;
                });

                for (i = 0; i < series.length; ++i) {
                    s = series[i];
                    s.datapoints = {
                        points: []
                    };

                    if (s.datapoints.points.length === 0) {
                        s.datapoints.points = reusePoints(prevSeries, i);
                    }

                    executeHooks(hooks.processRawData, [s, s.data, s.datapoints]);
                }

                // first pass: clean and copy data
                for (i = 0; i < series.length; ++i) {
                    s = series[i];

                    data = s.data;
                    format = s.datapoints.format;

                    if (!format) {
                        format = [];
                        // find out how to copy
                        format.push({
                            x: true,
                            y: false,
                            number: true,
                            required: true,
                            computeRange: true, // s.xaxis.options.autoScale !== 'none',
                            defaultValue: null
                        });

                        format.push({
                            x: false,
                            y: true,
                            number: true,
                            required: true,
                            computeRange: true, // s.yaxis.options.autoScale !== 'none',
                            defaultValue: null
                        });

                        if (s.stack || s.bars.show || (s.lines.show && s.lines.fill)) {
                            var expectedPs = s.datapoints.pointsize != null ? s.datapoints.pointsize : (s.data && s.data[0] && s.data[0].length ? s.data[0].length : 3);
                            if (expectedPs > 2) {
                                format.push({
                                    x: s.bars.horizontal,
                                    y: !s.bars.horizontal,
                                    number: true,
                                    required: false,
                                    computeRange: true, // s.yaxis.options.autoScale !== 'none',
                                    defaultValue: 0
                                });
                            }
                        }

                        s.datapoints.format = format;
                    }

                    s.xaxis.used = s.yaxis.used = true;

                    if (s.datapoints.pointsize != null) continue; // already filled in

                    s.datapoints.pointsize = format.length;
                    ps = s.datapoints.pointsize;
                    points = s.datapoints.points;

                    for (j = k = 0; j < data.length; ++j, k += ps) {
                        p = data[j];

                        var nullify = p == null;
                        if (!nullify) {
                            for (m = 0; m < ps; ++m) {
                                val = p[m];
                                f = format[m];

                                if (f) {
                                    if (f.number && val != null) {
                                        val = +val; // convert to number
                                        if (isNaN(val)) {
                                            val = null;
                                        }
                                    }

                                    if (val == null) {
                                        if (f.required) nullify = true;

                                        if (f.defaultValue != null) val = f.defaultValue;
                                    }
                                }

                                points[k + m] = val;
                            }
                        }

                        if (nullify) {
                            for (m = 0; m < ps; ++m) {
                                val = points[k + m];
                                if (val != null) {
                                    f = format[m];
                                    // extract min/max info
                                    if (f.computeRange) {
                                        if (f.x) {
                                            updateAxis(s.xaxis, val, val);
                                        }
                                        if (f.y) {
                                            updateAxis(s.yaxis, val, val);
                                        }
                                    }
                                }
                                points[k + m] = null;
                            }
                        }
                    }

                    points.length = k; //trims the internal buffer to the correct length
                }

                // give the hooks a chance to run
                for (i = 0; i < series.length; ++i) {
                    s = series[i];

                    executeHooks(hooks.processDatapoints, [s, s.datapoints]);
                }

                // second pass: find datamax/datamin for auto-scaling
                for (i = 0; i < series.length; ++i) {
                    s = series[i];
                    format = s.datapoints.format;

                    if (format.every(function (f) { return !f.computeRange; })) {
                        continue;
                    }

                    var range = plot.adjustSeriesDataRange(s,
                        plot.computeRangeForDataSeries(s));

                    executeHooks(hooks.adjustSeriesDataRange, [s, range]);

                    updateAxis(s.xaxis, range.xmin, range.xmax);
                    updateAxis(s.yaxis, range.ymin, range.ymax);
                }

                allAxes().forEach(function(axis) {
                    if (axis.datamin === topSentry) {
                        axis.datamin = null;
                    }

                    if (axis.datamax === bottomSentry) {
                        axis.datamax = null;
                    }
                });
            }

            function setupCanvases() {
                // Make sure the placeholder is clear of everything except canvases
                // from a previous plot in this container that we'll try to re-use.

                css(placeholder, "padding", 0); // padding messes up the positioning
                Array.from(placeholder.children).filter(function(child) {
                    return !child.classList.contains("flot-overlay") && !child.classList.contains('flot-base');
                }).forEach(function(child) { child.remove(); });

                if (css(placeholder, "position") === 'static') {
                    css(placeholder, "position", "relative"); // for positioning labels and overlay
                }

                surface = new Canvas("flot-base", placeholder);
                overlay = new Canvas("flot-overlay", placeholder); // overlay canvas for interactive features

                ctx = surface.context;
                octx = overlay.context;

                // define which element we're listening for events on
                eventHolder = overlay.element;
                unbind(eventHolder);

                // If we're re-using a plot object, shut down the old one

                var existing = data(placeholder, "plot");

                if (existing) {
                    existing.shutdown();
                    overlay.clear();
                }

                // save in case we get replotted
                data(placeholder, "plot", plot);
            }

            function bindEvents() {
                executeHooks(hooks.bindEvents, [eventHolder]);
            }

            function addEventHandler(event, handler, eventHolder, priority) {
                var key = eventHolder + event;
                var eventList = eventManager[key] || [];

                eventList.push({"event": event, "handler": handler, "eventHolder": eventHolder, "priority": priority});
                eventList.sort((a, b) => b.priority - a.priority);
                eventList.forEach(eventData => {
                    unbind(eventData.eventHolder, eventData.event, eventData.handler);
                    bind(eventData.eventHolder, eventData.event, eventData.handler);
                });

                eventManager[key] = eventList;
            }

            function shutdown() {
                if (redrawTimeout) {
                    window.clearTimeout(redrawTimeout);
                }

                executeHooks(hooks.shutdown, [eventHolder]);
            }

            function setTransformationHelpers(axis) {
                // set helper functions on the axis, assumes plot area
                // has been computed already

                function identity(x) {
                    return x;
                }

                var s, m, t = axis.options.transform || identity,
                    it = axis.options.inverseTransform;

                // precompute how much the axis is scaling a point
                // in canvas space
                if (axis.direction === "x") {
                    if (isFinite(t(axis.max) - t(axis.min))) {
                        s = axis.scale = plotWidth / Math.abs(t(axis.max) - t(axis.min));
                    } else {
                        s = axis.scale = 1 / Math.abs(saturated.delta(t(axis.min), t(axis.max), plotWidth));
                    }
                    m = Math.min(t(axis.max), t(axis.min));
                } else {
                    if (isFinite(t(axis.max) - t(axis.min))) {
                        s = axis.scale = plotHeight / Math.abs(t(axis.max) - t(axis.min));
                    } else {
                        s = axis.scale = 1 / Math.abs(saturated.delta(t(axis.min), t(axis.max), plotHeight));
                    }
                    s = -s;
                    m = Math.max(t(axis.max), t(axis.min));
                }

                // data point to canvas coordinate
                if (t === identity) {
                    // slight optimization
                    axis.p2c = function(p) {
                        if (isFinite(p - m)) {
                            return (p - m) * s;
                        } else {
                            return (p / 4 - m / 4) * s * 4;
                        }
                    };
                } else {
                    axis.p2c = function(p) {
                        var tp = t(p);

                        if (isFinite(tp - m)) {
                            return (tp - m) * s;
                        } else {
                            return (tp / 4 - m / 4) * s * 4;
                        }
                    };
                }

                // canvas coordinate to data point
                if (!it) {
                    axis.c2p = function(c) {
                        return m + c / s;
                    };
                } else {
                    axis.c2p = function(c) {
                        return it(m + c / s);
                    };
                }
            }

            function measureTickLabels(axis) {
                var opts = axis.options,
                    ticks = opts.showTickLabels !== 'none' && axis.ticks ? axis.ticks : [],
                    showMajorTickLabels = opts.showTickLabels === 'major' || opts.showTickLabels === 'all',
                    showEndpointsTickLabels = opts.showTickLabels === 'endpoints' || opts.showTickLabels === 'all',
                    labelWidth = opts.labelWidth || 0,
                    labelHeight = opts.labelHeight || 0,
                    legacyStyles = axis.direction + "Axis " + axis.direction + axis.n + "Axis",
                    layer = "flot-" + axis.direction + "-axis flot-" + axis.direction + axis.n + "-axis " + legacyStyles,
                    font = opts.font || "flot-tick-label tickLabel";

                for (var i = 0; i < ticks.length; ++i) {
                    var t = ticks[i];
                    var label = t.label;

                    if (!t.label ||
                        (showMajorTickLabels === false && i > 0 && i < ticks.length - 1) ||
                        (showEndpointsTickLabels === false && (i === 0 || i === ticks.length - 1))) {
                        continue;
                    }

                    if (typeof t.label === 'object') {
                        label = t.label.name;
                    }

                    var info = surface.getTextInfo(layer, label, font);

                    labelWidth = Math.max(labelWidth, info.width);
                    labelHeight = Math.max(labelHeight, info.height);
                }

                axis.labelWidth = opts.labelWidth || labelWidth;
                axis.labelHeight = opts.labelHeight || labelHeight;
            }

            function allocateAxisBoxFirstPhase(axis) {
                // find the bounding box of the axis by looking at label
                // widths/heights and ticks, make room by diminishing the
                // plotOffset; this first phase only looks at one
                // dimension per axis, the other dimension depends on the
                // other axes so will have to wait

                // here reserve additional space
                executeHooks(hooks.axisReserveSpace, [axis]);

                var lw = axis.labelWidth,
                    lh = axis.labelHeight,
                    pos = axis.options.position,
                    isXAxis = axis.direction === "x",
                    tickLength = axis.options.tickLength,
                    showTicks = axis.options.showTicks,
                    showMinorTicks = axis.options.showMinorTicks,
                    gridLines = axis.options.gridLines,
                    axisMargin = options.grid.axisMargin,
                    padding = options.grid.labelMargin,
                    innermost = true,
                    outermost = true,
                    found = false;

                // Determine the axis's position in its direction and on its side

                (isXAxis ? xaxes : yaxes).forEach(function(a) {
                    if (a && (a.show || a.reserveSpace)) {
                        if (a === axis) {
                            found = true;
                        } else if (a.options.position === pos) {
                            if (found) {
                                outermost = false;
                            } else {
                                innermost = false;
                            }
                        }
                    }
                });

                // The outermost axis on each side has no margin
                if (outermost) {
                    axisMargin = 0;
                }

                // Set the default tickLength if necessary
                if (tickLength == null) {
                    tickLength = TICK_LENGTH_CONSTANT;
                }

                // By default, major tick marks are visible
                if (showTicks == null) {
                    showTicks = true;
                }

                // By default, minor tick marks are visible
                if (showMinorTicks == null) {
                    showMinorTicks = true;
                }

                // By default, grid lines are visible
                if (gridLines == null) {
                    if (innermost) {
                        gridLines = true;
                    } else {
                        gridLines = false;
                    }
                }

                if (!isNaN(+tickLength)) {
                    padding += showTicks ? +tickLength : 0;
                }

                if (isXAxis) {
                    lh += padding;

                    if (pos === "bottom") {
                        plotOffset.bottom += lh + axisMargin;
                        axis.box = {
                            top: surface.height - plotOffset.bottom,
                            height: lh
                        };
                    } else {
                        axis.box = {
                            top: plotOffset.top + axisMargin,
                            height: lh
                        };
                        plotOffset.top += lh + axisMargin;
                    }
                } else {
                    lw += padding;

                    if (pos === "left") {
                        axis.box = {
                            left: plotOffset.left + axisMargin,
                            width: lw
                        };
                        plotOffset.left += lw + axisMargin;
                    } else {
                        plotOffset.right += lw + axisMargin;
                        axis.box = {
                            left: surface.width - plotOffset.right,
                            width: lw
                        };
                    }
                }

                // save for future reference
                axis.position = pos;
                axis.tickLength = tickLength;
                axis.showMinorTicks = showMinorTicks;
                axis.showTicks = showTicks;
                axis.gridLines = gridLines;
                axis.box.padding = padding;
                axis.innermost = innermost;
            }

            function allocateAxisBoxSecondPhase(axis) {
                // now that all axis boxes have been placed in one
                // dimension, we can set the remaining dimension coordinates
                if (axis.direction === "x") {
                    axis.box.left = plotOffset.left - axis.labelWidth / 2;
                    axis.box.width = surface.width - plotOffset.left - plotOffset.right + axis.labelWidth;
                } else {
                    axis.box.top = plotOffset.top - axis.labelHeight / 2;
                    axis.box.height = surface.height - plotOffset.bottom - plotOffset.top + axis.labelHeight;
                }
            }

            function adjustLayoutForThingsStickingOut() {
                // possibly adjust plot offset to ensure everything stays
                // inside the canvas and isn't clipped off

                var minMargin = options.grid.minBorderMargin,
                    i;

                // check stuff from the plot (FIXME: this should just read
                // a value from the series, otherwise it's impossible to
                // customize)
                if (minMargin == null) {
                    minMargin = 0;
                    for (i = 0; i < series.length; ++i) {
                        minMargin = Math.max(minMargin, 2 * (series[i].points.radius + series[i].points.lineWidth / 2));
                    }
                }

                var a, offset = {},
                    margins = {
                        left: minMargin,
                        right: minMargin,
                        top: minMargin,
                        bottom: minMargin
                    };

                // check axis labels, note we don't check the actual
                // labels but instead use the overall width/height to not
                // jump as much around with replots
                allAxes().forEach(function(axis) {
                    if (axis.reserveSpace && axis.ticks && axis.ticks.length) {
                        if (axis.direction === "x") {
                            margins.left = Math.max(margins.left, axis.labelWidth / 2);
                            margins.right = Math.max(margins.right, axis.labelWidth / 2);
                        } else {
                            margins.bottom = Math.max(margins.bottom, axis.labelHeight / 2);
                            margins.top = Math.max(margins.top, axis.labelHeight / 2);
                        }
                    }
                });

                for (a in margins) {
                    offset[a] = margins[a] - plotOffset[a];
                }
                xaxes.concat(yaxes).forEach(function(axis) {
                    alignAxisWithGrid(axis, offset, function (offset) {
                        return offset > 0;
                    });
                });

                plotOffset.left = Math.ceil(Math.max(margins.left, plotOffset.left));
                plotOffset.right = Math.ceil(Math.max(margins.right, plotOffset.right));
                plotOffset.top = Math.ceil(Math.max(margins.top, plotOffset.top));
                plotOffset.bottom = Math.ceil(Math.max(margins.bottom, plotOffset.bottom));
            }

            function alignAxisWithGrid(axis, offset, isValid) {
                if (axis.direction === "x") {
                    if (axis.position === "bottom" && isValid(offset.bottom)) {
                        axis.box.top -= Math.ceil(offset.bottom);
                    }
                    if (axis.position === "top" && isValid(offset.top)) {
                        axis.box.top += Math.ceil(offset.top);
                    }
                } else {
                    if (axis.position === "left" && isValid(offset.left)) {
                        axis.box.left += Math.ceil(offset.left);
                    }
                    if (axis.position === "right" && isValid(offset.right)) {
                        axis.box.left -= Math.ceil(offset.right);
                    }
                }
            }

            function setupGrid(autoScale) {
                var i, a, axes = allAxes(),
                    showGrid = options.grid.show;

                // Initialize the plot's offset from the edge of the canvas

                for (a in plotOffset) {
                    plotOffset[a] = 0;
                }

                executeHooks(hooks.processOffset, [plotOffset]);

                // If the grid is visible, add its border width to the offset
                for (a in plotOffset) {
                    if (typeof (options.grid.borderWidth) === "object") {
                        plotOffset[a] += showGrid ? options.grid.borderWidth[a] : 0;
                    } else {
                        plotOffset[a] += showGrid ? options.grid.borderWidth : 0;
                    }
                }

                axes.forEach(function(axis) {
                    var axisOpts = axis.options;
                    axis.show = axisOpts.show == null ? axis.used : axisOpts.show;
                    axis.reserveSpace = axisOpts.reserveSpace == null ? axis.show : axisOpts.reserveSpace;
                    setupTickFormatter(axis);
                    executeHooks(hooks.setRange, [axis, autoScale]);
                    setRange(axis, autoScale);
                });

                if (showGrid) {
                    plotWidth = surface.width - plotOffset.left - plotOffset.right;
                    plotHeight = surface.height - plotOffset.bottom - plotOffset.top;

                    var allocatedAxes = axes.filter(function(axis) {
                        return axis.show || axis.reserveSpace;
                    });

                    allocatedAxes.forEach(function(axis) {
                        // make the ticks
                        setupTickGeneration(axis);
                        setMajorTicks(axis);
                        snapRangeToTicks(axis, axis.ticks, series);

                        //for computing the endpoints precision, transformationHelpers are needed
                        setTransformationHelpers(axis);
                        setEndpointTicks(axis, series);

                        // find labelWidth/Height for axis
                        measureTickLabels(axis);
                    });

                    // with all dimensions calculated, we can compute the
                    // axis bounding boxes, start from the outside
                    // (reverse order)
                    for (i = allocatedAxes.length - 1; i >= 0; --i) {
                        allocateAxisBoxFirstPhase(allocatedAxes[i]);
                    }

                    // make sure we've got enough space for things that
                    // might stick out
                    adjustLayoutForThingsStickingOut();

                    allocatedAxes.forEach(function(axis) {
                        allocateAxisBoxSecondPhase(axis);
                    });
                }

                //adjust axis and plotOffset according to grid.margins
                if (options.grid.margin) {
                    for (a in plotOffset) {
                        var margin = options.grid.margin || 0;
                        plotOffset[a] += typeof margin === "number" ? margin : (margin[a] || 0);
                    }
                    xaxes.concat(yaxes).forEach(function(axis) {
                        alignAxisWithGrid(axis, options.grid.margin, function(offset) {
                            return offset !== undefined && offset !== null;
                        });
                    });
                }

                //after adjusting the axis, plot width and height will be modified
                plotWidth = surface.width - plotOffset.left - plotOffset.right;
                plotHeight = surface.height - plotOffset.bottom - plotOffset.top;

                // now we got the proper plot dimensions, we can compute the scaling
                axes.forEach(function(axis) {
                    setTransformationHelpers(axis);
                });

                if (showGrid) {
                    drawAxisLabels();
                }

                executeHooks(hooks.setupGrid, []);
            }

            function widenMinMax(minimum, maximum) {
                var min = (minimum === undefined ? null : minimum);
                var max = (maximum === undefined ? null : maximum);
                var delta = max - min;
                if (delta === 0.0) {
                    // degenerate case
                    var widen = max === 0 ? 1 : 0.01;
                    var wmin = null;
                    if (min == null) {
                        wmin -= widen;
                    }

                    // always widen max if we couldn't widen min to ensure we
                    // don't fall into min == max which doesn't work
                    if (max == null || min != null) {
                        max += widen;
                    }

                    if (wmin != null) {
                        min = wmin;
                    }
                }

                return {
                    min: min,
                    max: max
                };
            }

            function autoScaleAxis(axis) {
                var opts = axis.options,
                    min = opts.min,
                    max = opts.max,
                    datamin = axis.datamin,
                    datamax = axis.datamax,
                    delta;

                switch (opts.autoScale) {
                    case "none":
                        min = +(opts.min != null ? opts.min : datamin);
                        max = +(opts.max != null ? opts.max : datamax);
                        break;
                    case "loose":
                        if (datamin != null && datamax != null) {
                            min = datamin;
                            max = datamax;
                            delta = saturated.saturate(max - min);
                            var margin = ((typeof opts.autoScaleMargin === 'number') ? opts.autoScaleMargin : 0.02);
                            min = saturated.saturate(min - delta * margin);
                            max = saturated.saturate(max + delta * margin);

                            // make sure we don't go below zero if all values are positive
                            if (min < 0 && datamin >= 0) {
                                min = 0;
                            }
                        } else {
                            min = opts.min;
                            max = opts.max;
                        }
                        break;
                    case "exact":
                        min = (datamin != null ? datamin : opts.min);
                        max = (datamax != null ? datamax : opts.max);
                        break;
                    case "sliding-window":
                        if (datamax > max) {
                            // move the window to fit the new data,
                            // keeping the axis range constant
                            max = datamax;
                            min = Math.max(datamax - (opts.windowSize || 100), min);
                        }
                        break;
                }

                var widenedMinMax = widenMinMax(min, max);
                min = widenedMinMax.min;
                max = widenedMinMax.max;

                // grow loose or grow exact supported
                if (opts.growOnly === true && opts.autoScale !== "none" && opts.autoScale !== "sliding-window") {
                    min = (min < datamin) ? min : (datamin !== null ? datamin : min);
                    max = (max > datamax) ? max : (datamax !== null ? datamax : max);
                }

                axis.autoScaledMin = min;
                axis.autoScaledMax = max;
            }

            function setRange(axis, autoScale) {
                var min = typeof axis.options.min === 'number' ? axis.options.min : axis.min,
                    max = typeof axis.options.max === 'number' ? axis.options.max : axis.max,
                    plotOffset = axis.options.offset;

                if (autoScale) {
                    autoScaleAxis(axis);
                    min = axis.autoScaledMin;
                    max = axis.autoScaledMax;
                }

                min = (min != null ? min : -1) + (plotOffset.below || 0);
                max = (max != null ? max : 1) + (plotOffset.above || 0);

                if (min > max) {
                    var tmp = min;
                    min = max;
                    max = tmp;
                    axis.options.offset = { above: 0, below: 0 };
                }

                axis.min = saturated.saturate(min);
                axis.max = saturated.saturate(max);
            }

            function computeValuePrecision (min, max, direction, ticks, tickDecimals) {
                var noTicks = fixupNumberOfTicks(direction, surface, ticks);

                var delta = saturated.delta(min, max, noTicks),
                    dec = -Math.floor(Math.log(delta) / Math.LN10);

                //if it is called with tickDecimals, then the precision should not be greather then that
                if (tickDecimals && dec > tickDecimals) {
                    dec = tickDecimals;
                }

                var magn = parseFloat('1e' + (-dec)),
                    norm = delta / magn;

                if (norm > 2.25 && norm < 3 && (tickDecimals == null || (dec + 1) <= tickDecimals)) {
                    //we need an extra decimals when tickSize is 2.5
                    ++dec;
                }

                return isFinite(dec) ? dec : 0;
            }
            function computeTickSize (min, max, noTicks, tickDecimals) {
                var delta = saturated.delta(min, max, noTicks),
                    dec = -Math.floor(Math.log(delta) / Math.LN10);

                //if it is called with tickDecimals, then the precision should not be greather then that
                if (tickDecimals && dec > tickDecimals) {
                    dec = tickDecimals;
                }

                var magn = parseFloat('1e' + (-dec)),
                    norm = delta / magn, // norm is between 1.0 and 10.0
                    size;

                if (norm < 1.5) {
                    size = 1;
                } else if (norm < 3) {
                    size = 2;
                    if (norm > 2.25 && (tickDecimals == null || (dec + 1) <= tickDecimals)) {
                        size = 2.5;
                    }
                } else if (norm < 7.5) {
                    size = 5;
                } else {
                    size = 10;
                }

                size *= magn;
                return size;
            }

            function getAxisTickSize(min, max, direction, options, tickDecimals) {
                var noTicks;

                if (typeof options.ticks === "number" && options.ticks > 0) {
                    noTicks = options.ticks;
                } else {
                // heuristic based on the model a*sqrt(x) fitted to
                // some data points that seemed reasonable
                    noTicks = 0.3 * Math.sqrt(direction === "x" ? surface.width : surface.height);
                }

                var size = computeTickSize(min, max, noTicks, tickDecimals);

                if (options.minTickSize != null && size < options.minTickSize) {
                    size = options.minTickSize;
                }

                return options.tickSize || size;
            }
            function fixupNumberOfTicks(direction, surface, ticksOption) {
                var noTicks;

                if (typeof ticksOption === "number" && ticksOption > 0) {
                    noTicks = ticksOption;
                } else {
                    noTicks = 0.3 * Math.sqrt(direction === "x" ? surface.width : surface.height);
                }

                return noTicks;
            }

            function setupTickFormatter(axis) {
                var opts = axis.options;
                if (!axis.tickFormatter) {
                    if (typeof opts.tickFormatter === 'function') {
                        axis.tickFormatter = function() {
                            var args = Array.prototype.slice.call(arguments);
                            return "" + opts.tickFormatter.apply(null, args);
                        };
                    } else {
                        axis.tickFormatter = defaultTickFormatter;
                    }
                }
            }

            function setupTickGeneration(axis) {
                var opts = axis.options;
                var noTicks;

                noTicks = fixupNumberOfTicks(axis.direction, surface, opts.ticks);

                axis.delta = saturated.delta(axis.min, axis.max, noTicks);
                var precision = plot.computeValuePrecision(axis.min, axis.max, axis.direction, noTicks, opts.tickDecimals);

                axis.tickDecimals = Math.max(0, opts.tickDecimals != null ? opts.tickDecimals : precision);
                axis.tickSize = getAxisTickSize(axis.min, axis.max, axis.direction, opts, opts.tickDecimals);

                // Flot supports base-10 axes; any other mode else is handled by a plug-in,
                // like flot.time.js.

                if (!axis.tickGenerator) {
                    if (typeof opts.tickGenerator === 'function') {
                        axis.tickGenerator = opts.tickGenerator;
                    } else {
                        axis.tickGenerator = defaultTickGenerator;
                    }
                }

                if (opts.alignTicksWithAxis != null) {
                    var otherAxis = (axis.direction === "x" ? xaxes : yaxes)[opts.alignTicksWithAxis - 1];
                    if (otherAxis && otherAxis.used && otherAxis !== axis) {
                        // consider snapping min/max to outermost nice ticks
                        var niceTicks = axis.tickGenerator(axis, plot);
                        if (niceTicks.length > 0) {
                            if (opts.min == null) {
                                axis.min = Math.min(axis.min, niceTicks[0]);
                            }

                            if (opts.max == null && niceTicks.length > 1) {
                                axis.max = Math.max(axis.max, niceTicks[niceTicks.length - 1]);
                            }
                        }

                        axis.tickGenerator = function(axis) {
                            // copy ticks, scaled to this axis
                            var ticks = [],
                                v, i;
                            for (i = 0; i < otherAxis.ticks.length; ++i) {
                                v = (otherAxis.ticks[i].v - otherAxis.min) / (otherAxis.max - otherAxis.min);
                                v = axis.min + v * (axis.max - axis.min);
                                ticks.push(v);
                            }
                            return ticks;
                        };

                        // we might need an extra decimal since forced
                        // ticks don't necessarily fit naturally
                        if (!axis.mode && opts.tickDecimals == null) {
                            var extraDec = Math.max(0, -Math.floor(Math.log(axis.delta) / Math.LN10) + 1),
                                ts = axis.tickGenerator(axis, plot);

                            // only proceed if the tick interval rounded
                            // with an extra decimal doesn't give us a
                            // zero at end
                            if (!(ts.length > 1 && /\..*0$/.test((ts[1] - ts[0]).toFixed(extraDec)))) {
                                axis.tickDecimals = extraDec;
                            }
                        }
                    }
                }
            }

            function setMajorTicks(axis) {
                var oticks = axis.options.ticks,
                    ticks = [];
                if (oticks == null || (typeof oticks === "number" && oticks > 0)) {
                    ticks = axis.tickGenerator(axis, plot);
                } else if (oticks) {
                    if (typeof oticks === 'function') {
                    // generate the ticks
                        ticks = oticks(axis);
                    } else {
                        ticks = oticks;
                    }
                }

                // clean up/labelify the supplied ticks, copy them over
                var i, v;
                axis.ticks = [];
                for (i = 0; i < ticks.length; ++i) {
                    var label = null;
                    var t = ticks[i];
                    if (typeof t === "object") {
                        v = +t[0];
                        if (t.length > 1) {
                            label = t[1];
                        }
                    } else {
                        v = +t;
                    }

                    if (!isNaN(v)) {
                        axis.ticks.push(
                            newTick(v, label, axis, 'major'));
                    }
                }
            }

            function newTick(v, label, axis, type) {
                if (label === null) {
                    switch (type) {
                        case 'min':
                        case 'max':
                            //improving the precision of endpoints
                            var precision = getEndpointPrecision(v, axis);
                            label = isFinite(precision) ? axis.tickFormatter(v, axis, precision, plot) : axis.tickFormatter(v, axis, precision, plot);
                            break;
                        case 'major':
                            label = axis.tickFormatter(v, axis, undefined, plot);
                    }
                }
                return {
                    v: v,
                    label: label
                };
            }

            function snapRangeToTicks(axis, ticks, series) {
                var anyDataInSeries = function(series) {
                    return series.some(e => e.datapoints.points.length > 0);
                };

                if (axis.options.autoScale === "loose" && ticks.length > 0 && anyDataInSeries(series)) {
                    // snap to ticks
                    axis.min = Math.min(axis.min, ticks[0].v);
                    axis.max = Math.max(axis.max, ticks[ticks.length - 1].v);
                }
            }

            function getEndpointPrecision(value, axis) {
                var canvas1 = Math.floor(axis.p2c(value)),
                    canvas2 = axis.direction === "x" ? canvas1 + 1 : canvas1 - 1,
                    point1 = axis.c2p(canvas1),
                    point2 = axis.c2p(canvas2),
                    precision = computeValuePrecision(point1, point2, axis.direction, 1);

                return precision;
            }

            function setEndpointTicks(axis, series) {
                if (isValidEndpointTick(axis, series)) {
                    axis.ticks.unshift(newTick(axis.min, null, axis, 'min'));
                    axis.ticks.push(newTick(axis.max, null, axis, 'max'));
                }
            }

            function isValidEndpointTick(axis, series) {
                if (axis.options.showTickLabels === 'endpoints') {
                    return true;
                }
                if (axis.options.showTickLabels === 'all') {
                    var associatedSeries = series.filter(function(s) {
                            return s.bars.horizontal ? s.yaxis === axis : s.xaxis === axis;
                        }),
                        notAllBarSeries = associatedSeries.some(function(s) {
                            return !s.bars.show;
                        });
                    return associatedSeries.length === 0 || notAllBarSeries;
                }
                if (axis.options.showTickLabels === 'major' || axis.options.showTickLabels === 'none') {
                    return false;
                }
            }

            function draw() {
                surface.clear();
                executeHooks(hooks.drawBackground, [ctx]);

                var grid = options.grid;

                // draw background, if any
                if (grid.show && grid.backgroundColor) {
                    drawBackground();
                }

                if (grid.show && !grid.aboveData) {
                    drawGrid();
                }

                for (var i = 0; i < series.length; ++i) {
                    executeHooks(hooks.drawSeries, [ctx, series[i], i, getColorOrGradient]);
                    drawSeries$1(series[i]);
                }

                executeHooks(hooks.draw, [ctx]);

                if (grid.show && grid.aboveData) {
                    drawGrid();
                }

                surface.render();

                // A draw implies that either the axes or data have changed, so we
                // should probably update the overlay highlights as well.
                triggerRedrawOverlay();
            }

            function extractRange(ranges, coord) {
                var axis, from, to, key, axes = allAxes();

                for (var i = 0; i < axes.length; ++i) {
                    axis = axes[i];
                    if (axis.direction === coord) {
                        key = coord + axis.n + "axis";
                        if (!ranges[key] && axis.n === 1) {
                            // support x1axis as xaxis
                            key = coord + "axis";
                        }

                        if (ranges[key]) {
                            from = ranges[key].from;
                            to = ranges[key].to;
                            break;
                        }
                    }
                }

                // backwards-compat stuff - to be removed in future
                if (!ranges[key]) {
                    axis = coord === "x" ? xaxes[0] : yaxes[0];
                    from = ranges[coord + "1"];
                    to = ranges[coord + "2"];
                }

                // auto-reverse as an added bonus
                if (from != null && to != null && from > to) {
                    var tmp = from;
                    from = to;
                    to = tmp;
                }

                return {
                    from: from,
                    to: to,
                    axis: axis
                };
            }

            function drawBackground() {
                ctx.save();
                ctx.translate(plotOffset.left, plotOffset.top);

                ctx.fillStyle = getColorOrGradient(options.grid.backgroundColor, plotHeight, 0, "rgba(255, 255, 255, 0)");
                ctx.fillRect(0, 0, plotWidth, plotHeight);
                ctx.restore();
            }

            function drawMarkings() {
                // draw markings
                var markings = options.grid.markings,
                    axes;

                if (markings) {
                    if (typeof markings === 'function') {
                        axes = plot.getAxes();
                        // xmin etc. is backwards compatibility, to be
                        // removed in the future
                        axes.xmin = axes.xaxis.min;
                        axes.xmax = axes.xaxis.max;
                        axes.ymin = axes.yaxis.min;
                        axes.ymax = axes.yaxis.max;

                        markings = markings(axes);
                    }

                    if (!markings) return;

                    var i;
                    for (i = 0; i < markings.length; ++i) {
                        var m = markings[i],
                            xrange = extractRange(m, "x"),
                            yrange = extractRange(m, "y");

                        // fill in missing
                        if (xrange.from == null) {
                            xrange.from = xrange.axis.min;
                        }

                        if (xrange.to == null) {
                            xrange.to = xrange.axis.max;
                        }

                        if (yrange.from == null) {
                            yrange.from = yrange.axis.min;
                        }

                        if (yrange.to == null) {
                            yrange.to = yrange.axis.max;
                        }

                        // clip
                        if (xrange.to < xrange.axis.min || xrange.from > xrange.axis.max ||
                            yrange.to < yrange.axis.min || yrange.from > yrange.axis.max) {
                            continue;
                        }

                        xrange.from = Math.max(xrange.from, xrange.axis.min);
                        xrange.to = Math.min(xrange.to, xrange.axis.max);
                        yrange.from = Math.max(yrange.from, yrange.axis.min);
                        yrange.to = Math.min(yrange.to, yrange.axis.max);

                        var xequal = xrange.from === xrange.to,
                            yequal = yrange.from === yrange.to;

                        if (xequal && yequal) {
                            continue;
                        }

                        // then draw
                        xrange.from = Math.floor(xrange.axis.p2c(xrange.from));
                        xrange.to = Math.floor(xrange.axis.p2c(xrange.to));
                        yrange.from = Math.floor(yrange.axis.p2c(yrange.from));
                        yrange.to = Math.floor(yrange.axis.p2c(yrange.to));

                        if (xequal || yequal) {
                            var lineWidth = m.lineWidth || options.grid.markingsLineWidth,
                                subPixel = lineWidth % 2 ? 0.5 : 0;
                            ctx.beginPath();
                            ctx.strokeStyle = m.color || options.grid.markingsColor;
                            ctx.lineWidth = lineWidth;
                            if (xequal) {
                                ctx.moveTo(xrange.to + subPixel, yrange.from);
                                ctx.lineTo(xrange.to + subPixel, yrange.to);
                            } else {
                                ctx.moveTo(xrange.from, yrange.to + subPixel);
                                ctx.lineTo(xrange.to, yrange.to + subPixel);
                            }
                            ctx.stroke();
                        } else {
                            ctx.fillStyle = m.color || options.grid.markingsColor;
                            ctx.fillRect(xrange.from, yrange.to,
                                xrange.to - xrange.from,
                                yrange.from - yrange.to);
                        }
                    }
                }
            }

            function findEdges(axis) {
                var box = axis.box,
                    x = 0,
                    y = 0;

                // find the edges
                if (axis.direction === "x") {
                    x = 0;
                    y = box.top - plotOffset.top + (axis.position === "top" ? box.height : 0);
                } else {
                    y = 0;
                    x = box.left - plotOffset.left + (axis.position === "left" ? box.width : 0) + axis.boxPosition.centerX;
                }

                return {
                    x: x,
                    y: y
                };
            }
            function alignPosition(lineWidth, pos) {
                return ((lineWidth % 2) !== 0) ? Math.floor(pos) + 0.5 : pos;
            }
            function drawTickBar(axis) {
                ctx.lineWidth = 1;
                var edges = findEdges(axis),
                    x = edges.x,
                    y = edges.y;

                // draw tick bar
                if (axis.show) {
                    var xoff = 0,
                        yoff = 0;

                    ctx.strokeStyle = axis.options.color;
                    ctx.beginPath();
                    if (axis.direction === "x") {
                        xoff = plotWidth + 1;
                    } else {
                        yoff = plotHeight + 1;
                    }

                    if (axis.direction === "x") {
                        y = alignPosition(ctx.lineWidth, y);
                    } else {
                        x = alignPosition(ctx.lineWidth, x);
                    }

                    ctx.moveTo(x, y);
                    ctx.lineTo(x + xoff, y + yoff);
                    ctx.stroke();
                }
            }
            function drawTickMarks(axis) {
                var t = axis.tickLength,
                    minorTicks = axis.showMinorTicks,
                    minorTicksNr = MINOR_TICKS_COUNT_CONSTANT,
                    edges = findEdges(axis),
                    x = edges.x,
                    y = edges.y,
                    i = 0;

                // draw major tick marks
                ctx.strokeStyle = axis.options.color;
                ctx.beginPath();

                for (i = 0; i < axis.ticks.length; ++i) {
                    var v = axis.ticks[i].v,
                        xoff = 0,
                        yoff = 0,
                        xminor = 0,
                        yminor = 0,
                        j;

                    if (!isNaN(v) && v >= axis.min && v <= axis.max) {
                        if (axis.direction === "x") {
                            x = axis.p2c(v);
                            yoff = t;

                            if (axis.position === "top") {
                                yoff = -yoff;
                            }
                        } else {
                            y = axis.p2c(v);
                            xoff = t;

                            if (axis.position === "left") {
                                xoff = -xoff;
                            }
                        }

                        if (axis.direction === "x") {
                            x = alignPosition(ctx.lineWidth, x);
                        } else {
                            y = alignPosition(ctx.lineWidth, y);
                        }

                        ctx.moveTo(x, y);
                        ctx.lineTo(x + xoff, y + yoff);
                    }

                    //draw minor tick marks
                    if (minorTicks === true && i < axis.ticks.length - 1) {
                        var v1 = axis.ticks[i].v,
                            v2 = axis.ticks[i + 1].v,
                            step = (v2 - v1) / (minorTicksNr + 1);

                        for (j = 1; j <= minorTicksNr; j++) {
                            // compute minor tick position
                            if (axis.direction === "x") {
                                yminor = t / 2; // minor ticks are half length
                                x = alignPosition(ctx.lineWidth, axis.p2c(v1 + j * step));

                                if (axis.position === "top") {
                                    yminor = -yminor;
                                }

                                // don't go over the plot borders
                                if ((x < 0) || (x > plotWidth)) {
                                    continue;
                                }
                            } else {
                                xminor = t / 2; // minor ticks are half length
                                y = alignPosition(ctx.lineWidth, axis.p2c(v1 + j * step));

                                if (axis.position === "left") {
                                    xminor = -xminor;
                                }

                                // don't go over the plot borders
                                if ((y < 0) || (y > plotHeight)) {
                                    continue;
                                }
                            }

                            ctx.moveTo(x, y);
                            ctx.lineTo(x + xminor, y + yminor);
                        }
                    }
                }

                ctx.stroke();
            }
            function drawGridLines(axis) {
                // check if the line will be overlapped with a border
                var overlappedWithBorder = function (value) {
                    var bw = options.grid.borderWidth;
                    return (((typeof bw === "object" && bw[axis.position] > 0) || bw > 0) && (value === axis.min || value === axis.max));
                };

                ctx.strokeStyle = options.grid.tickColor;
                ctx.beginPath();
                var i;
                for (i = 0; i < axis.ticks.length; ++i) {
                    var v = axis.ticks[i].v,
                        xoff = 0,
                        yoff = 0,
                        x = 0,
                        y = 0;

                    if (isNaN(v) || v < axis.min || v > axis.max) continue;

                    // skip those lying on the axes if we got a border
                    if (overlappedWithBorder(v)) continue;

                    if (axis.direction === "x") {
                        x = axis.p2c(v);
                        y = plotHeight;
                        yoff = -plotHeight;
                    } else {
                        x = 0;
                        y = axis.p2c(v);
                        xoff = plotWidth;
                    }

                    if (axis.direction === "x") {
                        x = alignPosition(ctx.lineWidth, x);
                    } else {
                        y = alignPosition(ctx.lineWidth, y);
                    }

                    ctx.moveTo(x, y);
                    ctx.lineTo(x + xoff, y + yoff);
                }

                ctx.stroke();
            }
            function drawBorder() {
                // If either borderWidth or borderColor is an object, then draw the border
                // line by line instead of as one rectangle
                var bw = options.grid.borderWidth,
                    bc = options.grid.borderColor;

                if (typeof bw === "object" || typeof bc === "object") {
                    if (typeof bw !== "object") {
                        bw = {
                            top: bw,
                            right: bw,
                            bottom: bw,
                            left: bw
                        };
                    }
                    if (typeof bc !== "object") {
                        bc = {
                            top: bc,
                            right: bc,
                            bottom: bc,
                            left: bc
                        };
                    }

                    if (bw.top > 0) {
                        ctx.strokeStyle = bc.top;
                        ctx.lineWidth = bw.top;
                        ctx.beginPath();
                        ctx.moveTo(0 - bw.left, 0 - bw.top / 2);
                        ctx.lineTo(plotWidth, 0 - bw.top / 2);
                        ctx.stroke();
                    }

                    if (bw.right > 0) {
                        ctx.strokeStyle = bc.right;
                        ctx.lineWidth = bw.right;
                        ctx.beginPath();
                        ctx.moveTo(plotWidth + bw.right / 2, 0 - bw.top);
                        ctx.lineTo(plotWidth + bw.right / 2, plotHeight);
                        ctx.stroke();
                    }

                    if (bw.bottom > 0) {
                        ctx.strokeStyle = bc.bottom;
                        ctx.lineWidth = bw.bottom;
                        ctx.beginPath();
                        ctx.moveTo(plotWidth + bw.right, plotHeight + bw.bottom / 2);
                        ctx.lineTo(0, plotHeight + bw.bottom / 2);
                        ctx.stroke();
                    }

                    if (bw.left > 0) {
                        ctx.strokeStyle = bc.left;
                        ctx.lineWidth = bw.left;
                        ctx.beginPath();
                        ctx.moveTo(0 - bw.left / 2, plotHeight + bw.bottom);
                        ctx.lineTo(0 - bw.left / 2, 0);
                        ctx.stroke();
                    }
                } else {
                    ctx.lineWidth = bw;
                    ctx.strokeStyle = options.grid.borderColor;
                    ctx.strokeRect(-bw / 2, -bw / 2, plotWidth + bw, plotHeight + bw);
                }
            }
            function drawGrid() {
                var axes, bw;

                ctx.save();
                ctx.translate(plotOffset.left, plotOffset.top);

                drawMarkings();

                axes = allAxes();
                bw = options.grid.borderWidth;

                for (var j = 0; j < axes.length; ++j) {
                    var axis = axes[j];

                    if (!axis.show) {
                        continue;
                    }

                    drawTickBar(axis);
                    if (axis.showTicks === true) {
                        drawTickMarks(axis);
                    }

                    if (axis.gridLines === true) {
                        drawGridLines(axis);
                    }
                }

                // draw border
                if (bw) {
                    drawBorder();
                }

                ctx.restore();
            }

            function drawAxisLabels() {
                allAxes().forEach(function(axis) {
                    var box = axis.box,
                        legacyStyles = axis.direction + "Axis " + axis.direction + axis.n + "Axis",
                        layer = "flot-" + axis.direction + "-axis flot-" + axis.direction + axis.n + "-axis " + legacyStyles,
                        font = axis.options.font || "flot-tick-label tickLabel",
                        i, x, y, halign, valign, info,
                        margin = 3,
                        nullBox = {x: NaN, y: NaN, width: NaN, height: NaN}, newLabelBox, labelBoxes = [],
                        overlapping = function(x11, y11, x12, y12, x21, y21, x22, y22) {
                            return ((x11 <= x21 && x21 <= x12) || (x21 <= x11 && x11 <= x22)) &&
                                   ((y11 <= y21 && y21 <= y12) || (y21 <= y11 && y11 <= y22));
                        },
                        overlapsOtherLabels = function(newLabelBox, previousLabelBoxes) {
                            return previousLabelBoxes.some(function(labelBox) {
                                return overlapping(
                                    newLabelBox.x, newLabelBox.y, newLabelBox.x + newLabelBox.width, newLabelBox.y + newLabelBox.height,
                                    labelBox.x, labelBox.y, labelBox.x + labelBox.width, labelBox.y + labelBox.height);
                            });
                        },
                        drawAxisLabel = function (tick, labelBoxes) {
                            if (!tick || !tick.label || tick.v < axis.min || tick.v > axis.max) {
                                return nullBox;
                            }

                            info = surface.getTextInfo(layer, tick.label, font);

                            if (axis.direction === "x") {
                                halign = "center";
                                x = plotOffset.left + axis.p2c(tick.v);
                                if (axis.position === "bottom") {
                                    y = box.top + box.padding - axis.boxPosition.centerY;
                                } else {
                                    y = box.top + box.height - box.padding + axis.boxPosition.centerY;
                                    valign = "bottom";
                                }
                                newLabelBox = {x: x - info.width / 2 - margin, y: y - margin, width: info.width + 2 * margin, height: info.height + 2 * margin};
                            } else {
                                valign = "middle";
                                y = plotOffset.top + axis.p2c(tick.v);
                                if (axis.position === "left") {
                                    x = box.left + box.width - box.padding - axis.boxPosition.centerX;
                                    halign = "right";
                                } else {
                                    x = box.left + box.padding + axis.boxPosition.centerX;
                                }
                                newLabelBox = {x: x - info.width / 2 - margin, y: y - margin, width: info.width + 2 * margin, height: info.height + 2 * margin};
                            }

                            if (overlapsOtherLabels(newLabelBox, labelBoxes)) {
                                return nullBox;
                            }

                            surface.addText(layer, x, y, tick.label, font, null, null, halign, valign);

                            return newLabelBox;
                        };

                    // Remove text before checking for axis.show and ticks.length;
                    // otherwise plugins, like flot-tickrotor, that draw their own
                    // tick labels will end up with both theirs and the defaults.

                    surface.removeText(layer);

                    executeHooks(hooks.drawAxis, [axis, surface]);

                    if (!axis.show) {
                        return;
                    }

                    switch (axis.options.showTickLabels) {
                        case 'none':
                            break;
                        case 'endpoints':
                            labelBoxes.push(drawAxisLabel(axis.ticks[0], labelBoxes));
                            labelBoxes.push(drawAxisLabel(axis.ticks[axis.ticks.length - 1], labelBoxes));
                            break;
                        case 'major':
                            labelBoxes.push(drawAxisLabel(axis.ticks[0], labelBoxes));
                            labelBoxes.push(drawAxisLabel(axis.ticks[axis.ticks.length - 1], labelBoxes));
                            for (i = 1; i < axis.ticks.length - 1; ++i) {
                                labelBoxes.push(drawAxisLabel(axis.ticks[i], labelBoxes));
                            }
                            break;
                        case 'all':
                            labelBoxes.push(drawAxisLabel(axis.ticks[0], []));
                            labelBoxes.push(drawAxisLabel(axis.ticks[axis.ticks.length - 1], labelBoxes));
                            for (i = 1; i < axis.ticks.length - 1; ++i) {
                                labelBoxes.push(drawAxisLabel(axis.ticks[i], labelBoxes));
                            }
                            break;
                    }
                });
            }

            function drawSeries$1(series) {
                if (series.lines.show) {
                    drawSeries.drawSeriesLines(series, ctx, plotOffset, plotWidth, plotHeight, plot.drawSymbol, getColorOrGradient);
                }

                if (series.bars.show) {
                    drawSeries.drawSeriesBars(series, ctx, plotOffset, plotWidth, plotHeight, plot.drawSymbol, getColorOrGradient);
                }

                if (series.points.show) {
                    drawSeries.drawSeriesPoints(series, ctx, plotOffset, plotWidth, plotHeight, plot.drawSymbol, getColorOrGradient);
                }
            }

            function computeRangeForDataSeries(series, force, isValid) {
                var points = series.datapoints.points,
                    ps = series.datapoints.pointsize,
                    format = series.datapoints.format,
                    topSentry = Number.POSITIVE_INFINITY,
                    bottomSentry = Number.NEGATIVE_INFINITY,
                    range = {
                        xmin: topSentry,
                        ymin: topSentry,
                        xmax: bottomSentry,
                        ymax: bottomSentry
                    };

                for (var j = 0; j < points.length; j += ps) {
                    if (points[j] === null) {
                        continue;
                    }

                    if (typeof (isValid) === 'function' && !isValid(points[j])) {
                        continue;
                    }

                    for (var m = 0; m < ps; ++m) {
                        var val = points[j + m],
                            f = format[m];
                        if (f === null || f === undefined) {
                            continue;
                        }

                        if (typeof (isValid) === 'function' && !isValid(val)) {
                            continue;
                        }

                        if ((!force && !f.computeRange) || val === Infinity || val === -Infinity) {
                            continue;
                        }

                        if (f.x === true) {
                            if (val < range.xmin) {
                                range.xmin = val;
                            }

                            if (val > range.xmax) {
                                range.xmax = val;
                            }
                        }

                        if (f.y === true) {
                            if (val < range.ymin) {
                                range.ymin = val;
                            }

                            if (val > range.ymax) {
                                range.ymax = val;
                            }
                        }
                    }
                }

                return range;
            }
            function adjustSeriesDataRange(series, range) {
                if (series.bars.show) {
                    // make sure we got room for the bar on the dancing floor
                    var delta;

                    // update bar width if needed
                    var useAbsoluteBarWidth = series.bars.barWidth[1];
                    if (series.datapoints && series.datapoints.points && !useAbsoluteBarWidth) {
                        computeBarWidth(series);
                    }

                    var barWidth = series.bars.barWidth[0] || series.bars.barWidth;
                    switch (series.bars.align) {
                        case "left":
                            delta = 0;
                            break;
                        case "right":
                            delta = -barWidth;
                            break;
                        default:
                            delta = -barWidth / 2;
                    }

                    if (series.bars.horizontal) {
                        range.ymin += delta;
                        range.ymax += delta + barWidth;
                    } else {
                        range.xmin += delta;
                        range.xmax += delta + barWidth;
                    }
                }

                if ((series.bars.show && series.bars.zero) || (series.lines.show && series.lines.zero)) {
                    var ps = series.datapoints.pointsize;

                    // make sure the 0 point is included in the computed y range when requested
                    if (ps <= 2) {
                        /*if ps > 0 the points were already taken into account for autoScale */
                        range.ymin = Math.min(0, range.ymin);
                        range.ymax = Math.max(0, range.ymax);
                    }
                }

                return range;
            }
            function computeBarWidth(series) {
                var xValues = [];
                var pointsize = series.datapoints.pointsize, minDistance = Number.MAX_VALUE;

                if (series.datapoints.points.length <= pointsize) {
                    minDistance = 1;
                }

                var start = series.bars.horizontal ? 1 : 0;
                for (let j = start; j < series.datapoints.points.length; j += pointsize) {
                    if (isFinite(series.datapoints.points[j]) && series.datapoints.points[j] !== null) {
                        xValues.push(series.datapoints.points[j]);
                    }
                }

                function onlyUnique(value, index, self) {
                    return self.indexOf(value) === index;
                }

                xValues = xValues.filter(onlyUnique);
                xValues.sort(function(a, b) { return a - b });

                for (let j = 1; j < xValues.length; j++) {
                    var distance = Math.abs(xValues[j] - xValues[j - 1]);
                    if (distance < minDistance && isFinite(distance)) {
                        minDistance = distance;
                    }
                }

                if (typeof series.bars.barWidth === "number") {
                    series.bars.barWidth = series.bars.barWidth * minDistance;
                } else {
                    series.bars.barWidth[0] = series.bars.barWidth[0] * minDistance;
                }
            }

            function findNearbyItems(mouseX, mouseY, seriesFilter, radius, computeDistance) {
                var items = findItems(mouseX, mouseY, seriesFilter, radius, computeDistance);
                for (var i = 0; i < series.length; ++i) {
                    if (seriesFilter(i)) {
                        executeHooks(hooks.findNearbyItems, [mouseX, mouseY, series, i, radius, computeDistance, items]);
                    }
                }

                return items.sort((a, b) => {
                    if (b.distance === undefined) {
                        return -1;
                    } else if (a.distance === undefined && b.distance !== undefined) {
                        return 1;
                    }

                    return a.distance - b.distance;
                });
            }

            function findNearbyItem(mouseX, mouseY, seriesFilter, radius, computeDistance) {
                var items = findNearbyItems(mouseX, mouseY, seriesFilter, radius, computeDistance);
                return items[0] !== undefined ? items[0] : null;
            }

            // returns the data item the mouse is over/ the cursor is closest to, or null if none is found
            function findItems(mouseX, mouseY, seriesFilter, radius, computeDistance) {
                var i, foundItems = [],
                    items = [],
                    smallestDistance = radius * radius + 1;

                for (i = series.length - 1; i >= 0; --i) {
                    if (!seriesFilter(i)) continue;

                    var s = series[i];
                    if (!s.datapoints) return;

                    var foundPoint = false;
                    if (s.lines.show || s.points.show) {
                        var found = findNearbyPoint(s, mouseX, mouseY, radius, computeDistance);
                        if (found) {
                            items.push({ seriesIndex: i, dataIndex: found.dataIndex, distance: found.distance });
                            foundPoint = true;
                        }
                    }

                    if (s.bars.show && !foundPoint) { // no other point can be nearby
                        var foundIndex = findNearbyBar(s, mouseX, mouseY);
                        if (foundIndex >= 0) {
                            items.push({ seriesIndex: i, dataIndex: foundIndex, distance: smallestDistance });
                        }
                    }
                }

                for (i = 0; i < items.length; i++) {
                    var seriesIndex = items[i].seriesIndex;
                    var dataIndex = items[i].dataIndex;
                    var itemDistance = items[i].distance;
                    var ps = series[seriesIndex].datapoints.pointsize;

                    foundItems.push({
                        datapoint: series[seriesIndex].datapoints.points.slice(dataIndex * ps, (dataIndex + 1) * ps),
                        dataIndex: dataIndex,
                        series: series[seriesIndex],
                        seriesIndex: seriesIndex,
                        distance: Math.sqrt(itemDistance)
                    });
                }

                return foundItems;
            }

            function findNearbyPoint (series, mouseX, mouseY, maxDistance, computeDistance) {
                var mx = series.xaxis.c2p(mouseX),
                    my = series.yaxis.c2p(mouseY),
                    maxx = maxDistance / series.xaxis.scale,
                    maxy = maxDistance / series.yaxis.scale,
                    points = series.datapoints.points,
                    ps = series.datapoints.pointsize,
                    smallestDistance = Number.POSITIVE_INFINITY;

                // with inverse transforms, we can't use the maxx/maxy
                // optimization, sadly
                if (series.xaxis.options.inverseTransform) {
                    maxx = Number.MAX_VALUE;
                }

                if (series.yaxis.options.inverseTransform) {
                    maxy = Number.MAX_VALUE;
                }

                var found = null;
                for (var j = 0; j < points.length; j += ps) {
                    var x = points[j];
                    var y = points[j + 1];
                    if (x == null) {
                        continue;
                    }

                    if (x - mx > maxx || x - mx < -maxx ||
                        y - my > maxy || y - my < -maxy) {
                        continue;
                    }

                    // We have to calculate distances in pixels, not in
                    // data units, because the scales of the axes may be different
                    var dx = Math.abs(series.xaxis.p2c(x) - mouseX);
                    var dy = Math.abs(series.yaxis.p2c(y) - mouseY);
                    var dist = computeDistance ? computeDistance(dx, dy) : dx * dx + dy * dy;

                    // use <= to ensure last point takes precedence
                    // (last generally means on top of)
                    if (dist < smallestDistance) {
                        smallestDistance = dist;
                        found = { dataIndex: j / ps, distance: dist };
                    }
                }

                return found;
            }

            function findNearbyBar (series, mouseX, mouseY) {
                var barLeft, barRight,
                    barWidth = series.bars.barWidth[0] || series.bars.barWidth,
                    mx = series.xaxis.c2p(mouseX),
                    my = series.yaxis.c2p(mouseY),
                    points = series.datapoints.points,
                    ps = series.datapoints.pointsize;

                switch (series.bars.align) {
                    case "left":
                        barLeft = 0;
                        break;
                    case "right":
                        barLeft = -barWidth;
                        break;
                    default:
                        barLeft = -barWidth / 2;
                }

                barRight = barLeft + barWidth;

                var fillTowards = series.bars.fillTowards || 0;
                var defaultBottom = fillTowards > series.yaxis.min ? Math.min(series.yaxis.max, fillTowards) : series.yaxis.min;

                var foundIndex = -1;
                for (var j = 0; j < points.length; j += ps) {
                    var x = points[j], y = points[j + 1];
                    if (x == null) {
                        continue;
                    }

                    var bottom = ps === 3 ? points[j + 2] : defaultBottom;
                    // for a bar graph, the cursor must be inside the bar
                    if (series.bars.horizontal
                        ? (mx <= Math.max(bottom, x) && mx >= Math.min(bottom, x) &&
                            my >= y + barLeft && my <= y + barRight)
                        : (mx >= x + barLeft && mx <= x + barRight &&
                            my >= Math.min(bottom, y) && my <= Math.max(bottom, y))) {
                        foundIndex = j / ps;
                    }
                }

                return foundIndex;
            }

            function findNearbyInterpolationPoint(posX, posY, seriesFilter) {
                var i, j, dist, dx, dy, ps,
                    item,
                    smallestDistance = Number.MAX_VALUE;

                for (i = 0; i < series.length; ++i) {
                    if (!seriesFilter(i)) {
                        continue;
                    }
                    var points = series[i].datapoints.points;
                    ps = series[i].datapoints.pointsize;

                    // if the data is coming from positive -> negative, reverse the comparison
                    const comparer = points[points.length - ps] < points[0]
                        ? function (x1, x2) { return x1 > x2 }
                        : function (x1, x2) { return x2 > x1 };

                    // do not interpolate outside the bounds of the data.
                    if (comparer(posX, points[0])) {
                        continue;
                    }

                    // Find the nearest points, x-wise
                    for (j = ps; j < points.length; j += ps) {
                        if (comparer(posX, points[j])) {
                            break;
                        }
                    }

                    // Now Interpolate
                    var y,
                        p1x = points[j - ps],
                        p1y = points[j - ps + 1],
                        p2x = points[j],
                        p2y = points[j + 1];

                    if ((p1x === undefined) || (p2x === undefined) ||
                        (p1y === undefined) || (p2y === undefined)) {
                        continue;
                    }

                    if (p1x === p2x) {
                        y = p2y;
                    } else {
                        y = p1y + (p2y - p1y) * (posX - p1x) / (p2x - p1x);
                    }

                    posY = y;

                    dx = Math.abs(series[i].xaxis.p2c(p2x) - posX);
                    dy = Math.abs(series[i].yaxis.p2c(p2y) - posY);
                    dist = dx * dx + dy * dy;

                    if (dist < smallestDistance) {
                        smallestDistance = dist;
                        item = [posX, posY, i, j];
                    }
                }

                if (item) {
                    i = item[2];
                    j = item[3];
                    ps = series[i].datapoints.pointsize;
                    points = series[i].datapoints.points;
                    p1x = points[j - ps];
                    p1y = points[j - ps + 1];
                    p2x = points[j];
                    p2y = points[j + 1];

                    return {
                        datapoint: [item[0], item[1]],
                        leftPoint: [p1x, p1y],
                        rightPoint: [p2x, p2y],
                        seriesIndex: i
                    };
                }

                return null;
            }

            function triggerRedrawOverlay() {
                var t = options.interaction.redrawOverlayInterval;
                if (t === -1) { // skip event queue
                    drawOverlay();
                    return;
                }

                if (!redrawTimeout) {
                    redrawTimeout = window.setTimeout(function() {
                        drawOverlay(plot);
                    }, t);
                }
            }

            function drawOverlay(plot) {
                redrawTimeout = null;

                if (!octx) {
                    return;
                }
                overlay.clear();
                executeHooks(hooks.drawOverlay, [octx, overlay]);
                var event = new CustomEvent('onDrawingDone');
                plot.getEventHolder().dispatchEvent(event);
                trigger(plot.getPlaceholder(), 'drawingdone');
            }

            function getColorOrGradient(spec, bottom, top, defaultColor) {
                if (typeof spec === "string") {
                    return spec;
                } else {
                    // assume this is a gradient spec; IE currently only
                    // supports a simple vertical gradient properly, so that's
                    // what we support too
                    var gradient = ctx.createLinearGradient(0, top, 0, bottom);

                    for (var i = 0, l = spec.colors.length; i < l; ++i) {
                        var c = spec.colors[i];
                        if (typeof c !== "string") {
                            var co = color.parse(defaultColor);
                            if (c.brightness != null) {
                                co = co.scale('rgb', c.brightness);
                            }

                            if (c.opacity != null) {
                                co.a *= c.opacity;
                            }

                            c = co.toString();
                        }
                        gradient.addColorStop(i / (l - 1), c);
                    }

                    return gradient;
                }
            }
        }

        // Plugin registry. Plugins push to this array to register themselves.
        var plugins = [];

        var version = "5.1.0";

        // The main plot function.
        function plot(placeholder, data, options) {
            var el = typeof placeholder === 'string' ? document.querySelector(placeholder) : placeholder;
            return new Plot(el, data, options, plugins);
        }

        var linearTickGenerator = defaultTickGenerator;

    /* Flot plugin for plotting error bars.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    Error bars are used to show standard deviation and other statistical
    properties in a plot.

    * Created by Rui Pereira  -  rui (dot) pereira (at) gmail (dot) com

    This plugin allows you to plot error-bars over points. Set "errorbars" inside
    the points series to the axis name over which there will be error values in
    your data array (*even* if you do not intend to plot them later, by setting
    "show: null" on xerr/yerr).

    The plugin supports these options:

        series: {
            points: {
                errorbars: "x" or "y" or "xy",
                xerr: {
                    show: null/false or true,
                    asymmetric: null/false or true,
                    upperCap: null or "-" or function,
                    lowerCap: null or "-" or function,
                    color: null or color,
                    radius: null or number
                },
                yerr: { same options as xerr }
            }
        }

    Each data point array is expected to be of the type:

        "x"  [ x, y, xerr ]
        "y"  [ x, y, yerr ]
        "xy" [ x, y, xerr, yerr ]

    Where xerr becomes xerr_lower,xerr_upper for the asymmetric error case, and
    equivalently for yerr. Eg., a datapoint for the "xy" case with symmetric
    error-bars on X and asymmetric on Y would be:

        [ x, y, xerr, yerr_lower, yerr_upper ]

    By default no end caps are drawn. Setting upperCap and/or lowerCap to "-" will
    draw a small cap perpendicular to the error bar. They can also be set to a
    user-defined drawing function, with (ctx, x, y, radius) as parameters, as eg.

        function drawSemiCircle( ctx, x, y, radius ) {
            ctx.beginPath();
            ctx.arc( x, y, radius, 0, Math.PI, false );
            ctx.moveTo( x - radius, y );
            ctx.lineTo( x + radius, y );
            ctx.stroke();
        }

    Color and radius both default to the same ones of the points series if not
    set. The independent radius parameter on xerr/yerr is useful for the case when
    we may want to add error-bars to a line, without showing the interconnecting
    points (with radius: 0), and still showing end caps on the error-bars.
    shadowSize and lineWidth are derived as well from the points series.

    */


        var options$a = {
            series: {
                points: {
                    errorbars: null, //should be 'x', 'y' or 'xy'
                    xerr: {err: 'x', show: null, asymmetric: null, upperCap: null, lowerCap: null, color: null, radius: null},
                    yerr: {err: 'y', show: null, asymmetric: null, upperCap: null, lowerCap: null, color: null, radius: null}
                }
            }
        };

        function processRawData$1(plot, series, data, datapoints) {
            if (!series.points.errorbars) {
                return;
            }

            // x,y values
            var format = [
                { x: true, number: true, required: true },
                { y: true, number: true, required: true }
            ];

            var errors = series.points.errorbars;
            // error bars - first X then Y
            if (errors === 'x' || errors === 'xy') {
                // lower / upper error
                if (series.points.xerr.asymmetric) {
                    format.push({ x: true, number: true, required: true });
                    format.push({ x: true, number: true, required: true });
                } else {
                    format.push({ x: true, number: true, required: true });
                }
            }
            if (errors === 'y' || errors === 'xy') {
                // lower / upper error
                if (series.points.yerr.asymmetric) {
                    format.push({ y: true, number: true, required: true });
                    format.push({ y: true, number: true, required: true });
                } else {
                    format.push({ y: true, number: true, required: true });
                }
            }
            datapoints.format = format;
        }

        function parseErrors(series, i) {
            var points = series.datapoints.points;

            // read errors from points array
            var exl = null,
                exu = null,
                eyl = null,
                eyu = null;
            var xerr = series.points.xerr,
                yerr = series.points.yerr;

            var eb = series.points.errorbars;
            // error bars - first X
            if (eb === 'x' || eb === 'xy') {
                if (xerr.asymmetric) {
                    exl = points[i + 2];
                    exu = points[i + 3];
                    if (eb === 'xy') {
                        if (yerr.asymmetric) {
                            eyl = points[i + 4];
                            eyu = points[i + 5];
                        } else {
                            eyl = points[i + 4];
                        }
                    }
                } else {
                    exl = points[i + 2];
                    if (eb === 'xy') {
                        if (yerr.asymmetric) {
                            eyl = points[i + 3];
                            eyu = points[i + 4];
                        } else {
                            eyl = points[i + 3];
                        }
                    }
                }
            // only Y
            } else {
                if (eb === 'y') {
                    if (yerr.asymmetric) {
                        eyl = points[i + 2];
                        eyu = points[i + 3];
                    } else {
                        eyl = points[i + 2];
                    }
                }
            }

            // symmetric errors?
            if (exu == null) exu = exl;
            if (eyu == null) eyu = eyl;

            var errRanges = [exl, exu, eyl, eyu];
            // nullify if not showing
            if (!xerr.show) {
                errRanges[0] = null;
                errRanges[1] = null;
            }
            if (!yerr.show) {
                errRanges[2] = null;
                errRanges[3] = null;
            }
            return errRanges;
        }

        function drawSeriesErrors(plot, ctx, s) {
            var points = s.datapoints.points,
                ps = s.datapoints.pointsize,
                ax = [s.xaxis, s.yaxis],
                radius = s.points.radius,
                err = [s.points.xerr, s.points.yerr],
                tmp;

            //sanity check, in case some inverted axis hack is applied to flot
            var invertX = false;
            if (ax[0].p2c(ax[0].max) < ax[0].p2c(ax[0].min)) {
                invertX = true;
                tmp = err[0].lowerCap;
                err[0].lowerCap = err[0].upperCap;
                err[0].upperCap = tmp;
            }

            var invertY = false;
            if (ax[1].p2c(ax[1].min) < ax[1].p2c(ax[1].max)) {
                invertY = true;
                tmp = err[1].lowerCap;
                err[1].lowerCap = err[1].upperCap;
                err[1].upperCap = tmp;
            }

            for (var i = 0; i < s.datapoints.points.length; i += ps) {
                //parse
                var errRanges = parseErrors(s, i);

                //cycle xerr & yerr
                for (var e = 0; e < err.length; e++) {
                    var minmax = [ax[e].min, ax[e].max];

                    //draw this error?
                    if (errRanges[e * err.length]) {
                        //data coordinates
                        var x = points[i],
                            y = points[i + 1];

                        //errorbar ranges
                        var upper = [x, y][e] + errRanges[e * err.length + 1],
                            lower = [x, y][e] - errRanges[e * err.length];

                        //points outside of the canvas
                        if (err[e].err === 'x') {
                            if (y > ax[1].max || y < ax[1].min || upper < ax[0].min || lower > ax[0].max) {
                                continue;
                            }
                        }

                        if (err[e].err === 'y') {
                            if (x > ax[0].max || x < ax[0].min || upper < ax[1].min || lower > ax[1].max) {
                                continue;
                            }
                        }

                        // prevent errorbars getting out of the canvas
                        var drawUpper = true,
                            drawLower = true;

                        if (upper > minmax[1]) {
                            drawUpper = false;
                            upper = minmax[1];
                        }
                        if (lower < minmax[0]) {
                            drawLower = false;
                            lower = minmax[0];
                        }

                        //sanity check, in case some inverted axis hack is applied to flot
                        if ((err[e].err === 'x' && invertX) || (err[e].err === 'y' && invertY)) {
                            //swap coordinates
                            tmp = lower;
                            lower = upper;
                            upper = tmp;
                            tmp = drawLower;
                            drawLower = drawUpper;
                            drawUpper = tmp;
                            tmp = minmax[0];
                            minmax[0] = minmax[1];
                            minmax[1] = tmp;
                        }

                        // convert to pixels
                        x = ax[0].p2c(x);
                        y = ax[1].p2c(y);
                        upper = ax[e].p2c(upper);
                        lower = ax[e].p2c(lower);
                        minmax[0] = ax[e].p2c(minmax[0]);
                        minmax[1] = ax[e].p2c(minmax[1]);

                        //same style as points by default
                        var lw = err[e].lineWidth ? err[e].lineWidth : s.points.lineWidth,
                            sw = s.points.shadowSize != null ? s.points.shadowSize : s.shadowSize;

                        //shadow as for points
                        if (lw > 0 && sw > 0) {
                            var w = sw / 2;
                            ctx.lineWidth = w;
                            ctx.strokeStyle = "rgba(0,0,0,0.1)";
                            drawError(ctx, err[e], x, y, upper, lower, drawUpper, drawLower, radius, w + w / 2, minmax);

                            ctx.strokeStyle = "rgba(0,0,0,0.2)";
                            drawError(ctx, err[e], x, y, upper, lower, drawUpper, drawLower, radius, w / 2, minmax);
                        }

                        ctx.strokeStyle = err[e].color
                            ? err[e].color
                            : s.color;
                        ctx.lineWidth = lw;
                        //draw it
                        drawError(ctx, err[e], x, y, upper, lower, drawUpper, drawLower, radius, 0, minmax);
                    }
                }
            }
        }

        function drawError(ctx, err, x, y, upper, lower, drawUpper, drawLower, radius, offset, minmax) {
            //shadow offset
            y += offset;
            upper += offset;
            lower += offset;

            // error bar - avoid plotting over circles
            if (err.err === 'x') {
                if (upper > x + radius) drawPath(ctx, [[upper, y], [Math.max(x + radius, minmax[0]), y]]);
                else drawUpper = false;

                if (lower < x - radius) drawPath(ctx, [[Math.min(x - radius, minmax[1]), y], [lower, y]]);
                else drawLower = false;
            } else {
                if (upper < y - radius) drawPath(ctx, [[x, upper], [x, Math.min(y - radius, minmax[0])]]);
                else drawUpper = false;

                if (lower > y + radius) drawPath(ctx, [[x, Math.max(y + radius, minmax[1])], [x, lower]]);
                else drawLower = false;
            }

            //internal radius value in errorbar, allows to plot radius 0 points and still keep proper sized caps
            //this is a way to get errorbars on lines without visible connecting dots
            radius = err.radius != null
                ? err.radius
                : radius;

            // upper cap
            if (drawUpper) {
                if (err.upperCap === '-') {
                    if (err.err === 'x') drawPath(ctx, [[upper, y - radius], [upper, y + radius]]);
                    else drawPath(ctx, [[x - radius, upper], [x + radius, upper]]);
                } else if (typeof err.upperCap === 'function') {
                    if (err.err === 'x') err.upperCap(ctx, upper, y, radius);
                    else err.upperCap(ctx, x, upper, radius);
                }
            }
            // lower cap
            if (drawLower) {
                if (err.lowerCap === '-') {
                    if (err.err === 'x') drawPath(ctx, [[lower, y - radius], [lower, y + radius]]);
                    else drawPath(ctx, [[x - radius, lower], [x + radius, lower]]);
                } else if (typeof err.lowerCap === 'function') {
                    if (err.err === 'x') err.lowerCap(ctx, lower, y, radius);
                    else err.lowerCap(ctx, x, lower, radius);
                }
            }
        }

        function drawPath(ctx, pts) {
            ctx.beginPath();
            ctx.moveTo(pts[0][0], pts[0][1]);
            for (var p = 1; p < pts.length; p++) {
                ctx.lineTo(pts[p][0], pts[p][1]);
            }

            ctx.stroke();
        }

        function draw(plot, ctx) {
            var plotOffset = plot.getPlotOffset();

            ctx.save();
            ctx.translate(plotOffset.left, plotOffset.top);
            plot.getData().forEach(function (s) {
                if (s.points.errorbars && (s.points.xerr.show || s.points.yerr.show)) {
                    drawSeriesErrors(plot, ctx, s);
                }
            });
            ctx.restore();
        }

        function init$e(plot) {
            plot.hooks.processRawData.push(processRawData$1);
            plot.hooks.draw.push(draw);
        }

        plugins.push({
            init: init$e,
            options: options$a,
            name: 'errorbars',
            version: '1.0'
        });

    /* Pretty handling of log axes.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Copyright (c) 2015 Ciprian Ceteras cipix2000@gmail.com.
    Copyright (c) 2017 Raluca Portase
    Licensed under the MIT license.

    Set axis.mode to "log" to enable.
    */


        var options$9 = {
            xaxis: {}
        };

        /*tick generators and formatters*/
        var PREFERRED_LOG_TICK_VALUES = computePreferedLogTickValues(Number.MAX_VALUE, 10),
            EXTENDED_LOG_TICK_VALUES = computePreferedLogTickValues(Number.MAX_VALUE, 4);

        function computePreferedLogTickValues(endLimit, rangeStep) {
            var log10End = Math.floor(Math.log(endLimit) * Math.LOG10E) - 1,
                log10Start = -log10End,
                val, range, vals = [];

            for (var power = log10Start; power <= log10End; power++) {
                range = parseFloat('1e' + power);
                for (var mult = 1; mult < 9; mult += rangeStep) {
                    val = range * mult;
                    vals.push(val);
                }
            }
            return vals;
        }

        /**
        - logTickGenerator(plot, axis, noTicks)

        Generates logarithmic ticks, depending on axis range.
        In case the number of ticks that can be generated is less than the expected noTicks/4,
        a linear tick generation is used.
        */
        var logTickGenerator = function (plot, axis, noTicks) {
            var ticks = [],
                minIdx = -1,
                maxIdx = -1,
                surface = plot.getCanvas(),
                logTickValues = PREFERRED_LOG_TICK_VALUES,
                min = clampAxis(axis, plot),
                max = axis.max;

            if (!noTicks) {
                noTicks = 0.3 * Math.sqrt(axis.direction === "x" ? surface.width : surface.height);
            }

            PREFERRED_LOG_TICK_VALUES.some(function (val, i) {
                if (val >= min) {
                    minIdx = i;
                    return true;
                } else {
                    return false;
                }
            });

            PREFERRED_LOG_TICK_VALUES.some(function (val, i) {
                if (val >= max) {
                    maxIdx = i;
                    return true;
                } else {
                    return false;
                }
            });

            if (maxIdx === -1) {
                maxIdx = PREFERRED_LOG_TICK_VALUES.length - 1;
            }

            if (maxIdx - minIdx <= noTicks / 4 && logTickValues.length !== EXTENDED_LOG_TICK_VALUES.length) {
                //try with multiple of 5 for tick values
                logTickValues = EXTENDED_LOG_TICK_VALUES;
                minIdx *= 2;
                maxIdx *= 2;
            }

            var lastDisplayed = null,
                inverseNoTicks = 1 / noTicks,
                tickValue, pixelCoord, tick;

            // Count the number of tick values would appear, if we can get at least
            // nTicks / 4 accept them.
            if (maxIdx - minIdx >= noTicks / 4) {
                for (var idx = maxIdx; idx >= minIdx; idx--) {
                    tickValue = logTickValues[idx];
                    pixelCoord = (Math.log(tickValue) - Math.log(min)) / (Math.log(max) - Math.log(min));
                    tick = tickValue;

                    if (lastDisplayed === null) {
                        lastDisplayed = {
                            pixelCoord: pixelCoord,
                            idealPixelCoord: pixelCoord
                        };
                    } else {
                        if (Math.abs(pixelCoord - lastDisplayed.pixelCoord) >= inverseNoTicks) {
                            lastDisplayed = {
                                pixelCoord: pixelCoord,
                                idealPixelCoord: lastDisplayed.idealPixelCoord - inverseNoTicks
                            };
                        } else {
                            tick = null;
                        }
                    }

                    if (tick) {
                        ticks.push(tick);
                    }
                }
                // Since we went in backwards order.
                ticks.reverse();
            } else {
                var tickSize = plot.computeTickSize(min, max, noTicks),
                    customAxis = {min: min, max: max, tickSize: tickSize};
                ticks = linearTickGenerator(customAxis);
            }

            return ticks;
        };

        var clampAxis = function (axis, plot) {
            var min = axis.min,
                max = axis.max;

            if (min <= 0) {
                //for empty graph if axis.min is not strictly positive make it 0.1
                if (axis.datamin === null) {
                    min = axis.min = 0.1;
                } else {
                    min = processAxisOffset(plot, axis);
                }

                if (max < min) {
                    axis.max = axis.datamax !== null ? axis.datamax : axis.options.max;
                    axis.options.offset.below = 0;
                    axis.options.offset.above = 0;
                }
            }

            return min;
        };

        /**
        - logTickFormatter(value, axis, precision)

        This is the corresponding tickFormatter of the logaxis.
        For a number greater that 10^6 or smaller than 10^(-3), this will be drawn
        with e representation
        */
        var logTickFormatter = function (value, axis, precision) {
            var tenExponent = value > 0 ? Math.floor(Math.log(value) / Math.LN10) : 0;

            if (precision) {
                if ((tenExponent >= -4) && (tenExponent <= 7)) {
                    return defaultTickFormatter(value, axis, precision);
                } else {
                    return expRepTickFormatter(value, axis, precision);
                }
            }
            if ((tenExponent >= -4) && (tenExponent <= 7)) {
                //if we have float numbers, return a limited length string(ex: 0.0009 is represented as 0.000900001)
                var formattedValue = tenExponent < 0 ? value.toFixed(-tenExponent) : value.toFixed(tenExponent + 2);
                if (formattedValue.indexOf('.') !== -1) {
                    var lastZero = formattedValue.lastIndexOf('0');

                    while (lastZero === formattedValue.length - 1) {
                        formattedValue = formattedValue.slice(0, -1);
                        lastZero = formattedValue.lastIndexOf('0');
                    }

                    //delete the dot if is last
                    if (formattedValue.indexOf('.') === formattedValue.length - 1) {
                        formattedValue = formattedValue.slice(0, -1);
                    }
                }
                return formattedValue;
            } else {
                return expRepTickFormatter(value, axis);
            }
        };

        /*logaxis caracteristic functions*/
        var logTransform = function (v) {
            if (v < PREFERRED_LOG_TICK_VALUES[0]) {
                v = PREFERRED_LOG_TICK_VALUES[0];
            }

            return Math.log(v);
        };

        var logInverseTransform = function (v) {
            return Math.exp(v);
        };

        var invertedTransform = function (v) {
            return -v;
        };

        var invertedLogTransform = function (v) {
            return -logTransform(v);
        };

        var invertedLogInverseTransform = function (v) {
            return logInverseTransform(-v);
        };

        /**
        - setDataminRange(plot, axis)

        It is used for clamping the starting point of a logarithmic axis.
        This will set the axis datamin range to 0.1 or to the first datapoint greater then 0.
        The function is usefull since the logarithmic representation can not show
        values less than or equal to 0.
        */
        function setDataminRange(plot, axis) {
            if (axis.options.mode === 'log' && axis.datamin <= 0) {
                if (axis.datamin === null) {
                    axis.datamin = 0.1;
                } else {
                    axis.datamin = processAxisOffset(plot, axis);
                }
            }
        }

        function processAxisOffset(plot, axis) {
            var series = plot.getData(),
                range = series
                    .filter(function(series) {
                        return series.xaxis === axis || series.yaxis === axis;
                    })
                    .map(function(series) {
                        return plot.computeRangeForDataSeries(series, null, isValid);
                    }),
                min = axis.direction === 'x'
                    ? Math.min(0.1, range && range[0] ? range[0].xmin : 0.1)
                    : Math.min(0.1, range && range[0] ? range[0].ymin : 0.1);

            axis.min = min;

            return min;
        }

        function isValid(a) {
            return a > 0;
        }

        function init$d(plot) {
            plot.hooks.processOptions.push(function (plot) {
                var axes = plot.getAxes();
                Object.keys(axes).forEach(function (axisName) {
                    var axis = axes[axisName];
                    var opts = axis.options;
                    if (opts.mode === 'log') {
                        axis.tickGenerator = function (axis) {
                            var noTicks = 11;
                            return logTickGenerator(plot, axis, noTicks);
                        };
                        if (typeof axis.options.tickFormatter !== 'function') {
                            axis.options.tickFormatter = logTickFormatter;
                        }
                        axis.options.transform = opts.inverted ? invertedLogTransform : logTransform;
                        axis.options.inverseTransform = opts.inverted ? invertedLogInverseTransform : logInverseTransform;
                        axis.options.autoScaleMargin = 0;
                        plot.hooks.setRange.push(setDataminRange);
                    } else if (opts.inverted) {
                        axis.options.transform = invertedTransform;
                        axis.options.inverseTransform = invertedTransform;
                    }
                });
            });
        }

        plugins.push({
            init: init$d,
            options: options$9,
            name: 'log',
            version: '0.1'
        });

        var logTicksGenerator = logTickGenerator;

    /* Flot plugin that adds some extra symbols for plotting points.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    The symbols are accessed as strings through the standard symbol options:

        series: {
            points: {
                symbol: "square" // or "diamond", "triangle", "cross", "plus", "ellipse", "rectangle"
            }
        }

    */


        // we normalize the area of each symbol so it is approximately the
        // same as a circle of the given radius

        var square = function (ctx, x, y, radius, shadow) {
                // pi * r^2 = (2s)^2  =>  s = r * sqrt(pi)/2
                var size = radius * Math.sqrt(Math.PI) / 2;
                ctx.rect(x - size, y - size, size + size, size + size);
            },
            rectangle = function (ctx, x, y, radius, shadow) {
                // pi * r^2 = (2s)^2  =>  s = r * sqrt(pi)/2
                var size = radius * Math.sqrt(Math.PI) / 2;
                ctx.rect(x - size, y - size, size + size, size + size);
            },
            diamond = function (ctx, x, y, radius, shadow) {
                // pi * r^2 = 2s^2  =>  s = r * sqrt(pi/2)
                var size = radius * Math.sqrt(Math.PI / 2);
                ctx.moveTo(x - size, y);
                ctx.lineTo(x, y - size);
                ctx.lineTo(x + size, y);
                ctx.lineTo(x, y + size);
                ctx.lineTo(x - size, y);
                ctx.lineTo(x, y - size);
            },
            triangle = function (ctx, x, y, radius, shadow) {
                // pi * r^2 = 1/2 * s^2 * sin (pi / 3)  =>  s = r * sqrt(2 * pi / sin(pi / 3))
                var size = radius * Math.sqrt(2 * Math.PI / Math.sin(Math.PI / 3));
                var height = size * Math.sin(Math.PI / 3);
                ctx.moveTo(x - size / 2, y + height / 2);
                ctx.lineTo(x + size / 2, y + height / 2);
                if (!shadow) {
                    ctx.lineTo(x, y - height / 2);
                    ctx.lineTo(x - size / 2, y + height / 2);
                    ctx.lineTo(x + size / 2, y + height / 2);
                }
            },
            cross = function (ctx, x, y, radius, shadow) {
                // pi * r^2 = (2s)^2  =>  s = r * sqrt(pi)/2
                var size = radius * Math.sqrt(Math.PI) / 2;
                ctx.moveTo(x - size, y - size);
                ctx.lineTo(x + size, y + size);
                ctx.moveTo(x - size, y + size);
                ctx.lineTo(x + size, y - size);
            },
            ellipse = function(ctx, x, y, radius, shadow, fill) {
                if (!shadow) {
                    ctx.moveTo(x + radius, y);
                    ctx.arc(x, y, radius, 0, Math.PI * 2, false);
                }
            },
            plus = function (ctx, x, y, radius, shadow) {
                var size = radius * Math.sqrt(Math.PI / 2);
                ctx.moveTo(x - size, y);
                ctx.lineTo(x + size, y);
                ctx.moveTo(x, y + size);
                ctx.lineTo(x, y - size);
            },
            handlers = {
                square: square,
                rectangle: rectangle,
                diamond: diamond,
                triangle: triangle,
                cross: cross,
                ellipse: ellipse,
                plus: plus
            };

        square.fill = true;
        rectangle.fill = true;
        diamond.fill = true;
        triangle.fill = true;
        ellipse.fill = true;

        function init$c(plot) {
            plot.drawSymbol = handlers;
        }

        plugins.push({
            init: init$c,
            name: 'symbols',
            version: '1.0'
        });

    /* Support for flat 1D data series.

    A 1D flat data series is a data series in the form of a regular 1D array. The
    main reason for using a flat data series is that it performs better, consumes
    less memory and generates less garbage collection than the regular flot format.

    Example:

        plot.setData([[[0,0], [1,1], [2,2], [3,3]]]); // regular flot format
        plot.setData([{flatdata: true, data: [0, 1, 2, 3]}]); // flatdata format

    Set series.flatdata to true to enable this plugin.

    You can use series.start to specify the starting index of the series (default is 0)
    You can use series.step to specify the interval between consecutive indexes of the series (default is 1)
    */


        function process1DRawData(plot, series, data, datapoints) {
            if (series.flatdata === true) {
                var start = series.start || 0;
                var step = typeof series.step === 'number' ? series.step : 1;
                datapoints.pointsize = 2;
                for (var i = 0, j = 0; i < data.length; i++, j += 2) {
                    datapoints.points[j] = start + (i * step);
                    datapoints.points[j + 1] = data[i];
                }
                if (datapoints.points !== undefined) {
                    datapoints.points.length = data.length * 2;
                } else {
                    datapoints.points = [];
                }
            }
        }

        plugins.push({
            init: function(plot) {
                plot.hooks.processRawData.push(process1DRawData);
            },
            name: 'flatdata',
            version: '0.0.2'
        });

    /* Flot plugin for adding the ability to pan and zoom the plot.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Copyright (c) 2016 Ciprian Ceteras.
    Copyright (c) 2017 Raluca Portase.
    Licensed under the MIT license.

    */


        var options$8 = {
            zoom: {
                interactive: false,
                active: false,
                amount: 1.5 // how much to zoom relative to current position, 2 = 200% (zoom in), 0.5 = 50% (zoom out)
            },
            pan: {
                interactive: false,
                active: false,
                cursor: "move",
                frameRate: 60,
                mode: 'smart'
            },
            recenter: {
                interactive: true
            },
            xaxis: {
                axisZoom: true, //zoom axis when mouse over it is allowed
                plotZoom: true, //zoom axis is allowed for plot zoom
                axisPan: true, //pan axis when mouse over it is allowed
                plotPan: true, //pan axis is allowed for plot pan
                panRange: [undefined, undefined], // no limit on pan range, or [min, max] in axis units
                zoomRange: [undefined, undefined] // no limit on zoom range, or [closest zoom, furthest zoom] in axis units
            },
            yaxis: {
                axisZoom: true,
                plotZoom: true,
                axisPan: true,
                plotPan: true,
                panRange: [undefined, undefined], // no limit on pan range, or [min, max] in axis units
                zoomRange: [undefined, undefined] // no limit on zoom range, or [closest zoom, furthest zoom] in axis units
            }
        };

        var SNAPPING_CONSTANT = uiConstants.SNAPPING_CONSTANT;
        var PANHINT_LENGTH_CONSTANT = uiConstants.PANHINT_LENGTH_CONSTANT;

        function init$b(plot) {
            plot.hooks.processOptions.push(initNevigation);
        }

        function initNevigation(plot, options) {
            var panAxes = null;
            var useManualPan = options.pan.mode === 'manual',
                smartPanLock = options.pan.mode === 'smartLock',
                useSmartPan = smartPanLock || options.pan.mode === 'smart';

            function onZoomClick(e, zoomOut, amount) {
                var page = browser.getPageXY(e);

                var c = plot.offset();
                c.left = page.X - c.left;
                c.top = page.Y - c.top;

                var placeholderRect = plot.getPlaceholder().getBoundingClientRect();
                var ec = { left: placeholderRect.left + window.scrollX, top: placeholderRect.top + window.scrollY };
                ec.left = page.X - ec.left;
                ec.top = page.Y - ec.top;

                var axes = plot.getXAxes().concat(plot.getYAxes()).filter(function (axis) {
                    var box = axis.box;
                    if (box !== undefined) {
                        return (ec.left > box.left) && (ec.left < box.left + box.width) &&
                            (ec.top > box.top) && (ec.top < box.top + box.height);
                    }
                });

                if (axes.length === 0) {
                    axes = undefined;
                }

                if (zoomOut) {
                    plot.zoomOut({
                        center: c,
                        axes: axes,
                        amount: amount
                    });
                } else {
                    plot.zoom({
                        center: c,
                        axes: axes,
                        amount: amount
                    });
                }
            }

            var prevCursor = 'default',
                panHint = null,
                panTimeout = null,
                plotState,
                prevDragPosition = { x: 0, y: 0 },
                isPanAction = false;

            function onMouseWheel(e) {
                var delta = -e.deltaY;
                var maxAbsoluteDeltaOnMac = 1,
                    isMacScroll = Math.abs(e.deltaY) <= maxAbsoluteDeltaOnMac,
                    defaultNonMacScrollAmount = null,
                    macMagicRatio = 50,
                    amount = isMacScroll ? 1 + Math.abs(e.deltaY) / macMagicRatio : defaultNonMacScrollAmount;

                if (isPanAction) {
                    onDragEnd(e);
                }

                if (plot.getOptions().zoom.active) {
                    e.preventDefault();
                    onZoomClick(e, delta < 0, amount);
                    return false;
                }
            }

            plot.navigationState = function(startPageX, startPageY) {
                var axes = this.getAxes();
                var result = {};
                Object.keys(axes).forEach(function(axisName) {
                    var axis = axes[axisName];
                    result[axisName] = {
                        navigationOffset: { below: axis.options.offset.below || 0,
                            above: axis.options.offset.above || 0},
                        axisMin: axis.min,
                        axisMax: axis.max,
                        diagMode: false
                    };
                });

                result.startPageX = startPageX || 0;
                result.startPageY = startPageY || 0;
                return result;
            };

            function onDragStart(e) {

                isPanAction = true;
                var page = browser.getPageXY(e);

                var placeholderRect = plot.getPlaceholder().getBoundingClientRect();
                var ec = { left: placeholderRect.left + window.scrollX, top: placeholderRect.top + window.scrollY };
                ec.left = page.X - ec.left;
                ec.top = page.Y - ec.top;

                panAxes = plot.getXAxes().concat(plot.getYAxes()).filter(function (axis) {
                    var box = axis.box;
                    if (box !== undefined) {
                        return (ec.left > box.left) && (ec.left < box.left + box.width) &&
                            (ec.top > box.top) && (ec.top < box.top + box.height);
                    }
                });

                if (panAxes.length === 0) {
                    panAxes = undefined;
                }

                var c = css(plot.getPlaceholder(), 'cursor');
                if (c) {
                    prevCursor = c;
                }

                css(plot.getPlaceholder(), 'cursor', plot.getOptions().pan.cursor);

                if (useSmartPan) {
                    plotState = plot.navigationState(page.X, page.Y);
                } else if (useManualPan) {
                    prevDragPosition.x = page.X;
                    prevDragPosition.y = page.Y;
                }
            }

            function onDrag(e) {
                if (!isPanAction) {
                    return;
                }

                var page = browser.getPageXY(e);
                var frameRate = plot.getOptions().pan.frameRate;

                if (frameRate === -1) {
                    if (useSmartPan) {
                        plot.smartPan({
                            x: plotState.startPageX - page.X,
                            y: plotState.startPageY - page.Y
                        }, plotState, panAxes, false, smartPanLock);
                    } else if (useManualPan) {
                        plot.pan({
                            left: prevDragPosition.x - page.X,
                            top: prevDragPosition.y - page.Y,
                            axes: panAxes
                        });
                        prevDragPosition.x = page.X;
                        prevDragPosition.y = page.Y;
                    }
                    return;
                }

                if (panTimeout || !frameRate) return;

                panTimeout = window.setTimeout(function() {
                    if (useSmartPan) {
                        plot.smartPan({
                            x: plotState.startPageX - page.X,
                            y: plotState.startPageY - page.Y
                        }, plotState, panAxes, false, smartPanLock);
                    } else if (useManualPan) {
                        plot.pan({
                            left: prevDragPosition.x - page.X,
                            top: prevDragPosition.y - page.Y,
                            axes: panAxes
                        });
                        prevDragPosition.x = page.X;
                        prevDragPosition.y = page.Y;
                    }

                    panTimeout = null;
                }, 1 / frameRate * 1000);
            }

            function onDragEnd(e) {
                if (!isPanAction) {
                    return;
                }

                if (panTimeout) {
                    window.clearTimeout(panTimeout);
                    panTimeout = null;
                }

                isPanAction = false;
                var page = browser.getPageXY(e);

                css(plot.getPlaceholder(), 'cursor', prevCursor);

                if (useSmartPan) {
                    plot.smartPan({
                        x: plotState.startPageX - page.X,
                        y: plotState.startPageY - page.Y
                    }, plotState, panAxes, false, smartPanLock);
                    plot.smartPan.end();
                } else if (useManualPan) {
                    plot.pan({
                        left: prevDragPosition.x - page.X,
                        top: prevDragPosition.y - page.Y,
                        axes: panAxes
                    });
                    prevDragPosition.x = 0;
                    prevDragPosition.y = 0;
                }
            }

            function onDblClick(e) {
                plot.activate();
                var o = plot.getOptions();

                if (!o.recenter.interactive) { return; }

                var axes = plot.getTouchedAxis(e.clientX, e.clientY);

                plot.recenter({ axes: axes[0] ? axes : null });

                if (axes[0]) {
                    trigger(plot.getPlaceholder(), 're-center', { axisTouched: axes[0] });
                } else {
                    trigger(plot.getPlaceholder(), 're-center', e);
                }
            }

            function onClick(e) {
                plot.activate();

                if (isPanAction) {
                    onDragEnd(e);
                }

                return false;
            }

            plot.activate = function() {
                var o = plot.getOptions();
                if (!o.pan.active || !o.zoom.active) {
                    o.pan.active = true;
                    o.zoom.active = true;
                    trigger(plot.getPlaceholder(), "plotactivated", [plot]);
                }
            };

            function onPointerDown(e) {
                if (e.button !== 0) return;
                var el = e.currentTarget;
                onDragStart(e);

                function onPointerMove(e) {
                    onDrag(e);
                }

                function onPointerUp(e) {
                    onDragEnd(e);
                    el.removeEventListener("pointermove", onPointerMove);
                    el.removeEventListener("pointerup", onPointerUp);
                    el.removeEventListener("pointercancel", onPointerUp);
                    el.releasePointerCapture(e.pointerId);
                }

                el.setPointerCapture(e.pointerId);
                el.addEventListener("pointermove", onPointerMove);
                el.addEventListener("pointerup", onPointerUp);
                el.addEventListener("pointercancel", onPointerUp);
            }

            function bindEvents(plot, eventHolder) {
                var o = plot.getOptions();
                if (o.zoom.interactive) {
                    bind(eventHolder, "wheel", onMouseWheel);
                }

                if (o.pan.interactive) {
                    bind(eventHolder, "pointerdown", onPointerDown);
                }

                bind(eventHolder, "dblclick", onDblClick);
                bind(eventHolder, "click", onClick);
            }

            plot.zoomOut = function(args) {
                if (!args) {
                    args = {};
                }

                if (!args.amount) {
                    args.amount = plot.getOptions().zoom.amount;
                }

                args.amount = 1 / args.amount;
                plot.zoom(args);
            };

            plot.zoom = function(args) {
                if (!args) {
                    args = {};
                }

                var c = args.center,
                    amount = args.amount || plot.getOptions().zoom.amount,
                    w = plot.width(),
                    h = plot.height(),
                    axes = args.axes || plot.getAxes();

                if (!c) {
                    c = {
                        left: w / 2,
                        top: h / 2
                    };
                }

                var xf = c.left / w,
                    yf = c.top / h,
                    minmax = {
                        x: {
                            min: c.left - xf * w / amount,
                            max: c.left + (1 - xf) * w / amount
                        },
                        y: {
                            min: c.top - yf * h / amount,
                            max: c.top + (1 - yf) * h / amount
                        }
                    };

                for (var key in axes) {
                    if (!axes.hasOwnProperty(key)) {
                        continue;
                    }

                    var axis = axes[key],
                        opts = axis.options,
                        min = minmax[axis.direction].min,
                        max = minmax[axis.direction].max,
                        navigationOffset = axis.options.offset;

                    //skip axis without axisZoom when zooming only on certain axis or axis without plotZoom for zoom on entire plot
                    if ((!opts.axisZoom && args.axes) || (!args.axes && !opts.plotZoom)) {
                        continue;
                    }

                    min = saturated.saturate(axis.c2p(min));
                    max = saturated.saturate(axis.c2p(max));
                    if (min > max) {
                        // make sure min < max
                        var tmp = min;
                        min = max;
                        max = tmp;
                    }

                    // test for zoom limits zoomRange: [min,max]
                    if (opts.zoomRange) {
                        // zoomed in too far
                        if (max - min < opts.zoomRange[0]) {
                            continue;
                        }
                        // zoomed out to far
                        if (max - min > opts.zoomRange[1]) {
                            continue;
                        }
                    }

                    var offsetBelow = saturated.saturate(navigationOffset.below - (axis.min - min));
                    var offsetAbove = saturated.saturate(navigationOffset.above - (axis.max - max));
                    opts.offset = { below: offsetBelow, above: offsetAbove };
                }
                plot.setupGrid(true);
                plot.draw();

                if (!args.preventEvent) {
                    trigger(plot.getPlaceholder(), "plotzoom", [plot, args]);
                }
            };

            plot.pan = function(args) {
                var delta = {
                    x: +args.left,
                    y: +args.top
                };

                if (isNaN(delta.x)) delta.x = 0;
                if (isNaN(delta.y)) delta.y = 0;

                var panAxesOrAll = args.axes || plot.getAxes();
                Object.keys(panAxesOrAll).forEach(function(key) {
                    var axis = panAxesOrAll[key];
                    var opts = axis.options,
                        d = delta[axis.direction];

                    //skip axis without axisPan when panning only on certain axis or axis without plotPan for pan the entire plot
                    if ((!opts.axisPan && args.axes) || (!opts.plotPan && !args.axes)) {
                        return;
                    }

                    // calc min delta (revealing left edge of plot)
                    var minD = axis.p2c(opts.panRange[0]) - axis.p2c(axis.min);
                    // calc max delta (revealing right edge of plot)
                    var maxD = axis.p2c(opts.panRange[1]) - axis.p2c(axis.max);
                    // limit delta to min or max if enabled
                    if (opts.panRange[0] !== undefined && d >= maxD) d = maxD;
                    if (opts.panRange[1] !== undefined && d <= minD) d = minD;

                    if (d !== 0) {
                        var navigationOffsetBelow = saturated.saturate(axis.c2p(axis.p2c(axis.min) + d) - axis.c2p(axis.p2c(axis.min))),
                            navigationOffsetAbove = saturated.saturate(axis.c2p(axis.p2c(axis.max) + d) - axis.c2p(axis.p2c(axis.max)));

                        if (!isFinite(navigationOffsetBelow)) {
                            navigationOffsetBelow = 0;
                        }

                        if (!isFinite(navigationOffsetAbove)) {
                            navigationOffsetAbove = 0;
                        }

                        opts.offset = {
                            below: saturated.saturate(navigationOffsetBelow + (opts.offset.below || 0)),
                            above: saturated.saturate(navigationOffsetAbove + (opts.offset.above || 0))
                        };
                    }
                });

                plot.setupGrid(true);
                plot.draw();
                if (!args.preventEvent) {
                    trigger(plot.getPlaceholder(), "plotpan", [plot, args]);
                }
            };

            plot.recenter = function(args) {
                var recenterAxes = args.axes || plot.getAxes();
                Object.keys(recenterAxes).forEach(function(key) {
                    var axis = recenterAxes[key];
                    if (args.axes) {
                        if (axis.direction === 'x') {
                            axis.options.offset = { below: 0 };
                        } else if (axis.direction === 'y') {
                            axis.options.offset = { above: 0 };
                        }
                    } else {
                        axis.options.offset = { below: 0, above: 0 };
                    }
                });
                plot.setupGrid(true);
                plot.draw();
            };

            var shouldSnap = function(delta) {
                return (Math.abs(delta.y) < SNAPPING_CONSTANT && Math.abs(delta.x) >= SNAPPING_CONSTANT) ||
                    (Math.abs(delta.x) < SNAPPING_CONSTANT && Math.abs(delta.y) >= SNAPPING_CONSTANT);
            };

            // adjust delta so the pan action is constrained on the vertical or horizontal direction
            // it the movements in the other direction are small
            var adjustDeltaToSnap = function(delta) {
                if (Math.abs(delta.x) < SNAPPING_CONSTANT && Math.abs(delta.y) >= SNAPPING_CONSTANT) {
                    return {x: 0, y: delta.y};
                }

                if (Math.abs(delta.y) < SNAPPING_CONSTANT && Math.abs(delta.x) >= SNAPPING_CONSTANT) {
                    return {x: delta.x, y: 0};
                }

                return delta;
            };

            var lockedDirection = null;
            var lockDeltaDirection = function(delta) {
                if (!lockedDirection && Math.max(Math.abs(delta.x), Math.abs(delta.y)) >= SNAPPING_CONSTANT) {
                    lockedDirection = Math.abs(delta.x) < Math.abs(delta.y) ? 'y' : 'x';
                }

                switch (lockedDirection) {
                    case 'x':
                        return { x: delta.x, y: 0 };
                    case 'y':
                        return { x: 0, y: delta.y };
                    default:
                        return { x: 0, y: 0 };
                }
            };

            var isDiagonalMode = function(delta) {
                if (Math.abs(delta.x) > 0 && Math.abs(delta.y) > 0) {
                    return true;
                }
                return false;
            };

            var restoreAxisOffset = function(axes, initialState, delta) {
                var axis;
                Object.keys(axes).forEach(function(axisName) {
                    axis = axes[axisName];
                    if (delta[axis.direction] === 0) {
                        axis.options.offset.below = initialState[axisName].navigationOffset.below;
                        axis.options.offset.above = initialState[axisName].navigationOffset.above;
                    }
                });
            };

            var prevDelta = { x: 0, y: 0 };
            plot.smartPan = function(delta, initialState, panAxes, preventEvent, smartLock) {
                var snap = smartLock ? true : shouldSnap(delta),
                    axes = plot.getAxes(),
                    opts;
                delta = smartLock ? lockDeltaDirection(delta) : adjustDeltaToSnap(delta);

                if (isDiagonalMode(delta)) {
                    initialState.diagMode = true;
                }

                if (snap && initialState.diagMode === true) {
                    initialState.diagMode = false;
                    restoreAxisOffset(axes, initialState, delta);
                }

                if (snap) {
                    panHint = {
                        start: {
                            x: initialState.startPageX - plot.offset().left + plot.getPlotOffset().left,
                            y: initialState.startPageY - plot.offset().top + plot.getPlotOffset().top
                        },
                        end: {
                            x: initialState.startPageX - delta.x - plot.offset().left + plot.getPlotOffset().left,
                            y: initialState.startPageY - delta.y - plot.offset().top + plot.getPlotOffset().top
                        }
                    };
                } else {
                    panHint = {
                        start: {
                            x: initialState.startPageX - plot.offset().left + plot.getPlotOffset().left,
                            y: initialState.startPageY - plot.offset().top + plot.getPlotOffset().top
                        },
                        end: false
                    };
                }

                if (isNaN(delta.x)) delta.x = 0;
                if (isNaN(delta.y)) delta.y = 0;

                if (panAxes) {
                    axes = panAxes;
                }

                var axis, axisMin, axisMax, p, d;
                Object.keys(axes).forEach(function(axisName) {
                    axis = axes[axisName];
                    axisMin = axis.min;
                    axisMax = axis.max;
                    opts = axis.options;

                    d = delta[axis.direction];
                    p = prevDelta[axis.direction];

                    //skip axis without axisPan when panning only on certain axis or axis without plotPan for pan the entire plot
                    if ((!opts.axisPan && panAxes) || (!panAxes && !opts.plotPan)) {
                        return;
                    }

                    // calc min delta (revealing left edge of plot)
                    var minD = p + axis.p2c(opts.panRange[0]) - axis.p2c(axisMin);
                    // calc max delta (revealing right edge of plot)
                    var maxD = p + axis.p2c(opts.panRange[1]) - axis.p2c(axisMax);
                    // limit delta to min or max if enabled
                    if (opts.panRange[0] !== undefined && d >= maxD) d = maxD;
                    if (opts.panRange[1] !== undefined && d <= minD) d = minD;

                    if (d !== 0) {
                        var navigationOffsetBelow = saturated.saturate(axis.c2p(axis.p2c(axisMin) - (p - d)) - axis.c2p(axis.p2c(axisMin))),
                            navigationOffsetAbove = saturated.saturate(axis.c2p(axis.p2c(axisMax) - (p - d)) - axis.c2p(axis.p2c(axisMax)));

                        if (!isFinite(navigationOffsetBelow)) {
                            navigationOffsetBelow = 0;
                        }

                        if (!isFinite(navigationOffsetAbove)) {
                            navigationOffsetAbove = 0;
                        }

                        axis.options.offset.below = saturated.saturate(navigationOffsetBelow + (axis.options.offset.below || 0));
                        axis.options.offset.above = saturated.saturate(navigationOffsetAbove + (axis.options.offset.above || 0));
                    }
                });

                prevDelta = delta;
                plot.setupGrid(true);
                plot.draw();

                if (!preventEvent) {
                    trigger(plot.getPlaceholder(), "plotpan", [plot, delta, panAxes, initialState]);
                }
            };

            plot.smartPan.end = function() {
                panHint = null;
                lockedDirection = null;
                prevDelta = { x: 0, y: 0 };
                plot.triggerRedrawOverlay();
            };

            function shutdown(plot, eventHolder) {
                unbind(eventHolder, "wheel", onMouseWheel);
                unbind(eventHolder, "pointerdown", onPointerDown);
                unbind(eventHolder, "dblclick", onDblClick);
                unbind(eventHolder, "click", onClick);

                if (panTimeout) window.clearTimeout(panTimeout);
            }

            function drawOverlay(plot, ctx) {
                if (panHint) {
                    ctx.strokeStyle = 'rgba(96, 160, 208, 0.7)';
                    ctx.lineWidth = 2;
                    ctx.lineJoin = "round";
                    var startx = Math.round(panHint.start.x),
                        starty = Math.round(panHint.start.y),
                        endx, endy;

                    if (panAxes) {
                        if (panAxes[0].direction === 'x') {
                            endy = Math.round(panHint.start.y);
                            endx = Math.round(panHint.end.x);
                        } else if (panAxes[0].direction === 'y') {
                            endx = Math.round(panHint.start.x);
                            endy = Math.round(panHint.end.y);
                        }
                    } else {
                        endx = Math.round(panHint.end.x);
                        endy = Math.round(panHint.end.y);
                    }

                    ctx.beginPath();

                    if (panHint.end === false) {
                        ctx.moveTo(startx, starty - PANHINT_LENGTH_CONSTANT);
                        ctx.lineTo(startx, starty + PANHINT_LENGTH_CONSTANT);

                        ctx.moveTo(startx + PANHINT_LENGTH_CONSTANT, starty);
                        ctx.lineTo(startx - PANHINT_LENGTH_CONSTANT, starty);
                    } else {
                        var dirX = starty === endy;

                        ctx.moveTo(startx - (dirX ? 0 : PANHINT_LENGTH_CONSTANT), starty - (dirX ? PANHINT_LENGTH_CONSTANT : 0));
                        ctx.lineTo(startx + (dirX ? 0 : PANHINT_LENGTH_CONSTANT), starty + (dirX ? PANHINT_LENGTH_CONSTANT : 0));

                        ctx.moveTo(startx, starty);
                        ctx.lineTo(endx, endy);

                        ctx.moveTo(endx - (dirX ? 0 : PANHINT_LENGTH_CONSTANT), endy - (dirX ? PANHINT_LENGTH_CONSTANT : 0));
                        ctx.lineTo(endx + (dirX ? 0 : PANHINT_LENGTH_CONSTANT), endy + (dirX ? PANHINT_LENGTH_CONSTANT : 0));
                    }

                    ctx.stroke();
                }
            }

            plot.getTouchedAxis = function(touchPointX, touchPointY) {
                var placeholderRect = plot.getPlaceholder().getBoundingClientRect();
                var ec = { left: placeholderRect.left + window.scrollX, top: placeholderRect.top + window.scrollY };
                ec.left = touchPointX - ec.left;
                ec.top = touchPointY - ec.top;

                var axis = plot.getXAxes().concat(plot.getYAxes()).filter(function (axis) {
                    var box = axis.box;
                    if (box !== undefined) {
                        return (ec.left > box.left) && (ec.left < box.left + box.width) &&
                                (ec.top > box.top) && (ec.top < box.top + box.height);
                    }
                });

                return axis;
            };

            plot.hooks.drawOverlay.push(drawOverlay);
            plot.hooks.bindEvents.push(bindEvents);
            plot.hooks.shutdown.push(shutdown);
        }

        plugins.push({
            init: init$b,
            options: options$8,
            name: 'navigate',
            version: '1.3'
        });

    /* Flot plugin for computing bottoms for filled line and bar charts.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    The case: you've got two series that you want to fill the area between. In Flot
    terms, you need to use one as the fill bottom of the other. You can specify the
    bottom of each data point as the third coordinate manually, or you can use this
    plugin to compute it for you.

    In order to name the other series, you need to give it an id, like this:

        var dataset = [
            { data: [ ... ], id: "foo" } ,         // use default bottom
            { data: [ ... ], fillBetween: "foo" }, // use first dataset as bottom
        ];

        $.plot($("#placeholder"), dataset, { lines: { show: true, fill: true }});

    As a convenience, if the id given is a number that doesn't appear as an id in
    the series, it is interpreted as the index in the array instead (so fillBetween:
    0 can also mean the first series).

    Internally, the plugin modifies the datapoints in each series. For line series,
    extra data points might be inserted through interpolation. Note that at points
    where the bottom line is not defined (due to a null point or start/end of line),
    the current line will show a gap too. The algorithm comes from the
    jquery.flot.stack.js plugin, possibly some code could be shared.

    */


        var options$7 = {
            series: {
                fillBetween: null // or number
            }
        };

        function init$a(plot) {
            function findBottomSeries(s, allseries) {
                var i;

                for (i = 0; i < allseries.length; ++i) {
                    if (allseries[ i ].id === s.fillBetween) {
                        return allseries[ i ];
                    }
                }

                if (typeof s.fillBetween === "number") {
                    if (s.fillBetween < 0 || s.fillBetween >= allseries.length) {
                        return null;
                    }
                    return allseries[ s.fillBetween ];
                }

                return null;
            }

            function computeFormat(plot, s, data, datapoints) {
                if (s.fillBetween == null) {
                    return;
                }

                var format = datapoints.format;
                var plotHasId = function(id) {
                    var plotData = plot.getData();
                    for (var i = 0; i < plotData.length; i++) {
                        if (plotData[i].id === id) {
                            return true;
                        }
                    }

                    return false;
                };

                if (!format) {
                    format = [];

                    format.push({
                        x: true,
                        number: true,
                        computeRange: s.xaxis.options.autoScale !== 'none',
                        required: true
                    });
                    format.push({
                        y: true,
                        number: true,
                        computeRange: s.yaxis.options.autoScale !== 'none',
                        required: true
                    });

                    if (s.fillBetween !== undefined && s.fillBetween !== '' && plotHasId(s.fillBetween) && s.fillBetween !== s.id) {
                        format.push({
                            x: false,
                            y: true,
                            number: true,
                            required: false,
                            computeRange: s.yaxis.options.autoScale !== 'none',
                            defaultValue: 0
                        });
                    }

                    datapoints.format = format;
                }
            }

            function computeFillBottoms(plot, s, datapoints) {
                if (s.fillBetween == null) {
                    return;
                }

                var other = findBottomSeries(s, plot.getData());

                if (!other) {
                    return;
                }

                var ps = datapoints.pointsize,
                    points = datapoints.points,
                    otherps = other.datapoints.pointsize,
                    otherpoints = other.datapoints.points,
                    newpoints = [],
                    px, py, intery, qx, qy, bottom,
                    withlines = s.lines.show,
                    withbottom = ps > 2 && datapoints.format[2].y,
                    withsteps = withlines && s.lines.steps,
                    fromgap = true,
                    i = 0,
                    j = 0,
                    l, m;

                while (true) {
                    if (i >= points.length) {
                        break;
                    }

                    l = newpoints.length;

                    if (points[ i ] == null) {
                        // copy gaps
                        for (m = 0; m < ps; ++m) {
                            newpoints.push(points[ i + m ]);
                        }

                        i += ps;
                    } else if (j >= otherpoints.length) {
                        // for lines, we can't use the rest of the points
                        if (!withlines) {
                            for (m = 0; m < ps; ++m) {
                                newpoints.push(points[ i + m ]);
                            }
                        }

                        i += ps;
                    } else if (otherpoints[ j ] == null) {
                        // oops, got a gap
                        for (m = 0; m < ps; ++m) {
                            newpoints.push(null);
                        }

                        fromgap = true;
                        j += otherps;
                    } else {
                        // cases where we actually got two points
                        px = points[ i ];
                        py = points[ i + 1 ];
                        qx = otherpoints[ j ];
                        qy = otherpoints[ j + 1 ];
                        bottom = 0;

                        if (px === qx) {
                            for (m = 0; m < ps; ++m) {
                                newpoints.push(points[ i + m ]);
                            }

                            //newpoints[ l + 1 ] += qy;
                            bottom = qy;

                            i += ps;
                            j += otherps;
                        } else if (px > qx) {
                            // we got past point below, might need to
                            // insert interpolated extra point

                            if (withlines && i > 0 && points[ i - ps ] != null) {
                                intery = py + (points[ i - ps + 1 ] - py) * (qx - px) / (points[ i - ps ] - px);
                                newpoints.push(qx);
                                newpoints.push(intery);
                                for (m = 2; m < ps; ++m) {
                                    newpoints.push(points[ i + m ]);
                                }
                                bottom = qy;
                            }

                            j += otherps;
                        } else {
                            // px < qx
                            // if we come from a gap, we just skip this point

                            if (fromgap && withlines) {
                                i += ps;
                                continue;
                            }

                            for (m = 0; m < ps; ++m) {
                                newpoints.push(points[ i + m ]);
                            }

                            // we might be able to interpolate a point below,
                            // this can give us a better y

                            if (withlines && j > 0 && otherpoints[ j - otherps ] != null) {
                                bottom = qy + (otherpoints[ j - otherps + 1 ] - qy) * (px - qx) / (otherpoints[ j - otherps ] - qx);
                            }

                            //newpoints[l + 1] += bottom;

                            i += ps;
                        }

                        fromgap = false;

                        if (l !== newpoints.length && withbottom) {
                            newpoints[ l + 2 ] = bottom;
                        }
                    }

                    // maintain the line steps invariant

                    if (withsteps && l !== newpoints.length && l > 0 &&
                        newpoints[ l ] !== null &&
                        newpoints[ l ] !== newpoints[ l - ps ] &&
                        newpoints[ l + 1 ] !== newpoints[ l - ps + 1 ]) {
                        for (m = 0; m < ps; ++m) {
                            newpoints[ l + ps + m ] = newpoints[ l + m ];
                        }
                        newpoints[ l + 1 ] = newpoints[ l - ps + 1 ];
                    }
                }

                datapoints.points = newpoints;
            }

            plot.hooks.processRawData.push(computeFormat);
            plot.hooks.processDatapoints.push(computeFillBottoms);
        }

        plugins.push({
            init: init$a,
            options: options$7,
            name: "fillbetween",
            version: "1.0"
        });

    /* Flot plugin for plotting textual data or categories.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    Consider a dataset like [["February", 34], ["March", 20], ...]. This plugin
    allows you to plot such a dataset directly.

    To enable it, you must specify mode: "categories" on the axis with the textual
    labels, e.g.

        $.plot("#placeholder", data, { xaxis: { mode: "categories" } });

    By default, the labels are ordered as they are met in the data series. If you
    need a different ordering, you can specify "categories" on the axis options
    and list the categories there:

        xaxis: {
            mode: "categories",
            categories: ["February", "March", "April"]
        }

    If you need to customize the distances between the categories, you can specify
    "categories" as an object mapping labels to values

        xaxis: {
            mode: "categories",
            categories: { "February": 1, "March": 3, "April": 4 }
        }

    If you don't specify all categories, the remaining categories will be numbered
    from the max value plus 1 (with a spacing of 1 between each).

    Internally, the plugin works by transforming the input data through an auto-
    generated mapping where the first category becomes 0, the second 1, etc.
    Hence, a point like ["February", 34] becomes [0, 34] internally in Flot (this
    is visible in hover and click events that return numbers rather than the
    category labels). The plugin also overrides the tick generator to spit out the
    categories as ticks instead of the values.

    If you need to map a value back to its label, the mapping is always accessible
    as "categories" on the axis object, e.g. plot.getAxes().xaxis.categories.

    */


        var options$6 = {
            xaxis: {
                categories: null
            },
            yaxis: {
                categories: null
            }
        };

        function processRawData(plot, series, data, datapoints) {
            // if categories are enabled, we need to disable
            // auto-transformation to numbers so the strings are intact
            // for later processing

            var xCategories = series.xaxis.options.mode === "categories",
                yCategories = series.yaxis.options.mode === "categories";

            if (!(xCategories || yCategories)) {
                return;
            }

            var format = datapoints.format;

            if (!format) {
                // FIXME: auto-detection should really not be defined here
                var s = series;
                format = [];
                format.push({ x: true, number: true, required: true, computeRange: true});
                format.push({ y: true, number: true, required: true, computeRange: true });

                if (s.bars.show || (s.lines.show && s.lines.fill)) {
                    var autoScale = !!((s.bars.show && s.bars.zero) || (s.lines.show && s.lines.zero));
                    format.push({ y: true, number: true, required: false, defaultValue: 0, computeRange: autoScale });
                    if (s.bars.horizontal) {
                        delete format[format.length - 1].y;
                        format[format.length - 1].x = true;
                    }
                }

                datapoints.format = format;
            }

            for (var m = 0; m < format.length; ++m) {
                if (format[m].x && xCategories) {
                    format[m].number = false;
                }

                if (format[m].y && yCategories) {
                    format[m].number = false;
                    format[m].computeRange = false;
                }
            }
        }

        function getNextIndex(categories) {
            var index = -1;

            for (var v in categories) {
                if (categories[v] > index) {
                    index = categories[v];
                }
            }

            return index + 1;
        }

        function categoriesTickGenerator(axis) {
            var res = [];
            for (var label in axis.categories) {
                var v = axis.categories[label];
                if (v >= axis.min && v <= axis.max) {
                    res.push([v, label]);
                }
            }

            res.sort(function (a, b) { return a[0] - b[0]; });

            return res;
        }

        function setupCategoriesForAxis(series, axis, datapoints) {
            if (series[axis].options.mode !== "categories") {
                return;
            }

            if (!series[axis].categories) {
                // parse options
                var c = {}, o = series[axis].options.categories || {};
                if (Array.isArray(o)) {
                    for (var i = 0; i < o.length; ++i) {
                        c[o[i]] = i;
                    }
                } else {
                    for (var v in o) {
                        c[v] = o[v];
                    }
                }

                series[axis].categories = c;
            }

            // fix ticks
            if (!series[axis].options.ticks) {
                series[axis].options.ticks = categoriesTickGenerator;
            }

            transformPointsOnAxis(datapoints, axis, series[axis].categories);
        }

        function transformPointsOnAxis(datapoints, axis, categories) {
            // go through the points, transforming them
            var points = datapoints.points,
                ps = datapoints.pointsize,
                format = datapoints.format,
                formatColumn = axis.charAt(0),
                index = getNextIndex(categories);

            for (var i = 0; i < points.length; i += ps) {
                if (points[i] == null) {
                    continue;
                }

                for (var m = 0; m < ps; ++m) {
                    var val = points[i + m];

                    if (val == null || !format[m][formatColumn]) {
                        continue;
                    }

                    if (!(val in categories)) {
                        categories[val] = index;
                        ++index;
                    }

                    points[i + m] = categories[val];
                }
            }
        }

        function processDatapoints(plot, series, datapoints) {
            setupCategoriesForAxis(series, "xaxis", datapoints);
            setupCategoriesForAxis(series, "yaxis", datapoints);
        }

        function init$9(plot) {
            plot.hooks.processRawData.push(processRawData);
            plot.hooks.processDatapoints.push(processDatapoints);
        }

        plugins.push({
            init: init$9,
            options: options$6,
            name: 'categories',
            version: '1.0'
        });

    /* Flot plugin for stacking data sets rather than overlaying them.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    The plugin assumes the data is sorted on x (or y if stacking horizontally).
    For line charts, it is assumed that if a line has an undefined gap (from a
    null point), then the line above it should have the same gap - insert zeros
    instead of "null" if you want another behaviour. This also holds for the start
    and end of the chart. Note that stacking a mix of positive and negative values
    in most instances doesn't make sense (so it looks weird).

    Two or more series are stacked when their "stack" attribute is set to the same
    key (which can be any number or string or just "true"). To specify the default
    stack, you can set the stack option like this:

        series: {
            stack: null/false, true, or a key (number/string)
        }

    You can also specify it for a single series, like this:

        $.plot( $("#placeholder"), [{
            data: [ ... ],
            stack: true
        }])

    The stacking order is determined by the order of the data series in the array
    (later series end up on top of the previous).

    Internally, the plugin modifies the datapoints in each series, adding an
    offset to the y value. For line series, extra data points are inserted through
    interpolation. If there's a second y value, it's also adjusted (e.g for bar
    charts or filled areas).

    */


        var options$5 = {
            series: { stack: null } // or number/string
        };

        function init$8(plot) {
            function findMatchingSeries(s, allseries) {
                var res = null;
                for (var i = 0; i < allseries.length; ++i) {
                    if (s === allseries[i]) break;

                    if (allseries[i].stack === s.stack) {
                        res = allseries[i];
                    }
                }

                return res;
            }

            function addBottomPoints (s, datapoints) {
                var formattedPoints = [];
                for (var i = 0; i < datapoints.points.length; i += 2) {
                    formattedPoints.push(datapoints.points[i]);
                    formattedPoints.push(datapoints.points[i + 1]);
                    formattedPoints.push(0);
                }

                datapoints.format.push({
                    x: s.bars.horizontal,
                    y: !s.bars.horizontal,
                    number: true,
                    required: false,
                    computeRange: s.yaxis.options.autoScale !== 'none',
                    defaultValue: 0
                });
                datapoints.points = formattedPoints;
                datapoints.pointsize = 3;
            }

            function stackData(plot, s, datapoints) {
                if (s.stack == null || s.stack === false) return;

                var needsBottom = s.bars.show || (s.lines.show && s.lines.fill);
                var hasBottom = datapoints.pointsize > 2 && (s.bars.horizontal ? datapoints.format[2].x : datapoints.format[2].y);
                // Series data is missing bottom points - need to format
                if (needsBottom && !hasBottom) {
                    addBottomPoints(s, datapoints);
                }

                var other = findMatchingSeries(s, plot.getData());
                if (!other) return;

                var ps = datapoints.pointsize,
                    points = datapoints.points,
                    otherps = other.datapoints.pointsize,
                    otherpoints = other.datapoints.points,
                    newpoints = [],
                    px, py, intery, qx, qy, bottom,
                    withlines = s.lines.show,
                    horizontal = s.bars.horizontal,
                    withsteps = withlines && s.lines.steps,
                    fromgap = true,
                    keyOffset = horizontal ? 1 : 0,
                    accumulateOffset = horizontal ? 0 : 1,
                    i = 0, j = 0, l, m;

                while (true) {
                    if (i >= points.length) break;

                    l = newpoints.length;

                    if (points[i] == null) {
                        // copy gaps
                        for (m = 0; m < ps; ++m) {
                            newpoints.push(points[i + m]);
                        }

                        i += ps;
                    } else if (j >= otherpoints.length) {
                        // for lines, we can't use the rest of the points
                        if (!withlines) {
                            for (m = 0; m < ps; ++m) {
                                newpoints.push(points[i + m]);
                            }
                        }

                        i += ps;
                    } else if (otherpoints[j] == null) {
                        // oops, got a gap
                        for (m = 0; m < ps; ++m) {
                            newpoints.push(null);
                        }

                        fromgap = true;
                        j += otherps;
                    } else {
                        // cases where we actually got two points
                        px = points[i + keyOffset];
                        py = points[i + accumulateOffset];
                        qx = otherpoints[j + keyOffset];
                        qy = otherpoints[j + accumulateOffset];
                        bottom = 0;

                        if (px === qx) {
                            for (m = 0; m < ps; ++m) {
                                newpoints.push(points[i + m]);
                            }

                            newpoints[l + accumulateOffset] += qy;
                            bottom = qy;

                            i += ps;
                            j += otherps;
                        } else if (px > qx) {
                            // we got past point below, might need to
                            // insert interpolated extra point
                            if (withlines && i > 0 && points[i - ps] != null) {
                                intery = py + (points[i - ps + accumulateOffset] - py) * (qx - px) / (points[i - ps + keyOffset] - px);
                                newpoints.push(qx);
                                newpoints.push(intery + qy);
                                for (m = 2; m < ps; ++m) {
                                    newpoints.push(points[i + m]);
                                }

                                bottom = qy;
                            }

                            j += otherps;
                        } else { // px < qx
                            if (fromgap && withlines) {
                                // if we come from a gap, we just skip this point
                                i += ps;
                                continue;
                            }

                            for (m = 0; m < ps; ++m) {
                                newpoints.push(points[i + m]);
                            }

                            // we might be able to interpolate a point below,
                            // this can give us a better y
                            if (withlines && j > 0 && otherpoints[j - otherps] != null) {
                                bottom = qy + (otherpoints[j - otherps + accumulateOffset] - qy) * (px - qx) / (otherpoints[j - otherps + keyOffset] - qx);
                            }

                            newpoints[l + accumulateOffset] += bottom;

                            i += ps;
                        }

                        fromgap = false;

                        if (l !== newpoints.length && needsBottom) {
                            newpoints[l + 2] += bottom;
                        }
                    }

                    // maintain the line steps invariant
                    if (withsteps && l !== newpoints.length && l > 0 &&
                        newpoints[l] !== null &&
                        newpoints[l] !== newpoints[l - ps] &&
                        newpoints[l + 1] !== newpoints[l - ps + 1]) {
                        for (m = 0; m < ps; ++m) {
                            newpoints[l + ps + m] = newpoints[l + m];
                        }

                        newpoints[l + 1] = newpoints[l - ps + 1];
                    }
                }

                datapoints.points = newpoints;
            }

            plot.hooks.processDatapoints.push(stackData);
        }

        plugins.push({
            init: init$8,
            options: options$5,
            name: 'stack',
            version: '1.2'
        });

    var options$4 = {
            zoom: {
                enableTouch: false
            },
            pan: {
                enableTouch: false,
                touchMode: 'manual'
            },
            recenter: {
                enableTouch: true
            }
        };

        var ZOOM_DISTANCE_MARGIN = uiConstants.ZOOM_DISTANCE_MARGIN;

        function init$7(plot) {
            plot.hooks.processOptions.push(initTouchNavigation$1);
        }

        function initTouchNavigation$1(plot, options) {
            var gestureState = {
                    zoomEnable: false,
                    prevDistance: null,
                    prevTapTime: 0,
                    prevPanPosition: { x: 0, y: 0 },
                    prevTapPosition: { x: 0, y: 0 }
                },
                navigationState = {
                    prevTouchedAxis: 'none',
                    currentTouchedAxis: 'none',
                    touchedAxis: null,
                    navigationConstraint: 'unconstrained',
                    initialState: null
                },
                useManualPan = options.pan.interactive && options.pan.touchMode === 'manual',
                smartPanLock = options.pan.touchMode === 'smartLock',
                useSmartPan = options.pan.interactive && (smartPanLock || options.pan.touchMode === 'smart'),
                pan, pinch, doubleTap;

            function bindEvents(plot, eventHolder) {
                var o = plot.getOptions();

                if (o.zoom.interactive && o.zoom.enableTouch) {
                    eventHolder.addEventListener('pinchstart', pinch.start, false);
                    eventHolder.addEventListener('pinchdrag', pinch.drag, false);
                    eventHolder.addEventListener('pinchend', pinch.end, false);
                }

                if (o.pan.interactive && o.pan.enableTouch) {
                    eventHolder.addEventListener('panstart', pan.start, false);
                    eventHolder.addEventListener('pandrag', pan.drag, false);
                    eventHolder.addEventListener('panend', pan.end, false);
                }

                if ((o.recenter.interactive && o.recenter.enableTouch)) {
                    eventHolder.addEventListener('doubletap', doubleTap.recenterPlot, false);
                }
            }

            function shutdown(plot, eventHolder) {
                eventHolder.removeEventListener('panstart', pan.start);
                eventHolder.removeEventListener('pandrag', pan.drag);
                eventHolder.removeEventListener('panend', pan.end);
                eventHolder.removeEventListener('pinchstart', pinch.start);
                eventHolder.removeEventListener('pinchdrag', pinch.drag);
                eventHolder.removeEventListener('pinchend', pinch.end);
                eventHolder.removeEventListener('doubletap', doubleTap.recenterPlot);
            }

            pan = {
                start: function(e) {
                    presetNavigationState(e, 'pan');
                    updateData(e, 'pan', gestureState, navigationState);

                    if (useSmartPan) {
                        var point = getPoint(e, 'pan');
                        navigationState.initialState = plot.navigationState(point.x, point.y);
                    }
                },

                drag: function(e) {
                    presetNavigationState(e, 'pan');

                    if (useSmartPan) {
                        var point = getPoint(e, 'pan');
                        plot.smartPan({
                            x: navigationState.initialState.startPageX - point.x,
                            y: navigationState.initialState.startPageY - point.y
                        }, navigationState.initialState, navigationState.touchedAxis, false, smartPanLock);
                    } else if (useManualPan) {
                        plot.pan({
                            left: -delta(e, 'pan', gestureState).x,
                            top: -delta(e, 'pan', gestureState).y,
                            axes: navigationState.touchedAxis
                        });
                        updatePrevPanPosition(e, 'pan', gestureState, navigationState);
                    }
                },

                end: function(e) {
                    presetNavigationState(e, 'pan');

                    if (useSmartPan) {
                        plot.smartPan.end();
                    }

                    if (wasPinchEvent(e, gestureState)) {
                        updateprevPanPosition(e, 'pan', gestureState, navigationState);
                    }
                }
            };

            var pinchDragTimeout;
            pinch = {
                start: function(e) {
                    if (pinchDragTimeout) {
                        clearTimeout(pinchDragTimeout);
                        pinchDragTimeout = null;
                    }
                    presetNavigationState(e, 'pinch');
                    setPrevDistance(e, gestureState);
                    updateData(e, 'pinch', gestureState, navigationState);
                },

                drag: function(e) {
                    if (pinchDragTimeout) {
                        return;
                    }
                    pinchDragTimeout = setTimeout(function() {
                        presetNavigationState(e, 'pinch');
                        plot.pan({
                            left: -delta(e, 'pinch', gestureState).x,
                            top: -delta(e, 'pinch', gestureState).y,
                            axes: navigationState.touchedAxis
                        });
                        updatePrevPanPosition(e, 'pinch', gestureState, navigationState);

                        var dist = pinchDistance(e);

                        if (gestureState.zoomEnable || Math.abs(dist - gestureState.prevDistance) > ZOOM_DISTANCE_MARGIN) {
                            zoomPlot(plot, e, gestureState, navigationState);

                            //activate zoom mode
                            gestureState.zoomEnable = true;
                        }
                        pinchDragTimeout = null;
                    }, 1000 / 60);
                },

                end: function(e) {
                    if (pinchDragTimeout) {
                        clearTimeout(pinchDragTimeout);
                        pinchDragTimeout = null;
                    }
                    presetNavigationState(e, 'pinch');
                    gestureState.prevDistance = null;
                }
            };

            doubleTap = {
                recenterPlot: function(e) {
                    if (e && e.detail && e.detail.type === 'touchstart') {
                        // only do not recenter for touch start;
                        recenterPlotOnDoubleTap(plot, e, gestureState, navigationState);
                    }
                }
            };

            if (options.pan.enableTouch === true || options.zoom.enableTouch === true) {
                plot.hooks.bindEvents.push(bindEvents);
                plot.hooks.shutdown.push(shutdown);
            }

            function presetNavigationState(e, gesture, gestureState) {
                navigationState.touchedAxis = getAxis(plot, e, gesture, navigationState);
                if (noAxisTouched(navigationState)) {
                    navigationState.navigationConstraint = 'unconstrained';
                } else {
                    navigationState.navigationConstraint = 'axisConstrained';
                }
            }
        }

        plugins.push({
            init: init$7,
            options: options$4,
            name: 'navigateTouch',
            version: '0.3'
        });

        function recenterPlotOnDoubleTap(plot, e, gestureState, navigationState) {
            checkAxesForDoubleTap(plot, e, navigationState);
            if ((navigationState.currentTouchedAxis === 'x' && navigationState.prevTouchedAxis === 'x') ||
                (navigationState.currentTouchedAxis === 'y' && navigationState.prevTouchedAxis === 'y') ||
                (navigationState.currentTouchedAxis === 'none' && navigationState.prevTouchedAxis === 'none')) {
                plot.recenter({ axes: navigationState.touchedAxis });

                if (navigationState.touchedAxis) {
                    trigger(plot.getPlaceholder(), 're-center', { axisTouched: navigationState.touchedAxis });
                } else {
                    trigger(plot.getPlaceholder(), 're-center', e);
                }
            }
        }

        function checkAxesForDoubleTap(plot, e, navigationState) {
            var axis = plot.getTouchedAxis(e.detail.firstTouch.x, e.detail.firstTouch.y);
            if (axis[0] !== undefined) {
                navigationState.prevTouchedAxis = axis[0].direction;
            }

            axis = plot.getTouchedAxis(e.detail.secondTouch.x, e.detail.secondTouch.y);
            if (axis[0] !== undefined) {
                navigationState.touchedAxis = axis;
                navigationState.currentTouchedAxis = axis[0].direction;
            }

            if (noAxisTouched(navigationState)) {
                navigationState.touchedAxis = null;
                navigationState.prevTouchedAxis = 'none';
                navigationState.currentTouchedAxis = 'none';
            }
        }

        function zoomPlot(plot, e, gestureState, navigationState) {
            var offset = plot.offset(),
                center = {
                    left: 0,
                    top: 0
                },
                zoomAmount = pinchDistance(e) / gestureState.prevDistance,
                dist = pinchDistance(e);

            center.left = getPoint(e, 'pinch').x - offset.left;
            center.top = getPoint(e, 'pinch').y - offset.top;

            // send the computed touched axis to the zoom function so that it only zooms on that one
            plot.zoom({
                center: center,
                amount: zoomAmount,
                axes: navigationState.touchedAxis
            });
            gestureState.prevDistance = dist;
        }

        function wasPinchEvent(e, gestureState) {
            return (gestureState.zoomEnable && e.detail.touches.length === 1);
        }

        function getAxis(plot, e, gesture, navigationState) {
            if (e.type === 'pinchstart') {
                var axisTouch1 = plot.getTouchedAxis(e.detail.touches[0].pageX, e.detail.touches[0].pageY);
                var axisTouch2 = plot.getTouchedAxis(e.detail.touches[1].pageX, e.detail.touches[1].pageY);

                if (axisTouch1.length === axisTouch2.length && axisTouch1.toString() === axisTouch2.toString()) {
                    return axisTouch1;
                }
            } else if (e.type === 'panstart') {
                return plot.getTouchedAxis(e.detail.touches[0].pageX, e.detail.touches[0].pageY);
            } else if (e.type === 'pinchend') {
                //update axis since instead on pinch, a pan event is made
                return plot.getTouchedAxis(e.detail.touches[0].pageX, e.detail.touches[0].pageY);
            } else {
                return navigationState.touchedAxis;
            }
        }

        function noAxisTouched(navigationState) {
            return (!navigationState.touchedAxis || navigationState.touchedAxis.length === 0);
        }

        function setPrevDistance(e, gestureState) {
            gestureState.prevDistance = pinchDistance(e);
        }

        function updateData(e, gesture, gestureState, navigationState) {
            var axisDir,
                point = getPoint(e, gesture);

            switch (navigationState.navigationConstraint) {
                case 'unconstrained':
                    navigationState.touchedAxis = null;
                    gestureState.prevTapPosition = {
                        x: gestureState.prevPanPosition.x,
                        y: gestureState.prevPanPosition.y
                    };
                    gestureState.prevPanPosition = {
                        x: point.x,
                        y: point.y
                    };
                    break;
                case 'axisConstrained':
                    axisDir = navigationState.touchedAxis[0].direction;
                    navigationState.currentTouchedAxis = axisDir;
                    gestureState.prevTapPosition[axisDir] = gestureState.prevPanPosition[axisDir];
                    gestureState.prevPanPosition[axisDir] = point[axisDir];
                    break;
            }
        }

        function distance(x1, y1, x2, y2) {
            return Math.sqrt((x1 - x2) * (x1 - x2) + (y1 - y2) * (y1 - y2));
        }

        function pinchDistance(e) {
            var t1 = e.detail.touches[0],
                t2 = e.detail.touches[1];
            return distance(t1.pageX, t1.pageY, t2.pageX, t2.pageY);
        }

        function updatePrevPanPosition(e, gesture, gestureState, navigationState) {
            var point = getPoint(e, gesture);

            switch (navigationState.navigationConstraint) {
                case 'unconstrained':
                    gestureState.prevPanPosition.x = point.x;
                    gestureState.prevPanPosition.y = point.y;
                    break;
                case 'axisConstrained':
                    gestureState.prevPanPosition[navigationState.currentTouchedAxis] =
                    point[navigationState.currentTouchedAxis];
                    break;
            }
        }

        function delta(e, gesture, gestureState) {
            var point = getPoint(e, gesture);

            return {
                x: point.x - gestureState.prevPanPosition.x,
                y: point.y - gestureState.prevPanPosition.y
            }
        }

        function getPoint(e, gesture) {
            if (gesture === 'pinch') {
                return {
                    x: (e.detail.touches[0].pageX + e.detail.touches[1].pageX) / 2,
                    y: (e.detail.touches[0].pageY + e.detail.touches[1].pageY) / 2
                }
            } else {
                return {
                    x: e.detail.touches[0].pageX,
                    y: e.detail.touches[0].pageY
                }
            }
        }

    /* global jQuery */


        var options$3 = {
            grid: {
                hoverable: false,
                clickable: false
            }
        };

        var eventType = {
            click: 'click',
            hover: 'hover'
        };

        function init$6(plot) {
            var lastMouseMoveEvent;
            var highlights = [];

            function bindEvents(plot, eventHolder) {
                var o = plot.getOptions();

                if (o.grid.hoverable || o.grid.clickable) {
                    eventHolder.addEventListener('touchevent', triggerCleanupEvent, false);
                    eventHolder.addEventListener('tap', generatePlothoverEvent, false);
                }

                if (o.grid.clickable) {
                    bind(eventHolder, "click", onClick);
                }

                if (o.grid.hoverable) {
                    bind(eventHolder, "mousemove", onMouseMove);
                    bind(eventHolder, "mouseleave", onMouseLeave);
                }
            }

            function shutdown(plot, eventHolder) {
                eventHolder.removeEventListener('tap', generatePlothoverEvent);
                eventHolder.removeEventListener('touchevent', triggerCleanupEvent);
                unbind(eventHolder, "mousemove", onMouseMove);
                unbind(eventHolder, "mouseleave", onMouseLeave);
                unbind(eventHolder, "click", onClick);
                highlights = [];
            }

            function generatePlothoverEvent(e) {
                var o = plot.getOptions(),
                    newEvent = new CustomEvent('mouseevent');

                //transform from touch event to mouse event format
                newEvent.pageX = e.detail.changedTouches[0].pageX;
                newEvent.pageY = e.detail.changedTouches[0].pageY;
                newEvent.clientX = e.detail.changedTouches[0].clientX;
                newEvent.clientY = e.detail.changedTouches[0].clientY;

                if (o.grid.hoverable) {
                    doTriggerClickHoverEvent(newEvent, eventType.hover, 30);
                }
                return false;
            }

            function doTriggerClickHoverEvent(event, eventType, searchDistance) {
                var series = plot.getData();
                if (event !== undefined &&
                    series.length > 0 &&
                    series[0].xaxis.c2p !== undefined &&
                    series[0].yaxis.c2p !== undefined) {
                    var eventToTrigger = "plot" + eventType;
                    var seriesFlag = eventType + "able";
                    triggerClickHoverEvent(eventToTrigger, event,
                        function(i) {
                            return series[i][seriesFlag] !== false;
                        }, searchDistance);
                }
            }

            function onMouseMove(e) {
                lastMouseMoveEvent = e;
                plot.getPlaceholder().lastMouseMoveEvent = e;
                doTriggerClickHoverEvent(e, eventType.hover);
            }

            function onMouseLeave(e) {
                lastMouseMoveEvent = undefined;
                plot.getPlaceholder().lastMouseMoveEvent = undefined;
                triggerClickHoverEvent("plothover", e,
                    function(i) {
                        return false;
                    });
            }

            function onClick(e) {
                doTriggerClickHoverEvent(e, eventType.click);
            }

            function triggerCleanupEvent() {
                plot.unhighlight();
                trigger(plot.getPlaceholder(), 'plothovercleanup');
            }

            // trigger click or hover event (they send the same parameters
            // so we share their code)
            function triggerClickHoverEvent(eventname, event, seriesFilter, searchDistance) {
                var options = plot.getOptions(),
                    offset = plot.offset(),
                    page = browser.getPageXY(event),
                    canvasX = page.X - offset.left,
                    canvasY = page.Y - offset.top,
                    pos = plot.c2p({
                        left: canvasX,
                        top: canvasY
                    }),
                    distance = searchDistance !== undefined ? searchDistance : options.grid.mouseActiveRadius;

                pos.pageX = page.X;
                pos.pageY = page.Y;

                var items = plot.findNearbyItems(canvasX, canvasY, seriesFilter, distance);
                var item = items[0];

                for (let i = 1; i < items.length; ++i) {
                    if (item.distance === undefined ||
                        items[i].distance < item.distance) {
                        item = items[i];
                    }
                }

                if (item) {
                    // fill in mouse pos for any listeners out there
                    item.pageX = parseInt(item.series.xaxis.p2c(item.datapoint[0]) + offset.left, 10);
                    item.pageY = parseInt(item.series.yaxis.p2c(item.datapoint[1]) + offset.top, 10);
                } else {
                    item = null;
                }

                if (options.grid.autoHighlight) {
                    // clear auto-highlights
                    for (let i = 0; i < highlights.length; ++i) {
                        var h = highlights[i];
                        if ((h.auto === eventname &&
                            !(item && h.series === item.series &&
                                h.point[0] === item.datapoint[0] &&
                                h.point[1] === item.datapoint[1])) || !item) {
                            unhighlight(h.series, h.point);
                        }
                    }

                    if (item) {
                        highlight(item.series, item.datapoint, eventname);
                    }
                }

                trigger(plot.getPlaceholder(), eventname, [pos, item, items]);
            }

            function highlight(s, point, auto) {
                if (typeof s === "number") {
                    s = plot.getData()[s];
                }

                if (typeof point === "number") {
                    var ps = s.datapoints.pointsize;
                    point = s.datapoints.points.slice(ps * point, ps * (point + 1));
                }

                var i = indexOfHighlight(s, point);
                if (i === -1) {
                    highlights.push({
                        series: s,
                        point: point,
                        auto: auto
                    });

                    plot.triggerRedrawOverlay();
                } else if (!auto) {
                    highlights[i].auto = false;
                }
            }

            function unhighlight(s, point) {
                if (s == null && point == null) {
                    highlights = [];
                    plot.triggerRedrawOverlay();
                    return;
                }

                if (typeof s === "number") {
                    s = plot.getData()[s];
                }

                if (typeof point === "number") {
                    var ps = s.datapoints.pointsize;
                    point = s.datapoints.points.slice(ps * point, ps * (point + 1));
                }

                var i = indexOfHighlight(s, point);
                if (i !== -1) {
                    highlights.splice(i, 1);

                    plot.triggerRedrawOverlay();
                }
            }

            function indexOfHighlight(s, p) {
                for (var i = 0; i < highlights.length; ++i) {
                    var h = highlights[i];
                    if (h.series === s &&
                        h.point[0] === p[0] &&
                        h.point[1] === p[1]) {
                        return i;
                    }
                }

                return -1;
            }

            function processDatapoints() {
                triggerCleanupEvent();
                doTriggerClickHoverEvent(lastMouseMoveEvent, eventType.hover);
            }

            function setupGrid() {
                doTriggerClickHoverEvent(lastMouseMoveEvent, eventType.hover);
            }

            function drawOverlay(plot, octx, overlay) {
                var plotOffset = plot.getPlotOffset(),
                    i, hi;

                octx.save();
                octx.translate(plotOffset.left, plotOffset.top);
                for (i = 0; i < highlights.length; ++i) {
                    hi = highlights[i];

                    if (hi.series.bars.show) drawBarHighlight(hi.series, hi.point, octx);
                    else drawPointHighlight(hi.series, hi.point, octx, plot);
                }
                octx.restore();
            }

            function drawPointHighlight(series, point, octx, plot) {
                var x = point[0],
                    y = point[1],
                    axisx = series.xaxis,
                    axisy = series.yaxis,
                    highlightColor = (typeof series.highlightColor === "string") ? series.highlightColor : color.parse(series.color).scale('a', 0.5).toString();

                if (x < axisx.min || x > axisx.max || y < axisy.min || y > axisy.max) {
                    return;
                }

                var pointRadius = series.points.radius + series.points.lineWidth / 2;
                octx.lineWidth = pointRadius;
                octx.strokeStyle = highlightColor;
                var radius = 1.5 * pointRadius;
                x = axisx.p2c(x);
                y = axisy.p2c(y);

                octx.beginPath();
                var symbol = series.points.symbol;
                if (symbol === 'circle') {
                    octx.arc(x, y, radius, 0, 2 * Math.PI, false);
                } else if (typeof symbol === 'string' && plot.drawSymbol && plot.drawSymbol[symbol]) {
                    plot.drawSymbol[symbol](octx, x, y, radius, false);
                }

                octx.closePath();
                octx.stroke();
            }

            function drawBarHighlight(series, point, octx) {
                var highlightColor = (typeof series.highlightColor === "string") ? series.highlightColor : color.parse(series.color).scale('a', 0.5).toString(),
                    fillStyle = highlightColor,
                    barLeft;

                var barWidth = series.bars.barWidth[0] || series.bars.barWidth;
                switch (series.bars.align) {
                    case "left":
                        barLeft = 0;
                        break;
                    case "right":
                        barLeft = -barWidth;
                        break;
                    default:
                        barLeft = -barWidth / 2;
                }

                octx.lineWidth = series.bars.lineWidth;
                octx.strokeStyle = highlightColor;

                var fillTowards = series.bars.fillTowards || 0,
                    bottom = fillTowards > series.yaxis.min ? Math.min(series.yaxis.max, fillTowards) : series.yaxis.min;

                drawSeries.drawBar(point[0], point[1], point[2] || bottom, barLeft, barLeft + barWidth,
                    function() {
                        return fillStyle;
                    }, series.xaxis, series.yaxis, octx, series.bars.horizontal, series.bars.lineWidth);
            }

            function initHover(plot, options) {
                plot.highlight = highlight;
                plot.unhighlight = unhighlight;
                if (options.grid.hoverable || options.grid.clickable) {
                    plot.hooks.drawOverlay.push(drawOverlay);
                    plot.hooks.processDatapoints.push(processDatapoints);
                    plot.hooks.setupGrid.push(setupGrid);
                }

                lastMouseMoveEvent = plot.getPlaceholder().lastMouseMoveEvent;
            }

            plot.hooks.bindEvents.push(bindEvents);
            plot.hooks.shutdown.push(shutdown);
            plot.hooks.processOptions.push(initHover);
        }

        plugins.push({
            init: init$6,
            options: options$3,
            name: 'hover',
            version: '0.1'
        });

    var options$2 = {
            propagateSupportedGesture: false
        };

        function init$5(plot) {
            plot.hooks.processOptions.push(initTouchNavigation);
        }

        function initTouchNavigation(plot, options) {
            var gestureState = {
                    twoTouches: false,
                    currentTapStart: { x: 0, y: 0 },
                    currentTapEnd: { x: 0, y: 0 },
                    prevTap: { x: 0, y: 0 },
                    currentTap: { x: 0, y: 0 },
                    interceptedLongTap: false,
                    isUnsupportedGesture: false,
                    prevTapTime: null,
                    tapStartTime: null,
                    longTapTriggerId: null
                },
                maxDistanceBetweenTaps = 20,
                maxIntervalBetweenTaps = 500,
                maxLongTapDistance = 20,
                minLongTapDuration = 1500,
                pressedTapDuration = 125,
                mainEventHolder;

            function interpretGestures(e) {
                var o = plot.getOptions();

                if (!o.pan.active && !o.zoom.active) {
                    return;
                }

                updateOnMultipleTouches(e);
                mainEventHolder.dispatchEvent(new CustomEvent('touchevent', { detail: e }));

                if (isPinchEvent(e)) {
                    executeAction(e, 'pinch');
                } else {
                    executeAction(e, 'pan');
                    if (!wasPinchEvent(e)) {
                        if (isDoubleTap(e)) {
                            executeAction(e, 'doubleTap');
                        }
                        executeAction(e, 'tap');
                        executeAction(e, 'longTap');
                    }
                }
            }

            function executeAction(e, gesture) {
                switch (gesture) {
                    case 'pan':
                        pan[e.type](e);
                        break;
                    case 'pinch':
                        pinch[e.type](e);
                        break;
                    case 'doubleTap':
                        doubleTap.onDoubleTap(e);
                        break;
                    case 'longTap':
                        longTap[e.type](e);
                        break;
                    case 'tap':
                        tap[e.type](e);
                        break;
                }
            }

            function bindEvents(plot, eventHolder) {
                mainEventHolder = eventHolder;
                eventHolder.addEventListener('touchstart', interpretGestures, false);
                eventHolder.addEventListener('touchmove', interpretGestures, false);
                eventHolder.addEventListener('touchend', interpretGestures, false);
            }

            function shutdown(plot, eventHolder) {
                eventHolder.removeEventListener('touchstart', interpretGestures);
                eventHolder.removeEventListener('touchmove', interpretGestures);
                eventHolder.removeEventListener('touchend', interpretGestures);
                if (gestureState.longTapTriggerId) {
                    clearTimeout(gestureState.longTapTriggerId);
                    gestureState.longTapTriggerId = null;
                }
            }

            var pan = {
                touchstart: function(e) {
                    updatePrevForDoubleTap();
                    updateCurrentForDoubleTap(e);
                    updateStateForLongTapStart(e);

                    mainEventHolder.dispatchEvent(new CustomEvent('panstart', { detail: e }));
                },

                touchmove: function(e) {
                    preventEventBehaviors(e);

                    updateCurrentForDoubleTap(e);
                    updateStateForLongTapEnd(e);

                    if (!gestureState.isUnsupportedGesture) {
                        mainEventHolder.dispatchEvent(new CustomEvent('pandrag', { detail: e }));
                    }
                },

                touchend: function(e) {
                    preventEventBehaviors(e);

                    if (wasPinchEvent(e)) {
                        mainEventHolder.dispatchEvent(new CustomEvent('pinchend', { detail: e }));
                        mainEventHolder.dispatchEvent(new CustomEvent('panstart', { detail: e }));
                    } else if (noTouchActive(e)) {
                        mainEventHolder.dispatchEvent(new CustomEvent('panend', { detail: e }));
                    }
                }
            };

            var pinch = {
                touchstart: function(e) {
                    mainEventHolder.dispatchEvent(new CustomEvent('pinchstart', { detail: e }));
                },

                touchmove: function(e) {
                    preventEventBehaviors(e);
                    gestureState.twoTouches = isPinchEvent(e);
                    if (!gestureState.isUnsupportedGesture) {
                        mainEventHolder.dispatchEvent(new CustomEvent('pinchdrag', { detail: e }));
                    }
                },

                touchend: function(e) {
                    preventEventBehaviors(e);
                }
            };

            var doubleTap = {
                onDoubleTap: function(e) {
                    preventEventBehaviors(e);
                    mainEventHolder.dispatchEvent(new CustomEvent('doubletap', { detail: e }));
                }
            };

            var longTap = {
                touchstart: function(e) {
                    longTap.waitForLongTap(e);
                },

                touchmove: function(e) {
                },

                touchend: function(e) {
                    if (gestureState.longTapTriggerId) {
                        clearTimeout(gestureState.longTapTriggerId);
                        gestureState.longTapTriggerId = null;
                    }
                },

                isLongTap: function(e) {
                    var currentTime = new Date().getTime(),
                        tapDuration = currentTime - gestureState.tapStartTime;
                    if (tapDuration >= minLongTapDuration && !gestureState.interceptedLongTap) {
                        if (distance(gestureState.currentTapStart.x, gestureState.currentTapStart.y, gestureState.currentTapEnd.x, gestureState.currentTapEnd.y) < maxLongTapDistance) {
                            gestureState.interceptedLongTap = true;
                            return true;
                        }
                    }
                    return false;
                },

                waitForLongTap: function(e) {
                    var longTapTrigger = function() {
                        if (longTap.isLongTap(e)) {
                            mainEventHolder.dispatchEvent(new CustomEvent('longtap', { detail: e }));
                        }
                        gestureState.longTapTriggerId = null;
                    };
                    if (!gestureState.longTapTriggerId) {
                        gestureState.longTapTriggerId = setTimeout(longTapTrigger, minLongTapDuration);
                    }
                }
            };

            var tap = {
                touchstart: function(e) {
                    gestureState.tapStartTime = new Date().getTime();
                },

                touchmove: function(e) {
                },

                touchend: function(e) {
                    if (tap.isTap(e)) {
                        mainEventHolder.dispatchEvent(new CustomEvent('tap', { detail: e }));
                        preventEventBehaviors(e);
                    }
                },

                isTap: function(e) {
                    var currentTime = new Date().getTime(),
                        tapDuration = currentTime - gestureState.tapStartTime;
                    if (tapDuration <= pressedTapDuration) {
                        if (distance(gestureState.currentTapStart.x, gestureState.currentTapStart.y, gestureState.currentTapEnd.x, gestureState.currentTapEnd.y) < maxLongTapDistance) {
                            return true;
                        }
                    }
                    return false;
                }
            };

            if (options.pan.enableTouch === true || options.zoom.enableTouch) {
                plot.hooks.bindEvents.push(bindEvents);
                plot.hooks.shutdown.push(shutdown);
            }
            function updatePrevForDoubleTap() {
                gestureState.prevTap = {
                    x: gestureState.currentTap.x,
                    y: gestureState.currentTap.y
                };
            }
            function updateCurrentForDoubleTap(e) {
                gestureState.currentTap = {
                    x: e.touches[0].pageX,
                    y: e.touches[0].pageY
                };
            }

            function updateStateForLongTapStart(e) {
                gestureState.tapStartTime = new Date().getTime();
                gestureState.interceptedLongTap = false;
                gestureState.currentTapStart = {
                    x: e.touches[0].pageX,
                    y: e.touches[0].pageY
                };
                gestureState.currentTapEnd = {
                    x: e.touches[0].pageX,
                    y: e.touches[0].pageY
                };
            }
            function updateStateForLongTapEnd(e) {
                gestureState.currentTapEnd = {
                    x: e.touches[0].pageX,
                    y: e.touches[0].pageY
                };
            }
            function isDoubleTap(e) {
                var currentTime = new Date().getTime(),
                    intervalBetweenTaps = currentTime - gestureState.prevTapTime;

                if (intervalBetweenTaps >= 0 && intervalBetweenTaps < maxIntervalBetweenTaps) {
                    if (distance(gestureState.prevTap.x, gestureState.prevTap.y, gestureState.currentTap.x, gestureState.currentTap.y) < maxDistanceBetweenTaps) {
                        e.firstTouch = gestureState.prevTap;
                        e.secondTouch = gestureState.currentTap;
                        return true;
                    }
                }
                gestureState.prevTapTime = currentTime;
                return false;
            }

            function preventEventBehaviors(e) {
                if (!gestureState.isUnsupportedGesture) {
                    e.preventDefault();
                    if (!plot.getOptions().propagateSupportedGesture) {
                        e.stopPropagation();
                    }
                }
            }

            function distance(x1, y1, x2, y2) {
                return Math.sqrt((x1 - x2) * (x1 - x2) + (y1 - y2) * (y1 - y2));
            }

            function noTouchActive(e) {
                return (e.touches && e.touches.length === 0);
            }

            function wasPinchEvent(e) {
                return (gestureState.twoTouches && e.touches.length === 1);
            }

            function updateOnMultipleTouches(e) {
                if (e.touches.length >= 3) {
                    gestureState.isUnsupportedGesture = true;
                } else {
                    gestureState.isUnsupportedGesture = false;
                }
            }

            function isPinchEvent(e) {
                if (e.touches && e.touches.length >= 2) {
                    if (e.touches[0].target === plot.getEventHolder() &&
                        e.touches[1].target === plot.getEventHolder()) {
                        return true;
                    }
                }
                return false;
            }
        }

        plugins.push({
            init: init$5,
            options: options$2,
            name: 'navigateTouch',
            version: '0.3'
        });

    /* Pretty handling of time axes.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    Set axis.mode to "time" to enable. See the section "Time series data" in
    API.txt for details.
    */


        var options$1 = {
            xaxis: {
                timezone: null, // "browser" for local to the client or timezone for timezone-js
                timeformat: null, // format string to use
                twelveHourClock: false, // 12 or 24 time in time mode
                monthNames: null, // list of names of months
                timeBase: 'seconds' // are the values in given in mircoseconds, milliseconds or seconds
            },
            yaxis: {
                timeBase: 'seconds'
            }
        };

        var floorInBase = saturated.floorInBase;

        // Method to provide microsecond support to Date like classes.
        var CreateMicroSecondDate = function(DateType, microEpoch) {
            var newDate = new DateType(microEpoch);

            var oldSetTime = newDate.setTime.bind(newDate);
            newDate.update = function(microEpoch) {
                // Round epoch to 3 decimal accuracy
                microEpoch = Math.round(microEpoch * 1000) / 1000;

                oldSetTime(microEpoch);

                // Microseconds are stored as integers
                this.microseconds = 1000 * (microEpoch - Math.floor(microEpoch));
            };

            var oldGetTime = newDate.getTime.bind(newDate);
            newDate.getTime = function () {
                var microEpoch = oldGetTime() + this.microseconds / 1000;
                return microEpoch;
            };

            newDate.setTime = function (microEpoch) {
                this.update(microEpoch);
            };

            newDate.getMicroseconds = function() {
                return this.microseconds;
            };

            newDate.setMicroseconds = function(microseconds) {
                var epochWithoutMicroseconds = oldGetTime();
                var newEpoch = epochWithoutMicroseconds + microseconds / 1000;
                this.update(newEpoch);
            };

            newDate.setUTCMicroseconds = function(microseconds) { this.setMicroseconds(microseconds); };

            newDate.getUTCMicroseconds = function() { return this.getMicroseconds(); };

            newDate.microseconds = null;
            newDate.microEpoch = null;
            newDate.update(microEpoch);
            return newDate;
        };

        // Returns a string with the date d formatted according to fmt.
        // A subset of the Open Group's strftime format is supported.

        function formatDate(d, fmt, monthNames, dayNames) {
            if (typeof d.strftime === "function") {
                return d.strftime(fmt);
            }

            var leftPad = function(n, pad) {
                n = "" + n;
                pad = "" + (pad == null ? "0" : pad);
                return n.length === 1 ? pad + n : n;
            };

            var formatSubSeconds = function(milliseconds, microseconds, numberDecimalPlaces) {
                var totalMicroseconds = milliseconds * 1000 + microseconds;
                var formattedString;
                if (numberDecimalPlaces < 6 && numberDecimalPlaces > 0) {
                    var magnitude = parseFloat('1e' + (numberDecimalPlaces - 6));
                    totalMicroseconds = Math.round(Math.round(totalMicroseconds * magnitude) / magnitude);
                    formattedString = ('00000' + totalMicroseconds).slice(-6, -(6 - numberDecimalPlaces));
                } else {
                    totalMicroseconds = Math.round(totalMicroseconds);
                    formattedString = ('00000' + totalMicroseconds).slice(-6);
                }
                return formattedString;
            };

            var r = [];
            var escape = false;
            var hours = d.getHours();
            var isAM = hours < 12;

            if (!monthNames) {
                monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            }

            if (!dayNames) {
                dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            }

            var hours12;
            if (hours > 12) {
                hours12 = hours - 12;
            } else if (hours === 0) {
                hours12 = 12;
            } else {
                hours12 = hours;
            }

            var decimals = -1;
            for (var i = 0; i < fmt.length; ++i) {
                var c = fmt.charAt(i);

                if (!isNaN(Number(c)) && Number(c) > 0) {
                    decimals = Number(c);
                } else if (escape) {
                    switch (c) {
                        case 'a': c = "" + dayNames[d.getDay()]; break;
                        case 'b': c = "" + monthNames[d.getMonth()]; break;
                        case 'd': c = leftPad(d.getDate()); break;
                        case 'e': c = leftPad(d.getDate(), " "); break;
                        case 'h': // For back-compat with 0.7; remove in 1.0
                        case 'H': c = leftPad(hours); break;
                        case 'I': c = leftPad(hours12); break;
                        case 'l': c = leftPad(hours12, " "); break;
                        case 'm': c = leftPad(d.getMonth() + 1); break;
                        case 'M': c = leftPad(d.getMinutes()); break;
                        // quarters not in Open Group's strftime specification
                        case 'q':
                            c = "" + (Math.floor(d.getMonth() / 3) + 1); break;
                        case 'S': c = leftPad(d.getSeconds()); break;
                        case 's': c = "" + formatSubSeconds(d.getMilliseconds(), d.getMicroseconds(), decimals); break;
                        case 'y': c = leftPad(d.getFullYear() % 100); break;
                        case 'Y': c = "" + d.getFullYear(); break;
                        case 'p': c = (isAM) ? ("" + "am") : ("" + "pm"); break;
                        case 'P': c = (isAM) ? ("" + "AM") : ("" + "PM"); break;
                        case 'w': c = "" + d.getDay(); break;
                    }
                    r.push(c);
                    escape = false;
                } else {
                    if (c === "%") {
                        escape = true;
                    } else {
                        r.push(c);
                    }
                }
            }

            return r.join("");
        }

        // To have a consistent view of time-based data independent of which time
        // zone the client happens to be in we need a date-like object independent
        // of time zones.  This is done through a wrapper that only calls the UTC
        // versions of the accessor methods.

        function makeUtcWrapper(d) {
            function addProxyMethod(sourceObj, sourceMethod, targetObj, targetMethod) {
                sourceObj[sourceMethod] = function() {
                    return targetObj[targetMethod].apply(targetObj, arguments);
                };
            }

            var utc = {
                date: d
            };

            // support strftime, if found
            if (d.strftime !== undefined) {
                addProxyMethod(utc, "strftime", d, "strftime");
            }

            addProxyMethod(utc, "getTime", d, "getTime");
            addProxyMethod(utc, "setTime", d, "setTime");

            var props = ["Date", "Day", "FullYear", "Hours", "Minutes", "Month", "Seconds", "Milliseconds", "Microseconds"];

            for (var p = 0; p < props.length; p++) {
                addProxyMethod(utc, "get" + props[p], d, "getUTC" + props[p]);
                addProxyMethod(utc, "set" + props[p], d, "setUTC" + props[p]);
            }

            return utc;
        }

        // select time zone strategy.  This returns a date-like object tied to the
        // desired timezone
        function dateGenerator(ts, opts) {
            var maxDateValue = 8640000000000000;

            if (opts && opts.timeBase === 'seconds') {
                ts *= 1000;
            } else if (opts.timeBase === 'microseconds') {
                ts /= 1000;
            }

            if (ts > maxDateValue) {
                ts = maxDateValue;
            } else if (ts < -maxDateValue) {
                ts = -maxDateValue;
            }

            if (opts.timezone === "browser") {
                return CreateMicroSecondDate(Date, ts);
            } else if (!opts.timezone || opts.timezone === "utc") {
                return makeUtcWrapper(CreateMicroSecondDate(Date, ts));
            } else if (typeof timezoneJS !== "undefined" && typeof timezoneJS.Date !== "undefined") {
                var d = CreateMicroSecondDate(timezoneJS.Date, ts);
                // timezone-js is fickle, so be sure to set the time zone before
                // setting the time.
                d.setTimezone(opts.timezone);
                d.setTime(ts);
                return d;
            } else {
                return makeUtcWrapper(CreateMicroSecondDate(Date, ts));
            }
        }

        // map of app. size of time units in seconds
        var timeUnitSizeSeconds = {
            "microsecond": 0.000001,
            "millisecond": 0.001,
            "second": 1,
            "minute": 60,
            "hour": 60 * 60,
            "day": 24 * 60 * 60,
            "month": 30 * 24 * 60 * 60,
            "quarter": 3 * 30 * 24 * 60 * 60,
            "year": 365.2425 * 24 * 60 * 60
        };

        // map of app. size of time units in milliseconds
        var timeUnitSizeMilliseconds = {
            "microsecond": 0.001,
            "millisecond": 1,
            "second": 1000,
            "minute": 60 * 1000,
            "hour": 60 * 60 * 1000,
            "day": 24 * 60 * 60 * 1000,
            "month": 30 * 24 * 60 * 60 * 1000,
            "quarter": 3 * 30 * 24 * 60 * 60 * 1000,
            "year": 365.2425 * 24 * 60 * 60 * 1000
        };

        // map of app. size of time units in microseconds
        var timeUnitSizeMicroseconds = {
            "microsecond": 1,
            "millisecond": 1000,
            "second": 1000000,
            "minute": 60 * 1000000,
            "hour": 60 * 60 * 1000000,
            "day": 24 * 60 * 60 * 1000000,
            "month": 30 * 24 * 60 * 60 * 1000000,
            "quarter": 3 * 30 * 24 * 60 * 60 * 1000000,
            "year": 365.2425 * 24 * 60 * 60 * 1000000
        };

        // the allowed tick sizes, after 1 year we use
        // an integer algorithm

        var baseSpec = [
            [1, "microsecond"], [2, "microsecond"], [5, "microsecond"], [10, "microsecond"],
            [25, "microsecond"], [50, "microsecond"], [100, "microsecond"], [250, "microsecond"], [500, "microsecond"],
            [1, "millisecond"], [2, "millisecond"], [5, "millisecond"], [10, "millisecond"],
            [25, "millisecond"], [50, "millisecond"], [100, "millisecond"], [250, "millisecond"], [500, "millisecond"],
            [1, "second"], [2, "second"], [5, "second"], [10, "second"],
            [30, "second"],
            [1, "minute"], [2, "minute"], [5, "minute"], [10, "minute"],
            [30, "minute"],
            [1, "hour"], [2, "hour"], [4, "hour"],
            [8, "hour"], [12, "hour"],
            [1, "day"], [2, "day"], [3, "day"],
            [0.25, "month"], [0.5, "month"], [1, "month"],
            [2, "month"]
        ];

        // we don't know which variant(s) we'll need yet, but generating both is
        // cheap

        var specMonths = baseSpec.concat([[3, "month"], [6, "month"],
            [1, "year"]]);
        var specQuarters = baseSpec.concat([[1, "quarter"], [2, "quarter"],
            [1, "year"]]);

        function dateTickGenerator(axis) {
            var opts = axis.options,
                ticks = [],
                d = dateGenerator(axis.min, opts),
                minSize = 0;

            // make quarter use a possibility if quarters are
            // mentioned in either of these options
            var spec = (opts.tickSize && opts.tickSize[1] ===
                "quarter") ||
                (opts.minTickSize && opts.minTickSize[1] ===
                "quarter") ? specQuarters : specMonths;

            var timeUnitSize;
            if (opts.timeBase === 'seconds') {
                timeUnitSize = timeUnitSizeSeconds;
            } else if (opts.timeBase === 'microseconds') {
                timeUnitSize = timeUnitSizeMicroseconds;
            } else {
                timeUnitSize = timeUnitSizeMilliseconds;
            }

            if (opts.minTickSize !== null && opts.minTickSize !== undefined) {
                if (typeof opts.tickSize === "number") {
                    minSize = opts.tickSize;
                } else {
                    minSize = opts.minTickSize[0] * timeUnitSize[opts.minTickSize[1]];
                }
            }

            for (var i = 0; i < spec.length - 1; ++i) {
                if (axis.delta < (spec[i][0] * timeUnitSize[spec[i][1]] +
                    spec[i + 1][0] * timeUnitSize[spec[i + 1][1]]) / 2 &&
                    spec[i][0] * timeUnitSize[spec[i][1]] >= minSize) {
                    break;
                }
            }

            var size = spec[i][0];
            var unit = spec[i][1];
            // special-case the possibility of several years
            if (unit === "year") {
                // if given a minTickSize in years, just use it,
                // ensuring that it's an integer

                if (opts.minTickSize !== null && opts.minTickSize !== undefined && opts.minTickSize[1] === "year") {
                    size = Math.floor(opts.minTickSize[0]);
                } else {
                    var magn = parseFloat('1e' + Math.floor(Math.log(axis.delta / timeUnitSize.year) / Math.LN10));
                    var norm = (axis.delta / timeUnitSize.year) / magn;

                    if (norm < 1.5) {
                        size = 1;
                    } else if (norm < 3) {
                        size = 2;
                    } else if (norm < 7.5) {
                        size = 5;
                    } else {
                        size = 10;
                    }

                    size *= magn;
                }

                // minimum size for years is 1

                if (size < 1) {
                    size = 1;
                }
            }

            axis.tickSize = opts.tickSize || [size, unit];
            var tickSize = axis.tickSize[0];
            unit = axis.tickSize[1];

            var step = tickSize * timeUnitSize[unit];

            if (unit === "microsecond") {
                d.setMicroseconds(floorInBase(d.getMicroseconds(), tickSize));
            } else if (unit === "millisecond") {
                d.setMilliseconds(floorInBase(d.getMilliseconds(), tickSize));
            } else if (unit === "second") {
                d.setSeconds(floorInBase(d.getSeconds(), tickSize));
            } else if (unit === "minute") {
                d.setMinutes(floorInBase(d.getMinutes(), tickSize));
            } else if (unit === "hour") {
                d.setHours(floorInBase(d.getHours(), tickSize));
            } else if (unit === "month") {
                d.setMonth(floorInBase(d.getMonth(), tickSize));
            } else if (unit === "quarter") {
                d.setMonth(3 * floorInBase(d.getMonth() / 3,
                    tickSize));
            } else if (unit === "year") {
                d.setFullYear(floorInBase(d.getFullYear(), tickSize));
            }

            // reset smaller components

            if (step >= timeUnitSize.millisecond) {
                d.setMicroseconds(0);
            }
            if (step >= timeUnitSize.second) {
                d.setMilliseconds(0);
            }
            if (step >= timeUnitSize.minute) {
                d.setSeconds(0);
            }
            if (step >= timeUnitSize.hour) {
                d.setMinutes(0);
            }
            if (step >= timeUnitSize.day) {
                d.setHours(0);
            }
            if (step >= timeUnitSize.day * 4) {
                d.setDate(1);
            }
            if (step >= timeUnitSize.month * 2) {
                d.setMonth(floorInBase(d.getMonth(), 3));
            }
            if (step >= timeUnitSize.quarter * 2) {
                d.setMonth(floorInBase(d.getMonth(), 6));
            }
            if (step >= timeUnitSize.year) {
                d.setMonth(0);
            }

            var carry = 0;
            var v = Number.NaN;
            var v1000;
            var prev;
            do {
                prev = v;
                v1000 = d.getTime();
                if (opts && opts.timeBase === 'seconds') {
                    v = v1000 / 1000;
                } else if (opts && opts.timeBase === 'microseconds') {
                    v = v1000 * 1000;
                } else {
                    v = v1000;
                }

                ticks.push(v);

                if (unit === "month" || unit === "quarter") {
                    if (tickSize < 1) {
                        // a bit complicated - we'll divide the
                        // month/quarter up but we need to take
                        // care of fractions so we don't end up in
                        // the middle of a day
                        d.setDate(1);
                        var start = d.getTime();
                        d.setMonth(d.getMonth() +
                            (unit === "quarter" ? 3 : 1));
                        var end = d.getTime();
                        d.setTime((v + carry * timeUnitSize.hour + (end - start) * tickSize));
                        carry = d.getHours();
                        d.setHours(0);
                    } else {
                        d.setMonth(d.getMonth() +
                            tickSize * (unit === "quarter" ? 3 : 1));
                    }
                } else if (unit === "year") {
                    d.setFullYear(d.getFullYear() + tickSize);
                } else {
                    if (opts.timeBase === 'seconds') {
                        d.setTime((v + step) * 1000);
                    } else if (opts.timeBase === 'microseconds') {
                        d.setTime((v + step) / 1000);
                    } else {
                        d.setTime(v + step);
                    }
                }
            } while (v < axis.max && v !== prev);

            return ticks;
        }
        function init$4(plot) {
            plot.hooks.processOptions.push(function (plot) {
                var axes = plot.getAxes();
                Object.keys(axes).forEach(function(axisName) {
                    var axis = axes[axisName];
                    var opts = axis.options;
                    if (opts.mode === "time") {
                        axis.tickGenerator = dateTickGenerator;

                        // if a tick formatter is already provided do not overwrite it
                        if ('tickFormatter' in opts && typeof opts.tickFormatter === 'function') return;

                        axis.tickFormatter = function (v, axis) {
                            var d = dateGenerator(v, axis.options);

                            // first check global format
                            if (opts.timeformat != null) {
                                return formatDate(d, opts.timeformat, opts.monthNames, opts.dayNames);
                            }

                            // possibly use quarters if quarters are mentioned in
                            // any of these places
                            var useQuarters = (axis.options.tickSize &&
                                    axis.options.tickSize[1] === "quarter") ||
                                (axis.options.minTickSize &&
                                    axis.options.minTickSize[1] === "quarter");

                            var timeUnitSize;
                            if (opts.timeBase === 'seconds') {
                                timeUnitSize = timeUnitSizeSeconds;
                            } else if (opts.timeBase === 'microseconds') {
                                timeUnitSize = timeUnitSizeMicroseconds;
                            } else {
                                timeUnitSize = timeUnitSizeMilliseconds;
                            }

                            var t = axis.tickSize[0] * timeUnitSize[axis.tickSize[1]];
                            var span = axis.max - axis.min;
                            var suffix = (opts.twelveHourClock) ? " %p" : "";
                            var hourCode = (opts.twelveHourClock) ? "%I" : "%H";
                            var factor;
                            var fmt;

                            if (opts.timeBase === 'seconds') {
                                factor = 1;
                            } else if (opts.timeBase === 'microseconds') {
                                factor = 1000000;
                            } else {
                                factor = 1000;
                            }

                            if (t < timeUnitSize.second) {
                                var decimals = -Math.floor(Math.log10(t / factor));

                                // the two-and-halves require an additional decimal
                                if (String(t).indexOf('25') > -1) {
                                    decimals++;
                                }

                                fmt = "%S.%" + decimals + "s";
                            } else
                            if (t < timeUnitSize.minute) {
                                fmt = hourCode + ":%M:%S" + suffix;
                            } else if (t < timeUnitSize.day) {
                                if (span < 2 * timeUnitSize.day) {
                                    fmt = hourCode + ":%M" + suffix;
                                } else {
                                    fmt = "%b %d " + hourCode + ":%M" + suffix;
                                }
                            } else if (t < timeUnitSize.month) {
                                fmt = "%b %d";
                            } else if ((useQuarters && t < timeUnitSize.quarter) ||
                                (!useQuarters && t < timeUnitSize.year)) {
                                if (span < timeUnitSize.year) {
                                    fmt = "%b";
                                } else {
                                    fmt = "%b %Y";
                                }
                            } else if (useQuarters && t < timeUnitSize.year) {
                                if (span < timeUnitSize.year) {
                                    fmt = "Q%q";
                                } else {
                                    fmt = "Q%q %Y";
                                }
                            } else {
                                fmt = "%Y";
                            }

                            var rt = formatDate(d, fmt, opts.monthNames, opts.dayNames);

                            return rt;
                        };
                    }
                });
            });
        }

        plugins.push({
            init: init$4,
            options: options$1,
            name: 'time',
            version: '1.0'
        });

        // Time-axis support used to be in Flot core, which exposed the
        // formatDate function on the plot object.  The entry point
        // (index.js) wires these onto $.plot for backwards compatibility.

    /*
    Axis label plugin for flot

    Derived from:
    Axis Labels Plugin for flot.
    http://github.com/markrcote/flot-axislabels

    Original code is Copyright (c) 2010 Xuan Luo.
    Original code was released under the GPLv3 license by Xuan Luo, September 2010.
    Original code was rereleased under the MIT license by Xuan Luo, April 2012.

    Permission is hereby granted, free of charge, to any person obtaining
    a copy of this software and associated documentation files (the
    "Software"), to deal in the Software without restriction, including
    without limitation the rights to use, copy, modify, merge, publish,
    distribute, sublicense, and/or sell copies of the Software, and to
    permit persons to whom the Software is furnished to do so, subject to
    the following conditions:

    The above copyright notice and this permission notice shall be
    included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
    EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
    MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
    NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
    LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
    OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
    WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    */


        var options = {
            axisLabels: {
                show: true
            }
        };

        function AxisLabel(axisName, position, padding, placeholder, axisLabel, surface) {
            this.axisName = axisName;
            this.position = position;
            this.padding = padding;
            this.placeholder = placeholder;
            this.axisLabel = axisLabel;
            this.surface = surface;
            this.width = 0;
            this.height = 0;
            this.elem = null;
        }

        AxisLabel.prototype.calculateSize = function() {
            var axisId = this.axisName + 'Label',
                layerId = axisId + 'Layer',
                className = axisId + ' axisLabels';

            var info = this.surface.getTextInfo(layerId, this.axisLabel, className);
            this.labelWidth = info.width;
            this.labelHeight = info.height;

            if (this.position === 'left' || this.position === 'right') {
                this.width = this.labelHeight + this.padding;
                this.height = 0;
            } else {
                this.width = 0;
                this.height = this.labelHeight + this.padding;
            }
        };

        AxisLabel.prototype.transforms = function(degrees, x, y, svgLayer) {
            var transforms = [], translate, rotate;
            if (x !== 0 || y !== 0) {
                translate = svgLayer.createSVGTransform();
                translate.setTranslate(x, y);
                transforms.push(translate);
            }
            if (degrees !== 0) {
                rotate = svgLayer.createSVGTransform();
                var centerX = Math.round(this.labelWidth / 2),
                    centerY = 0;
                rotate.setRotate(degrees, centerX, centerY);
                transforms.push(rotate);
            }

            return transforms;
        };

        AxisLabel.prototype.calculateOffsets = function(box) {
            var offsets = {
                x: 0,
                y: 0,
                degrees: 0
            };
            if (this.position === 'bottom') {
                offsets.x = box.left + box.width / 2 - this.labelWidth / 2;
                offsets.y = box.top + box.height - this.labelHeight;
            } else if (this.position === 'top') {
                offsets.x = box.left + box.width / 2 - this.labelWidth / 2;
                offsets.y = box.top;
            } else if (this.position === 'left') {
                offsets.degrees = -90;
                offsets.x = box.left - this.labelWidth / 2;
                offsets.y = box.height / 2 + box.top;
            } else if (this.position === 'right') {
                offsets.degrees = 90;
                offsets.x = box.left + box.width - this.labelWidth / 2;
                offsets.y = box.height / 2 + box.top;
            }
            offsets.x = Math.round(offsets.x);
            offsets.y = Math.round(offsets.y);

            return offsets;
        };

        AxisLabel.prototype.cleanup = function() {
            var axisId = this.axisName + 'Label',
                layerId = axisId + 'Layer',
                className = axisId + ' axisLabels';
            this.surface.removeText(layerId, 0, 0, this.axisLabel, className);
        };

        AxisLabel.prototype.draw = function(box) {
            var axisId = this.axisName + 'Label',
                layerId = axisId + 'Layer',
                className = axisId + ' axisLabels',
                offsets = this.calculateOffsets(box),
                style = {
                    position: 'absolute',
                    bottom: '',
                    right: '',
                    display: 'inline-block',
                    'white-space': 'nowrap'
                };

            var layer = this.surface.getSVGLayer(layerId);
            var transforms = this.transforms(offsets.degrees, offsets.x, offsets.y, layer.parentNode);

            this.surface.addText(layerId, 0, 0, this.axisLabel, className, undefined, undefined, undefined, undefined, transforms);
            this.surface.render();
            Object.keys(style).forEach(function(key) {
                layer.style[key] = style[key];
            });
        };

        function init$3(plot) {
            plot.hooks.processOptions.push(function(plot, options) {
                if (!options.axisLabels.show) {
                    return;
                }

                var axisLabels = {};
                var defaultPadding = 2; // padding between axis and tick labels

                plot.hooks.axisReserveSpace.push(function(plot, axis) {
                    var opts = axis.options;
                    var axisName = axis.direction + axis.n;

                    axis.labelHeight += axis.boxPosition.centerY;
                    axis.labelWidth += axis.boxPosition.centerX;

                    if (!opts || !opts.axisLabel || !axis.show) {
                        return;
                    }

                    var padding = opts.axisLabelPadding === undefined
                        ? defaultPadding
                        : opts.axisLabelPadding;

                    var axisLabel = axisLabels[axisName];
                    if (!axisLabel) {
                        axisLabel = new AxisLabel(axisName,
                            opts.position, padding,
                            plot.getPlaceholder(), opts.axisLabel, plot.getSurface());
                        axisLabels[axisName] = axisLabel;
                    }

                    axisLabel.calculateSize();

                    // Incrementing the sizes of the tick labels.
                    axis.labelHeight += axisLabel.height;
                    axis.labelWidth += axisLabel.width;
                });

                // TODO - use the drawAxis hook
                plot.hooks.draw.push(function(plot, ctx) {
                    var axes = plot.getAxes();
                    Object.keys(axes).forEach(function(flotAxisName) {
                        var axis = axes[flotAxisName];
                        var opts = axis.options;
                        if (!opts || !opts.axisLabel || !axis.show) {
                            return;
                        }

                        var axisName = axis.direction + axis.n;
                        axisLabels[axisName].draw(axis.box);
                    });
                });

                plot.hooks.shutdown.push(function(plot, eventHolder) {
                    for (var axisName in axisLabels) {
                        axisLabels[axisName].cleanup();
                    }
                });
            });
        }
        plugins.push({
            init: init$3,
            options: options,
            name: 'axisLabels',
            version: '3.0'
        });

    /* Flot plugin for selecting regions of a plot.

    Copyright (c) 2007-2014 IOLA and Ole Laursen.
    Licensed under the MIT license.

    The plugin supports these options:

    selection: {
        mode: null or "x" or "y" or "xy" or "smart",
        color: color,
        shape: "round" or "miter" or "bevel",
        visualization: "fill" or "focus",
        displaySelectionDecorations: true or false,
        minSize: number of pixels
    }

    Selection support is enabled by setting the mode to one of "x", "y" or "xy".
    In "x" mode, the user will only be able to specify the x range, similarly for
    "y" mode. For "xy", the selection becomes a rectangle where both ranges can be
    specified. "color" is color of the selection (if you need to change the color
    later on, you can get to it with plot.getOptions().selection.color). "shape"
    is the shape of the corners of the selection.

    The way how the selection is visualized, can be changed by using the option
    "visualization". Flot currently supports two modes: "focus" and "fill". The
    option "focus" draws a colored bezel around the selected area while keeping
    the selected area clear. The option "fill" highlights (i.e., fills) the
    selected area with a colored highlight.

    There are optional selection decorations (handles) that are rendered with the
    "focus" visualization option. The selection decoration is rendered by default
    but can be turned off by setting displaySelectionDecorations to false.

    "minSize" is the minimum size a selection can be in pixels. This value can
    be customized to determine the smallest size a selection can be and still
    have the selection rectangle be displayed. When customizing this value, the
    fact that it refers to pixels, not axis units must be taken into account.
    Thus, for example, if there is a bar graph in time mode with BarWidth set to 1
    minute, setting "minSize" to 1 will not make the minimum selection size 1
    minute, but rather 1 pixel. Note also that setting "minSize" to 0 will prevent
    "plotunselected" events from being fired when the user clicks the mouse without
    dragging.

    When selection support is enabled, a "plotselected" event will be emitted on
    the DOM element you passed into the plot function. The event handler gets a
    parameter with the ranges selected on the axes, like this:

        placeholder.bind( "plotselected", function( event, ranges ) {
            alert("You selected " + ranges.xaxis.from + " to " + ranges.xaxis.to)
            // similar for yaxis - with multiple axes, the extra ones are in
            // x2axis, x3axis, ...
        });

    The "plotselected" event is only fired when the user has finished making the
    selection. A "plotselecting" event is fired during the process with the same
    parameters as the "plotselected" event, in case you want to know what's
    happening while it's happening,

    A "plotunselected" event with no arguments is emitted when the user clicks the
    mouse to remove the selection. As stated above, setting "minSize" to 0 will
    destroy this behavior.

    The plugin allso adds the following methods to the plot object:

    - setSelection( ranges, preventEvent )

      Set the selection rectangle. The passed in ranges is on the same form as
      returned in the "plotselected" event. If the selection mode is "x", you
      should put in either an xaxis range, if the mode is "y" you need to put in
      an yaxis range and both xaxis and yaxis if the selection mode is "xy", like
      this:

        setSelection({ xaxis: { from: 0, to: 10 }, yaxis: { from: 40, to: 60 } });

      setSelection will trigger the "plotselected" event when called. If you don't
      want that to happen, e.g. if you're inside a "plotselected" handler, pass
      true as the second parameter. If you are using multiple axes, you can
      specify the ranges on any of those, e.g. as x2axis/x3axis/... instead of
      xaxis, the plugin picks the first one it sees.

    - clearSelection( preventEvent )

      Clear the selection rectangle. Pass in true to avoid getting a
      "plotunselected" event.

    - getSelection()

      Returns the current selection in the same format as the "plotselected"
      event. If there's currently no selection, the function returns null.

    */


        function init$2(plot) {
            var selection = {
                first: {x: -1, y: -1},
                second: {x: -1, y: -1},
                show: false,
                currentMode: 'xy',
                active: false
            };

            var SNAPPING_CONSTANT = uiConstants.SNAPPING_CONSTANT;

            // FIXME: The drag handling implemented here should be
            // abstracted out, there's some similar code from a library in
            // the navigation plugin, this should be massaged a bit to fit
            // the Flot cases here better and reused. Doing this would
            // make this plugin much slimmer.
            var savedhandlers = {};

            function onDrag(e) {
                if (selection.active) {
                    updateSelection(e);

                    trigger(plot.getPlaceholder(), "plotselecting", [ getSelection() ]);
                }
            }

            function onDragStart(e) {
                var o = plot.getOptions();
                // only accept left-click
                if (e.button !== 0 || o.selection.mode === null) return;

                // reinitialize currentMode
                selection.currentMode = 'xy';

                // cancel out any text selections
                document.body.focus();

                // prevent text selection and drag in old-school browsers
                if (document.onselectstart !== undefined && savedhandlers.onselectstart == null) {
                    savedhandlers.onselectstart = document.onselectstart;
                    document.onselectstart = function () { return false; };
                }
                if (document.ondrag !== undefined && savedhandlers.ondrag == null) {
                    savedhandlers.ondrag = document.ondrag;
                    document.ondrag = function () { return false; };
                }

                setSelectionPos(selection.first, e);

                selection.active = true;
            }

            function onDragEnd(e) {
                // revert drag stuff for old-school browsers
                if (document.onselectstart !== undefined) {
                    document.onselectstart = savedhandlers.onselectstart;
                }

                if (document.ondrag !== undefined) {
                    document.ondrag = savedhandlers.ondrag;
                }

                // no more dragging
                selection.active = false;
                updateSelection(e);

                if (selectionIsSane()) {
                    triggerSelectedEvent();
                } else {
                    // this counts as a clear
                    trigger(plot.getPlaceholder(), "plotunselected", [ ]);
                    trigger(plot.getPlaceholder(), "plotselecting", [ null ]);
                }

                return false;
            }

            function getSelection() {
                if (!selectionIsSane()) return null;

                if (!selection.show) return null;

                var r = {},
                    c1 = {x: selection.first.x, y: selection.first.y},
                    c2 = {x: selection.second.x, y: selection.second.y};

                if (selectionDirection(plot) === 'x') {
                    c1.y = 0;
                    c2.y = plot.height();
                }

                if (selectionDirection(plot) === 'y') {
                    c1.x = 0;
                    c2.x = plot.width();
                }

                var axes = plot.getAxes();
                Object.keys(axes).forEach(function (name) {
                    var axis = axes[name];
                    if (axis.used) {
                        var p1 = axis.c2p(c1[axis.direction]), p2 = axis.c2p(c2[axis.direction]);
                        r[name] = { from: Math.min(p1, p2), to: Math.max(p1, p2) };
                    }
                });
                return r;
            }

            function triggerSelectedEvent() {
                var r = getSelection();

                trigger(plot.getPlaceholder(), "plotselected", [ r ]);

                // backwards-compat stuff, to be removed in future
                if (r.xaxis && r.yaxis) {
                    trigger(plot.getPlaceholder(), "selected", [ { x1: r.xaxis.from, y1: r.yaxis.from, x2: r.xaxis.to, y2: r.yaxis.to } ]);
                }
            }

            function clamp(min, value, max) {
                return value < min ? min : (value > max ? max : value);
            }

            function selectionDirection(plot) {
                var o = plot.getOptions();

                if (o.selection.mode === 'smart') {
                    return selection.currentMode;
                } else {
                    return o.selection.mode;
                }
            }

            function updateMode(pos) {
                if (selection.first) {
                    var delta = {
                        x: pos.x - selection.first.x,
                        y: pos.y - selection.first.y
                    };

                    if (Math.abs(delta.x) < SNAPPING_CONSTANT) {
                        selection.currentMode = 'y';
                    } else if (Math.abs(delta.y) < SNAPPING_CONSTANT) {
                        selection.currentMode = 'x';
                    } else {
                        selection.currentMode = 'xy';
                    }
                }
            }

            function setSelectionPos(pos, e) {
                var placeholderRect = plot.getPlaceholder().getBoundingClientRect();
                var offset = { left: placeholderRect.left + window.scrollX, top: placeholderRect.top + window.scrollY };
                var plotOffset = plot.getPlotOffset();
                pos.x = clamp(0, e.pageX - offset.left - plotOffset.left, plot.width());
                pos.y = clamp(0, e.pageY - offset.top - plotOffset.top, plot.height());

                if (pos !== selection.first) updateMode(pos);

                if (selectionDirection(plot) === "y") {
                    pos.x = pos === selection.first ? 0 : plot.width();
                }

                if (selectionDirection(plot) === "x") {
                    pos.y = pos === selection.first ? 0 : plot.height();
                }
            }

            function updateSelection(pos) {
                if (pos.pageX == null) return;

                setSelectionPos(selection.second, pos);
                if (selectionIsSane()) {
                    selection.show = true;
                    plot.triggerRedrawOverlay();
                } else clearSelection(true);
            }

            function clearSelection(preventEvent) {
                if (selection.show) {
                    selection.show = false;
                    selection.currentMode = '';
                    plot.triggerRedrawOverlay();
                    if (!preventEvent) {
                        trigger(plot.getPlaceholder(), "plotunselected", [ ]);
                    }
                }
            }

            // function taken from markings support in Flot
            function extractRange(ranges, coord) {
                var axis, from, to, key, axes = plot.getAxes();

                for (var k in axes) {
                    axis = axes[k];
                    if (axis.direction === coord) {
                        key = coord + axis.n + "axis";
                        if (!ranges[key] && axis.n === 1) {
                            // support x1axis as xaxis
                            key = coord + "axis";
                        }

                        if (ranges[key]) {
                            from = ranges[key].from;
                            to = ranges[key].to;
                            break;
                        }
                    }
                }

                // backwards-compat stuff - to be removed in future
                if (!ranges[key]) {
                    axis = coord === "x" ? plot.getXAxes()[0] : plot.getYAxes()[0];
                    from = ranges[coord + "1"];
                    to = ranges[coord + "2"];
                }

                // auto-reverse as an added bonus
                if (from != null && to != null && from > to) {
                    var tmp = from;
                    from = to;
                    to = tmp;
                }

                return { from: from, to: to, axis: axis };
            }

            function setSelection(ranges, preventEvent) {
                var range;

                if (selectionDirection(plot) === "y") {
                    selection.first.x = 0;
                    selection.second.x = plot.width();
                } else {
                    range = extractRange(ranges, "x");
                    selection.first.x = range.axis.p2c(range.from);
                    selection.second.x = range.axis.p2c(range.to);
                }

                if (selectionDirection(plot) === "x") {
                    selection.first.y = 0;
                    selection.second.y = plot.height();
                } else {
                    range = extractRange(ranges, "y");
                    selection.first.y = range.axis.p2c(range.from);
                    selection.second.y = range.axis.p2c(range.to);
                }

                selection.show = true;
                plot.triggerRedrawOverlay();
                if (!preventEvent && selectionIsSane()) {
                    triggerSelectedEvent();
                }
            }

            function selectionIsSane() {
                var minSize = plot.getOptions().selection.minSize;
                return Math.abs(selection.second.x - selection.first.x) >= minSize &&
                    Math.abs(selection.second.y - selection.first.y) >= minSize;
            }

            plot.clearSelection = clearSelection;
            plot.setSelection = setSelection;
            plot.getSelection = getSelection;

            function onPointerDown(e) {
                if (e.button !== 0) return;
                var el = e.currentTarget;
                onDragStart(e);

                function onPointerMove(e) {
                    onDrag(e);
                }

                function onPointerUp(e) {
                    onDragEnd(e);
                    el.removeEventListener("pointermove", onPointerMove);
                    el.removeEventListener("pointerup", onPointerUp);
                    el.removeEventListener("pointercancel", onPointerUp);
                    el.releasePointerCapture(e.pointerId);
                }

                el.setPointerCapture(e.pointerId);
                el.addEventListener("pointermove", onPointerMove);
                el.addEventListener("pointerup", onPointerUp);
                el.addEventListener("pointercancel", onPointerUp);
            }

            plot.hooks.bindEvents.push(function(plot, eventHolder) {
                var o = plot.getOptions();
                if (o.selection.mode != null) {
                    plot.addEventHandler("pointerdown", onPointerDown, eventHolder, 0);
                }
            });

            function drawSelectionDecorations(ctx, x, y, w, h, oX, oY, mode) {
                var spacing = 3;
                var fullEarWidth = 15;
                var earWidth = Math.max(0, Math.min(fullEarWidth, w / 2 - 2, h / 2 - 2));
                ctx.fillStyle = '#ffffff';

                if (mode === 'xy') {
                    ctx.beginPath();
                    ctx.moveTo(x, y + earWidth);
                    ctx.lineTo(x - 3, y + earWidth);
                    ctx.lineTo(x - 3, y - 3);
                    ctx.lineTo(x + earWidth, y - 3);
                    ctx.lineTo(x + earWidth, y);
                    ctx.lineTo(x, y);
                    ctx.closePath();

                    ctx.moveTo(x, y + h - earWidth);
                    ctx.lineTo(x - 3, y + h - earWidth);
                    ctx.lineTo(x - 3, y + h + 3);
                    ctx.lineTo(x + earWidth, y + h + 3);
                    ctx.lineTo(x + earWidth, y + h);
                    ctx.lineTo(x, y + h);
                    ctx.closePath();

                    ctx.moveTo(x + w, y + earWidth);
                    ctx.lineTo(x + w + 3, y + earWidth);
                    ctx.lineTo(x + w + 3, y - 3);
                    ctx.lineTo(x + w - earWidth, y - 3);
                    ctx.lineTo(x + w - earWidth, y);
                    ctx.lineTo(x + w, y);
                    ctx.closePath();

                    ctx.moveTo(x + w, y + h - earWidth);
                    ctx.lineTo(x + w + 3, y + h - earWidth);
                    ctx.lineTo(x + w + 3, y + h + 3);
                    ctx.lineTo(x + w - earWidth, y + h + 3);
                    ctx.lineTo(x + w - earWidth, y + h);
                    ctx.lineTo(x + w, y + h);
                    ctx.closePath();

                    ctx.stroke();
                    ctx.fill();
                }

                x = oX;
                y = oY;

                if (mode === 'x') {
                    ctx.beginPath();
                    ctx.moveTo(x, y + fullEarWidth);
                    ctx.lineTo(x, y - fullEarWidth);
                    ctx.lineTo(x - spacing, y - fullEarWidth);
                    ctx.lineTo(x - spacing, y + fullEarWidth);
                    ctx.closePath();

                    ctx.moveTo(x + w, y + fullEarWidth);
                    ctx.lineTo(x + w, y - fullEarWidth);
                    ctx.lineTo(x + w + spacing, y - fullEarWidth);
                    ctx.lineTo(x + w + spacing, y + fullEarWidth);
                    ctx.closePath();
                    ctx.stroke();
                    ctx.fill();
                }

                if (mode === 'y') {
                    ctx.beginPath();

                    ctx.moveTo(x - fullEarWidth, y);
                    ctx.lineTo(x + fullEarWidth, y);
                    ctx.lineTo(x + fullEarWidth, y - spacing);
                    ctx.lineTo(x - fullEarWidth, y - spacing);
                    ctx.closePath();

                    ctx.moveTo(x - fullEarWidth, y + h);
                    ctx.lineTo(x + fullEarWidth, y + h);
                    ctx.lineTo(x + fullEarWidth, y + h + spacing);
                    ctx.lineTo(x - fullEarWidth, y + h + spacing);
                    ctx.closePath();
                    ctx.stroke();
                    ctx.fill();
                }
            }

            plot.hooks.drawOverlay.push(function (plot, ctx) {
                // draw selection
                if (selection.show && selectionIsSane()) {
                    var plotOffset = plot.getPlotOffset();
                    var o = plot.getOptions();

                    ctx.save();
                    ctx.translate(plotOffset.left, plotOffset.top);

                    var c = color.parse(o.selection.color);
                    var visualization = o.selection.visualization;
                    var displaySelectionDecorations = o.selection.displaySelectionDecorations;

                    var scalingFactor = 1;

                    // use a dimmer scaling factor if visualization is "fill"
                    if (visualization === "fill") {
                        scalingFactor = 0.8;
                    }

                    ctx.strokeStyle = c.scale('a', scalingFactor).toString();
                    ctx.lineWidth = 1;
                    ctx.lineJoin = o.selection.shape;
                    ctx.fillStyle = c.scale('a', 0.4).toString();

                    var x = Math.min(selection.first.x, selection.second.x) + 0.5,
                        oX = x,
                        y = Math.min(selection.first.y, selection.second.y) + 0.5,
                        oY = y,
                        w = Math.abs(selection.second.x - selection.first.x) - 1,
                        h = Math.abs(selection.second.y - selection.first.y) - 1;

                    if (selectionDirection(plot) === 'x') {
                        h += y;
                        y = 0;
                    }

                    if (selectionDirection(plot) === 'y') {
                        w += x;
                        x = 0;
                    }

                    if (visualization === "fill") {
                        ctx.fillRect(x, y, w, h);
                        ctx.strokeRect(x, y, w, h);
                    } else {
                        ctx.fillRect(0, 0, plot.width(), plot.height());
                        ctx.clearRect(x, y, w, h);

                        if (displaySelectionDecorations) {
                            drawSelectionDecorations(ctx, x, y, w, h, oX, oY, selectionDirection(plot));
                        }
                    }

                    ctx.restore();
                }
            });

            plot.hooks.shutdown.push(function (plot, eventHolder) {
                unbind(eventHolder, "pointerdown", onPointerDown);
            });
        }

        plugins.push({
            init: init$2,
            options: {
                selection: {
                    mode: null, // one of null, "x", "y" or "xy"
                    visualization: "focus", // "focus" or "fill"
                    displaySelectionDecorations: true, // true or false (currently only relevant for the focus visualization)
                    color: "#888888",
                    shape: "round", // one of "round", "miter", or "bevel"
                    minSize: 5 // minimum number of pixels
                }
            },
            name: 'selection',
            version: '1.1'
        });

    /** ## jquery.flot.composeImages.js

    This plugin is used to expose a function used to overlap several canvases and
    SVGs, for the purpose of creating a snaphot out of them.

    ### When composeImages is used:
    When multiple canvases and SVGs have to be overlapped into a single image
    and their offset on the page, must be preserved.

    ### Where can be used:
    In creating a downloadable snapshot of the plots, axes, cursors etc of a graph.

    ### How it works:
    The entry point is composeImages function. It expects an array of objects,
    which should be either canvases or SVGs (or a mix). It does a prevalidation
    of them, by verifying if they will be usable or not, later in the flow.
    After selecting only usable sources, it passes them to getGenerateTempImg
    function, which generates temporary images out of them. This function
    expects that some of the passed sources (canvas or SVG) may still have
    problems being converted to an image and makes sure the promises system,
    used by composeImages function, moves forward. As an example, SVGs with
    missing information from header or with unsupported content, may lead to
    failure in generating the temporary image. Temporary images are required
    mostly on extracting content from SVGs, but this is also where the x/y
    offsets are extracted for each image which will be added. For SVGs in
    particular, their CSS rules have to be applied.
    After all temporary images are generated, they are overlapped using
    getExecuteImgComposition function. This is where the destination canvas
    is set to the proper dimensions. It is then output by composeImages.
    This function returns a promise, which can be used to wait for the whole
    composition process. It requires to be asynchronous, because this is how
    temporary images load their data.
    */

        const GENERALFAILURECALLBACKERROR = -100; //simply a negative number
        const SUCCESSFULIMAGEPREPARATION = 0;
        const EMPTYARRAYOFIMAGESOURCES = -1;
        const NEGATIVEIMAGESIZE = -2;
        var pixelRatio = 1;
        var getPixelRatio = browser.getPixelRatio;

        function composeImages(canvasOrSvgSources, destinationCanvas) {
            var validCanvasOrSvgSources = canvasOrSvgSources.filter(isValidSource);
            pixelRatio = getPixelRatio(destinationCanvas.getContext('2d'));

            var allImgCompositionPromises = validCanvasOrSvgSources.map(function(validCanvasOrSvgSource) {
                var tempImg = new Image();
                var currentPromise = new Promise(getGenerateTempImg(tempImg, validCanvasOrSvgSource));
                return currentPromise;
            });

            var lastPromise = Promise.all(allImgCompositionPromises).then(getExecuteImgComposition(destinationCanvas), failureCallback);
            return lastPromise;
        }

        function isValidSource(canvasOrSvgSource) {
            var isValidFromCanvas = true;
            var isValidFromContent = true;
            if ((canvasOrSvgSource === null) || (canvasOrSvgSource === undefined)) {
                isValidFromContent = false;
            } else {
                if (canvasOrSvgSource.tagName === 'CANVAS') {
                    if ((canvasOrSvgSource.getBoundingClientRect().right === canvasOrSvgSource.getBoundingClientRect().left) ||
                        (canvasOrSvgSource.getBoundingClientRect().bottom === canvasOrSvgSource.getBoundingClientRect().top)) {
                        isValidFromCanvas = false;
                    }
                }
            }
            return isValidFromContent && isValidFromCanvas && (window.getComputedStyle(canvasOrSvgSource).visibility === 'visible');
        }

        function getGenerateTempImg(tempImg, canvasOrSvgSource) {
            tempImg.sourceDescription = '<info className="' + canvasOrSvgSource.className + '" tagName="' + canvasOrSvgSource.tagName + '" id="' + canvasOrSvgSource.id + '">';
            tempImg.sourceComponent = canvasOrSvgSource;

            return function doGenerateTempImg(successCallbackFunc, failureCallbackFunc) {
                tempImg.onload = function(evt) {
                    tempImg.successfullyLoaded = true;
                    successCallbackFunc(tempImg);
                };

                tempImg.onabort = function(evt) {
                    tempImg.successfullyLoaded = false;
                    console.log('Can\'t generate temp image from ' + tempImg.sourceDescription + '. It is possible that it is missing some properties or its content is not supported by this browser. Source component:', tempImg.sourceComponent);
                    successCallbackFunc(tempImg); //call successCallback, to allow snapshot of all working images
                };

                tempImg.onerror = function(evt) {
                    tempImg.successfullyLoaded = false;
                    console.log('Can\'t generate temp image from ' + tempImg.sourceDescription + '. It is possible that it is missing some properties or its content is not supported by this browser. Source component:', tempImg.sourceComponent);
                    successCallbackFunc(tempImg); //call successCallback, to allow snapshot of all working images
                };

                generateTempImageFromCanvasOrSvg(canvasOrSvgSource, tempImg);
            };
        }

        function getExecuteImgComposition(destinationCanvas) {
            return function executeImgComposition(tempImgs) {
                var compositionResult = copyImgsToCanvas(tempImgs, destinationCanvas);
                return compositionResult;
            };
        }

        function copyCanvasToImg(canvas, img) {
            img.src = canvas.toDataURL('image/png');
        }

        function getCSSRules(document) {
            var styleSheets = document.styleSheets,
                rulesList = [];
            for (var i = 0; i < styleSheets.length; i++) {
                // CORS requests for style sheets throw and an exception on Chrome > 64
                try {
                    // in Chrome, the external CSS files are empty when the page is directly loaded from disk
                    var rules = styleSheets[i].cssRules || [];
                    for (var j = 0; j < rules.length; j++) {
                        var rule = rules[j];
                        rulesList.push(rule.cssText);
                    }
                } catch (e) {
                    console.log('Failed to get some css rules');
                }
            }
            return rulesList;
        }

        function embedCSSRulesInSVG(rules, svg) {
            var text = [
                '<svg class="snapshot ' + svg.classList + '" width="' + svg.width.baseVal.value * pixelRatio + '" height="' + svg.height.baseVal.value * pixelRatio + '" viewBox="0 0 ' + svg.width.baseVal.value + ' ' + svg.height.baseVal.value + '" xmlns="http://www.w3.org/2000/svg">',
                '<style>',
                '/* <![CDATA[ */',
                rules.join('\n'),
                '/* ]]> */',
                '</style>',
                svg.innerHTML,
                '</svg>'
            ].join('\n');
            return text;
        }

        function copySVGToImgMostBrowsers(svg, img) {
            var rules = getCSSRules(document),
                source = embedCSSRulesInSVG(rules, svg);

            source = patchSVGSource(source);

            var blob = new Blob([source], {type: "image/svg+xml;charset=utf-8"}),
                domURL = self.URL || self.webkitURL || self,
                url = domURL.createObjectURL(blob);
            img.src = url;
        }

        function copySVGToImgSafari(svg, img) {
            // Use this method to convert a string buffer array to a binary string.
            // Do so by breaking up large strings into smaller substrings; this is necessary to avoid the
            // "maximum call stack size exceeded" exception that can happen when calling 'String.fromCharCode.apply'
            // with a very long array.
            function buildBinaryString (arrayBuffer) {
                var binaryString = "";
                const utf8Array = new Uint8Array(arrayBuffer);
                const blockSize = 16384;
                for (var i = 0; i < utf8Array.length; i = i + blockSize) {
                    const binarySubString = String.fromCharCode.apply(null, utf8Array.subarray(i, i + blockSize));
                    binaryString = binaryString + binarySubString;
                }
                return binaryString;
            }
            var rules = getCSSRules(document),
                source = embedCSSRulesInSVG(rules, svg),
                data,
                utf8BinaryString;

            source = patchSVGSource(source);

            // Encode the string as UTF-8 and convert it to a binary string. The UTF-8 encoding is required to
            // capture unicode characters correctly.
            utf8BinaryString = buildBinaryString(new (TextEncoder || TextEncoderLite)().encode(source));

            data = "data:image/svg+xml;base64," + btoa(utf8BinaryString);
            img.src = data;
        }

        function patchSVGSource(svgSource) {
            var source = '';
            //add name spaces.
            if (!svgSource.match(/^<svg[^>]+xmlns="http:\/\/www\.w3\.org\/2000\/svg"/)) {
                source = svgSource.replace(/^<svg/, '<svg xmlns="http://www.w3.org/2000/svg"');
            }
            if (!svgSource.match(/^<svg[^>]+"http:\/\/www\.w3\.org\/1999\/xlink"/)) {
                source = svgSource.replace(/^<svg/, '<svg xmlns:xlink="http://www.w3.org/1999/xlink"');
            }

            //add xml declaration
            return '<?xml version="1.0" standalone="no"?>\r\n' + source;
        }

        function copySVGToImg(svg, img) {
            if (browser.isSafari() || browser.isMobileSafari()) {
                copySVGToImgSafari(svg, img);
            } else {
                copySVGToImgMostBrowsers(svg, img);
            }
        }

        function adaptDestSizeToZoom(destinationCanvas, sources) {
            function containsSVGs(source) {
                return source.srcImgTagName === 'svg';
            }

            if (sources.find(containsSVGs) !== undefined) {
                if (pixelRatio < 1) {
                    destinationCanvas.width = destinationCanvas.width * pixelRatio;
                    destinationCanvas.height = destinationCanvas.height * pixelRatio;
                }
            }
        }

        function prepareImagesToBeComposed(sources, destination) {
            var result = SUCCESSFULIMAGEPREPARATION;
            if (sources.length === 0) {
                result = EMPTYARRAYOFIMAGESOURCES; //nothing to do if called without sources
            } else {
                var minX = sources[0].genLeft;
                var minY = sources[0].genTop;
                var maxX = sources[0].genRight;
                var maxY = sources[0].genBottom;
                var i = 0;

                for (i = 1; i < sources.length; i++) {
                    if (minX > sources[i].genLeft) {
                        minX = sources[i].genLeft;
                    }

                    if (minY > sources[i].genTop) {
                        minY = sources[i].genTop;
                    }
                }

                for (i = 1; i < sources.length; i++) {
                    if (maxX < sources[i].genRight) {
                        maxX = sources[i].genRight;
                    }

                    if (maxY < sources[i].genBottom) {
                        maxY = sources[i].genBottom;
                    }
                }

                if ((maxX - minX <= 0) || (maxY - minY <= 0)) {
                    result = NEGATIVEIMAGESIZE; //this might occur on hidden images
                } else {
                    destination.width = Math.round(maxX - minX);
                    destination.height = Math.round(maxY - minY);

                    for (i = 0; i < sources.length; i++) {
                        sources[i].xCompOffset = sources[i].genLeft - minX;
                        sources[i].yCompOffset = sources[i].genTop - minY;
                    }

                    adaptDestSizeToZoom(destination, sources);
                }
            }
            return result;
        }

        function copyImgsToCanvas(sources, destination) {
            var prepareImagesResult = prepareImagesToBeComposed(sources, destination);
            if (prepareImagesResult === SUCCESSFULIMAGEPREPARATION) {
                var destinationCtx = destination.getContext('2d');

                for (var i = 0; i < sources.length; i++) {
                    if (sources[i].successfullyLoaded === true) {
                        destinationCtx.drawImage(sources[i], sources[i].xCompOffset * pixelRatio, sources[i].yCompOffset * pixelRatio);
                    }
                }
            }
            return prepareImagesResult;
        }

        function adnotateDestImgWithBoundingClientRect(srcCanvasOrSvg, destImg) {
            destImg.genLeft = srcCanvasOrSvg.getBoundingClientRect().left;
            destImg.genTop = srcCanvasOrSvg.getBoundingClientRect().top;

            if (srcCanvasOrSvg.tagName === 'CANVAS') {
                destImg.genRight = destImg.genLeft + srcCanvasOrSvg.width;
                destImg.genBottom = destImg.genTop + srcCanvasOrSvg.height;
            }

            if (srcCanvasOrSvg.tagName === 'svg') {
                destImg.genRight = srcCanvasOrSvg.getBoundingClientRect().right;
                destImg.genBottom = srcCanvasOrSvg.getBoundingClientRect().bottom;
            }
        }

        function generateTempImageFromCanvasOrSvg(srcCanvasOrSvg, destImg) {
            if (srcCanvasOrSvg.tagName === 'CANVAS') {
                copyCanvasToImg(srcCanvasOrSvg, destImg);
            }

            if (srcCanvasOrSvg.tagName === 'svg') {
                copySVGToImg(srcCanvasOrSvg, destImg);
            }

            destImg.srcImgTagName = srcCanvasOrSvg.tagName;
            adnotateDestImgWithBoundingClientRect(srcCanvasOrSvg, destImg);
        }

        function failureCallback() {
            return GENERALFAILURECALLBACKERROR;
        }

        function init$1(plot) {
            // used to extend the public API of the plot
            plot.composeImages = composeImages;
        }

        plugins.push({
            init: init$1,
            name: 'composeImages',
            version: '1.0'
        });

    /* Flot plugin for drawing legends.

    */


        var defaultOptions = {
            legend: {
                show: false,
                noColumns: 1,
                labelFormatter: null, // fn: string -> string
                container: null, // container (as jQuery object) to put legend in, null means default on top of graph
                position: 'ne', // position of default legend container within plot
                margin: 5, // distance from grid edge to default legend container within plot
                sorted: null // default to no legend sorting
            }
        };

        function insertLegend(plot, options, placeholder, legendEntries) {
            // clear before redraw
            if (options.legend.container != null) {
                options.legend.container.innerHTML = '';
            } else {
                var oldLegends = placeholder.querySelectorAll('.legend');
                for (var li = 0; li < oldLegends.length; li++) {
                    oldLegends[li].remove();
                }
            }

            if (!options.legend.show) {
                return;
            }

            // Save the legend entries in legend options
            var entries = options.legend.legendEntries = legendEntries,
                plotOffset = options.legend.plotOffset = plot.getPlotOffset(),
                html = [],
                entry, labelHtml, iconHtml,
                j = 0,
                i,
                pos = "",
                p = options.legend.position,
                m = options.legend.margin,
                shape = {
                    name: '',
                    label: '',
                    xPos: '',
                    yPos: ''
                };

            html[j++] = '<svg class="legendLayer" style="width:inherit;height:inherit;">';
            html[j++] = '<rect class="background" width="100%" height="100%"/>';
            html[j++] = svgShapeDefs;

            var left = 0;
            var columnWidths = [];
            var style = window.getComputedStyle(document.querySelector('body'));
            for (i = 0; i < entries.length; ++i) {
                let columnIndex = i % options.legend.noColumns;
                entry = entries[i];
                shape.label = entry.label;
                var info = plot.getSurface().getTextInfo('', shape.label, {
                    style: style.fontStyle,
                    variant: style.fontVariant,
                    weight: style.fontWeight,
                    size: parseInt(style.fontSize),
                    lineHeight: parseInt(style.lineHeight),
                    family: style.fontFamily
                });

                var labelWidth = info.width;
                // 36px = 1.5em + 6px margin
                var iconWidth = 48;
                if (columnWidths[columnIndex]) {
                    if (labelWidth > columnWidths[columnIndex]) {
                        columnWidths[columnIndex] = labelWidth + iconWidth;
                    }
                } else {
                    columnWidths[columnIndex] = labelWidth + iconWidth;
                }
            }

            // Generate html for icons and labels from a list of entries
            for (i = 0; i < entries.length; ++i) {
                let columnIndex = i % options.legend.noColumns;
                entry = entries[i];
                iconHtml = '';
                shape.label = entry.label;
                shape.xPos = (left + 3) + 'px';
                left += columnWidths[columnIndex];
                if ((i + 1) % options.legend.noColumns === 0) {
                    left = 0;
                }
                shape.yPos = Math.floor(i / options.legend.noColumns) * 1.5 + 'em';
                // area
                if (entry.options.lines.show && entry.options.lines.fill) {
                    shape.name = 'area';
                    shape.fillColor = entry.color;
                    iconHtml += getEntryIconHtml(shape);
                }
                // bars
                if (entry.options.bars.show) {
                    shape.name = 'bar';
                    shape.fillColor = entry.color;
                    iconHtml += getEntryIconHtml(shape);
                }
                // lines
                if (entry.options.lines.show && !entry.options.lines.fill) {
                    shape.name = 'line';
                    shape.strokeColor = entry.color;
                    shape.strokeWidth = entry.options.lines.lineWidth;
                    iconHtml += getEntryIconHtml(shape);
                }
                // points
                if (entry.options.points.show) {
                    shape.name = entry.options.points.symbol;
                    shape.strokeColor = entry.color;
                    shape.fillColor = entry.options.points.fillColor;
                    shape.strokeWidth = entry.options.points.lineWidth;
                    iconHtml += getEntryIconHtml(shape);
                }

                labelHtml = '<text x="' + shape.xPos + '" y="' + shape.yPos + '" text-anchor="start"><tspan dx="2em" dy="1.2em">' + shape.label + '</tspan></text>';
                html[j++] = '<g>' + iconHtml + labelHtml + '</g>';
            }

            html[j++] = '</svg>';
            if (m[0] == null) {
                m = [m, m];
            }

            if (p.charAt(0) === 'n') {
                pos += 'top:' + (m[1] + plotOffset.top) + 'px;';
            } else if (p.charAt(0) === 's') {
                pos += 'bottom:' + (m[1] + plotOffset.bottom) + 'px;';
            }

            if (p.charAt(1) === 'e') {
                pos += 'right:' + (m[0] + plotOffset.right) + 'px;';
            } else if (p.charAt(1) === 'w') {
                pos += 'left:' + (m[0] + plotOffset.left) + 'px;';
            }

            var width = 6;
            for (i = 0; i < columnWidths.length; ++i) {
                width += columnWidths[i];
            }

            var legendEl,
                height = Math.ceil(entries.length / options.legend.noColumns) * 1.6;
            if (!options.legend.container) {
                legendEl = document.createElement('div');
                legendEl.className = 'legend';
                legendEl.style.cssText = 'position:absolute;' + pos;
                legendEl.innerHTML = html.join('');
                legendEl.style.width = width + 'px';
                legendEl.style.height = height + 'em';
                legendEl.style.pointerEvents = 'none';
                placeholder.appendChild(legendEl);
            } else {
                options.legend.container.innerHTML = html.join('');
                options.legend.container.style.width = width + 'px';
                options.legend.container.style.height = height + 'em';
            }
        }

        // Generate html for a shape
        function getEntryIconHtml(shape) {
            var html = '',
                name = shape.name,
                x = shape.xPos,
                y = shape.yPos,
                fill = shape.fillColor,
                stroke = shape.strokeColor,
                width = shape.strokeWidth;
            switch (name) {
                case 'circle':
                    html = '<use xlink:href="#circle" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        'fill="' + fill + '" ' +
                        'stroke="' + stroke + '" ' +
                        'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                case 'diamond':
                    html = '<use xlink:href="#diamond" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        'fill="' + fill + '" ' +
                        'stroke="' + stroke + '" ' +
                        'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                case 'cross':
                    html = '<use xlink:href="#cross" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        // 'fill="' + fill + '" ' +
                        'stroke="' + stroke + '" ' +
                        'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                case 'rectangle':
                    html = '<use xlink:href="#rectangle" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        'fill="' + fill + '" ' +
                        'stroke="' + stroke + '" ' +
                        'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                case 'plus':
                    html = '<use xlink:href="#plus" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        // 'fill="' + fill + '" ' +
                        'stroke="' + stroke + '" ' +
                        'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                case 'bar':
                    html = '<use xlink:href="#bars" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        'fill="' + fill + '" ' +
                        // 'stroke="' + stroke + '" ' +
                        // 'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                case 'area':
                    html = '<use xlink:href="#area" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        'fill="' + fill + '" ' +
                        // 'stroke="' + stroke + '" ' +
                        // 'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                case 'line':
                    html = '<use xlink:href="#line" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        // 'fill="' + fill + '" ' +
                        'stroke="' + stroke + '" ' +
                        'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
                    break;
                default:
                    // default is circle
                    html = '<use xlink:href="#circle" class="legendIcon" ' +
                        'x="' + x + '" ' +
                        'y="' + y + '" ' +
                        'fill="' + fill + '" ' +
                        'stroke="' + stroke + '" ' +
                        'stroke-width="' + width + '" ' +
                        'width="1.5em" height="1.5em"' +
                        '/>';
            }

            return html;
        }

        // Define svg symbols for shapes
        var svgShapeDefs = '' +
            '<defs>' +
                '<symbol id="line" fill="none" viewBox="-5 -5 25 25">' +
                    '<polyline points="0,15 5,5 10,10 15,0"/>' +
                '</symbol>' +

                '<symbol id="area" stroke-width="1" viewBox="-5 -5 25 25">' +
                    '<polyline points="0,15 5,5 10,10 15,0, 15,15, 0,15"/>' +
                '</symbol>' +

                '<symbol id="bars" stroke-width="1" viewBox="-5 -5 25 25">' +
                    '<polyline points="1.5,15.5 1.5,12.5, 4.5,12.5 4.5,15.5 6.5,15.5 6.5,3.5, 9.5,3.5 9.5,15.5 11.5,15.5 11.5,7.5 14.5,7.5 14.5,15.5 1.5,15.5"/>' +
                '</symbol>' +

                '<symbol id="circle" viewBox="-5 -5 25 25">' +
                    '<circle cx="0" cy="15" r="2.5"/>' +
                    '<circle cx="5" cy="5" r="2.5"/>' +
                    '<circle cx="10" cy="10" r="2.5"/>' +
                    '<circle cx="15" cy="0" r="2.5"/>' +
                '</symbol>' +

                '<symbol id="rectangle" viewBox="-5 -5 25 25">' +
                    '<rect x="-2.1" y="12.9" width="4.2" height="4.2"/>' +
                    '<rect x="2.9" y="2.9" width="4.2" height="4.2"/>' +
                    '<rect x="7.9" y="7.9" width="4.2" height="4.2"/>' +
                    '<rect x="12.9" y="-2.1" width="4.2" height="4.2"/>' +
                '</symbol>' +

                '<symbol id="diamond" viewBox="-5 -5 25 25">' +
                    '<path d="M-3,15 L0,12 L3,15, L0,18 Z"/>' +
                    '<path d="M2,5 L5,2 L8,5, L5,8 Z"/>' +
                    '<path d="M7,10 L10,7 L13,10, L10,13 Z"/>' +
                    '<path d="M12,0 L15,-3 L18,0, L15,3 Z"/>' +
                '</symbol>' +

                '<symbol id="cross" fill="none" viewBox="-5 -5 25 25">' +
                    '<path d="M-2.1,12.9 L2.1,17.1, M2.1,12.9 L-2.1,17.1 Z"/>' +
                    '<path d="M2.9,2.9 L7.1,7.1 M7.1,2.9 L2.9,7.1 Z"/>' +
                    '<path d="M7.9,7.9 L12.1,12.1 M12.1,7.9 L7.9,12.1 Z"/>' +
                    '<path d="M12.9,-2.1 L17.1,2.1 M17.1,-2.1 L12.9,2.1 Z"/>' +
                '</symbol>' +

                '<symbol id="plus" fill="none" viewBox="-5 -5 25 25">' +
                    '<path d="M0,12 L0,18, M-3,15 L3,15 Z"/>' +
                    '<path d="M5,2 L5,8 M2,5 L8,5 Z"/>' +
                    '<path d="M10,7 L10,13 M7,10 L13,10 Z"/>' +
                    '<path d="M15,-3 L15,3 M12,0 L18,0 Z"/>' +
                '</symbol>' +
            '</defs>';

        // Generate a list of legend entries in their final order
        function getLegendEntries(series, labelFormatter, sorted) {
            var lf = labelFormatter,
                legendEntries = series.reduce(function(validEntries, s, i) {
                    var labelEval = (lf ? lf(s.label, s) : s.label);
                    if (s.hasOwnProperty("label") ? labelEval : true) {
                        var entry = {
                            label: labelEval || 'Plot ' + (i + 1),
                            color: s.color,
                            options: {
                                lines: s.lines,
                                points: s.points,
                                bars: s.bars
                            }
                        };
                        validEntries.push(entry);
                    }
                    return validEntries;
                }, []);

            // Sort the legend using either the default or a custom comparator
            if (sorted) {
                if (typeof sorted === 'function') {
                    legendEntries.sort(sorted);
                } else if (sorted === 'reverse') {
                    legendEntries.reverse();
                } else {
                    var ascending = (sorted !== 'descending');
                    legendEntries.sort(function(a, b) {
                        return a.label === b.label
                            ? 0
                            : ((a.label < b.label) !== ascending ? 1 : -1 // Logical XOR
                            );
                    });
                }
            }

            return legendEntries;
        }

        // return false if opts1 same as opts2
        function checkOptions(opts1, opts2) {
            for (var prop in opts1) {
                if (opts1.hasOwnProperty(prop)) {
                    if (opts1[prop] !== opts2[prop]) {
                        return true;
                    }
                }
            }
            return false;
        }

        // Compare two lists of legend entries
        function shouldRedraw(oldEntries, newEntries) {
            if (!oldEntries || !newEntries) {
                return true;
            }

            if (oldEntries.length !== newEntries.length) {
                return true;
            }
            var i, newEntry, oldEntry, newOpts, oldOpts;
            for (i = 0; i < newEntries.length; i++) {
                newEntry = newEntries[i];
                oldEntry = oldEntries[i];

                if (newEntry.label !== oldEntry.label) {
                    return true;
                }

                if (newEntry.color !== oldEntry.color) {
                    return true;
                }

                // check for changes in lines options
                newOpts = newEntry.options.lines;
                oldOpts = oldEntry.options.lines;
                if (checkOptions(newOpts, oldOpts)) {
                    return true;
                }

                // check for changes in points options
                newOpts = newEntry.options.points;
                oldOpts = oldEntry.options.points;
                if (checkOptions(newOpts, oldOpts)) {
                    return true;
                }

                // check for changes in bars options
                newOpts = newEntry.options.bars;
                oldOpts = oldEntry.options.bars;
                if (checkOptions(newOpts, oldOpts)) {
                    return true;
                }
            }

            return false;
        }

        function init(plot) {
            plot.hooks.setupGrid.push(function (plot) {
                var options = plot.getOptions();
                var series = plot.getData(),
                    labelFormatter = options.legend.labelFormatter,
                    oldEntries = options.legend.legendEntries,
                    oldPlotOffset = options.legend.plotOffset,
                    newEntries = getLegendEntries(series, labelFormatter, options.legend.sorted),
                    newPlotOffset = plot.getPlotOffset();

                if (shouldRedraw(oldEntries, newEntries) ||
                    checkOptions(oldPlotOffset, newPlotOffset)) {
                    insertLegend(plot, options, plot.getPlaceholder(), newEntries);
                }
            });
        }

        plugins.push({
            init: init,
            options: defaultOptions,
            name: 'legend',
            version: '1.0'
        });

    exports.Canvas = Canvas;
    exports.browser = browser;
    exports.color = color;
    exports.composeImages = composeImages;
    exports.dateGenerator = dateGenerator;
    exports.dateTickGenerator = dateTickGenerator;
    exports.defaultTickFormatter = defaultTickFormatter;
    exports.drawSeries = drawSeries;
    exports.expRepTickFormatter = expRepTickFormatter;
    exports.formatDate = formatDate;
    exports.linearTickGenerator = linearTickGenerator;
    exports.logTickFormatter = logTickFormatter;
    exports.logTicksGenerator = logTicksGenerator;
    exports.makeUtcWrapper = makeUtcWrapper;
    exports.plot = plot;
    exports.plugins = plugins;
    exports.saturated = saturated;
    exports.uiConstants = uiConstants;
    exports.version = version;

    return exports;

})({});
