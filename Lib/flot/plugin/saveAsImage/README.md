canvasAsImage.js
================

Reference this file in your header of which page you are using canvas, then when you right click on your canvas, a context menu would appeared and gave you a option to save your drawings on canvas as image to your local disk.

##Online examples:##
http://zizhujy.com/GraphWorld

jquery.flot.saveAsImage.js
==========================

Flot plugin that adds a function to allow user save the current graph as an image by right clicking on the graph and then choose "Save image as ..." to local disk.

Copyright (c) 2013 http://zizhujy.com.
Licensed under the MIT license.

##Screen shot:##
http://zizhujy.com/blog/post/2013/07/02/A-Flot-plugin-for-saving-canvas-image-to-local-disk.aspx

##Installation and usage:##
```html
bower install flot.saveasimage

<script type="text/javascript" src="bower_components/flot.saveasimage/lib/base64.js">
<script type="text/javascript" src="bower_components/flot.saveasimage/lib/canvas2image.js">
<script type="text/javascript" src="bower_components/flot.saveasimage/jquery.flot.saveAsImage.js">
```
    
Now you are all set. Right click on your flot canvas, you will see the "Save image as ..." option.

##Online examples:##
http://zizhujy.com/FunctionGrapher is using it, you can try right clicking on the function graphs and
you will see you can save the image to local disk.

##Dependencies:##

This plugin references the base64.js and canvas2image.js.

##Customizations:##

The default behavior of this plugin is dynamically creating an image from the flot canvas, and then puts the 
image above the flot canvas. If you want to add some css effects on to the dynamically created image, you can
apply whatever css styles on to it, only remember to make sure the css class name is set correspondingly by 
the options object of this plugin. You can also customize the image format through this options object:

```javascript
options: {
    imageClassName: "canvas-image",
    imageFormat: "png"
}
```
