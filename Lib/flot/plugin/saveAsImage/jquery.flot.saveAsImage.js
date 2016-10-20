/* Flot plugin that adds a function to allow user save the current graph as an image
    by right clicking on the graph and then choose "Save image as ..." to local disk.

Copyright (c) 2013 http://zizhujy.com.
Licensed under the MIT license.

Usage:
    Inside the <head></head> area of your html page, add the following lines:
    
    <script type="text/javascript" src="http://zizhujy.com/Scripts/base64.js"></script>
    <script type="text/javascript" src="http://zizhujy.com/Scripts/drawing/canvas2image.js"></script>
    <script type="text/javascript" src="http://zizhujy.com/Scripts/flot/jquery.flot.saveAsImage.js"></script>

    Now you are all set. Right click on your flot canvas, you will see the "Save image as ..." option.

Online examples:
    http://zizhujy.com/FunctionGrapher is using it, you can try right clicking on the function graphs and
    you will see you can save the image to local disk.

Dependencies:
    This plugin references the base64.js and canvas2image.js.

Customizations:
    The default behavior of this plugin is dynamically creating an image from the flot canvas, and then puts the 
    image above the flot canvas. If you want to add some css effects on to the dynamically created image, you can
    apply whatever css styles on to it, only remember to make sure the css class name is set correspondingly by 
    the options object of this plugin. You can also customize the image format through this options object:

    options: {
        imageClassName: "canvas-image",
        imageFormat: "png"
    }

*/

; (function ($, Canvas2Image) {
    var imageCreated = null;
    var mergedCanvas = null;
    var theClasses = null;

    function init(plot, classes) {
        theClasses = classes;
        plot.hooks.bindEvents.push(bindEvents);
        plot.hooks.shutdown.push(shutdown);

        function bindEvents(plot, eventHolder) {
            eventHolder.mousedown(onMouseDown);
        }

        function shutdown(plot, eventHolder) {
            eventHolder.unbind("mousedown", onMouseDown);
        }

        function onMouseDown(e) {
            if (e.button == 2) {
                // Open an API in Canvas2Image, in case you would need to call
                // it to delete the dynamically created image.
                //Canvas2Image.deleteStaleCanvasImage = deleteStaleCanvasImage;
                deleteStaleCanvasImage(plot, mergedCanvas);
                mergedCanvas = mergeCanvases(plot);
                createImageFromCanvas(mergedCanvas, plot, plot.getOptions().imageFormat);
                // For ubuntu chrome:
                setTimeout(function () { deleteStaleCanvasImage(plot, mergedCanvas); }, 500);
            }
        }
    }

    function onMouseUp(plot) {
        setTimeout(function () { deleteStaleCanvasImage(plot, mergedCanvas); }, 100);
    }

    function deleteStaleCanvasImage(plot, mergedCanvas) {
        //$(plot.getCanvas()).parent().find("img." + plot.getOptions().imageClassName).unbind("mouseup", onMouseUp).remove();
        $(imageCreated).unbind("mouseup", onMouseUp).remove();
        if (!!mergedCanvas) {
            $(mergedCanvas).remove();
        }
        $(".mergedCanvas").remove();
    }
    
    function mergeCanvases(plot) {
        
        var theMergedCanvas = plot.getCanvas();

        if (!!theClasses) {
            theMergedCanvas = new theClasses.Canvas("mergedCanvas", plot.getPlaceholder());
            var mergedContext = theMergedCanvas.context;
            var plotCanvas = plot.getCanvas();
            
            theMergedCanvas.element.height = plotCanvas.height;
            theMergedCanvas.element.width = plotCanvas.width;
            
            mergedContext.restore();

            $(theMergedCanvas).css({
                "visibility": "hidden",
                "z-index": "-100",
                "position": "absolute"
            });

            var $canvases = $(plot.getPlaceholder()).find("canvas").not('.mergedCanvas');
            $canvases.each(function(index, canvas) {
                mergedContext.drawImage(canvas, 0, 0);
            });

            return theMergedCanvas.element;
        }

        return theMergedCanvas;
    }

    function createImageFromCanvas(canvas, plot, format) {
        if (!canvas) {
            canvas = plot.getCanvas();
        }
        
        var img = null;
        switch (format.toLowerCase()) {
            case "png":
                img = Canvas2Image.saveAsPNG(canvas, format);
                break;
            case "bmp":
                img = Canvas2Image.saveAsBMP(canvas, format);
                break;
            case "jpeg":
                img = Canvas2Image.saveAsJPEG(canvas, format);
                break;
            default:
                break;
        }

        if (!img) {
            img = Canvas2Image.saveAsPNG(canvas, "png");
        }

        if (!img) {
            img = Canvas2Image.saveAsPNG(canvas, "bmp");
        }

        if (!img) {
            img = Canvas2Image.saveAsJPEG(canvas, "jpeg");
        }

        if (!img) {
            alert(plot.getOptions().notSupportMessage || "Oh Sorry, but this browser is not capable of creating image files, please use PRINT SCREEN key instead!");
            return false;
        }

        $(img).attr("class", plot.getOptions().imageClassName);
        $(img).css({ "border": $(canvas).css("border"), "z-index": "9999", "position": "absolute" });
        $(img).insertBefore($(canvas));
        $(img).mouseup(plot, onMouseUp);

        imageCreated = img;
    }

    var options = {
        imageClassName: "canvas-image",
        imageFormat: "png"
    };

    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'saveAsImage',
        version: '1.6'
    });

})(jQuery, Canvas2Image);
