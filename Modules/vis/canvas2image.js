/**
 * covert canvas to image
 * and save the image file
 */

var Canvas2Image = function () {

	// check if support sth.
	var $support = function () {
		var canvas = document.createElement('canvas'),
			ctx = canvas.getContext('2d');

		return {
			canvas: !!ctx,
			imageData: !!ctx.getImageData,
			dataURL: !!canvas.toDataURL,
			btoa: !!window.btoa
		};
	}();

	var downloadMime = 'image/octet-stream';

	function scaleCanvas (canvas, width, height, background) {
		var w = canvas.width,
			h = canvas.height;
		if (width == undefined) {
			width = w;
		}
		if (height == undefined) {
			height = h;
		}
		
		var retCanvas = document.createElement('canvas');
		var retCtx = retCanvas.getContext('2d');
		retCanvas.width = width;
		retCanvas.height = height;
		
		if (background == undefined) {
			background = false;
		}
		if (background === true){
			retCtx.fillStyle = '#ffffff';
			retCtx.fillRect(0,0, width, height);
		}
		
		retCtx.drawImage(canvas, 0, 0, w, h, 0, 0, width, height);
		return retCanvas;
	}

	function getDataURL (canvas, type, width, height, background) {
		canvas = scaleCanvas(canvas, width, height, background);
		return canvas.toDataURL(type);
	}


	function saveFile(uri, name) {
		function eventFire(el, etype){
			if (el.fireEvent) {
				(el.fireEvent('on' + etype));
			} else {
				var evObj = document.createEvent('Events');
				evObj.initEvent(etype, true, false);
				el.dispatchEvent(evObj);
			}
		}
		var link = document.createElement("a");
		link.download = name;
		link.href = uri;
//		eventFire(link, "click");
		var body = document.getElementsByTagName('body')[0];

		body.appendChild(link);
		link.click();
		body.removeChild(link);
	}

	function genImage(strData) {
		var img = document.createElement('img');
		img.src = strData;
		return img;
	}
	function fixType (type) {
		type = type.toLowerCase().replace(/jpg/i, 'jpeg');
		var r = type.match(/png|jpeg|bmp|gif/)[0];
		return 'image/' + r;
	}
	function encodeData (data) {
		if (!window.btoa) { throw 'btoa undefined' }
		var str = '';
		if (typeof data == 'string') {
			str = data;
		} else {
			for (var i = 0; i < data.length; i ++) {
				str += String.fromCharCode(data[i]);
			}
		}

		return btoa(str);
	}
	function getImageData (canvas) {
		var w = canvas.width,
			h = canvas.height;
		return canvas.getContext('2d').getImageData(0, 0, w, h);
	}
	function makeURI (strData, type) {
		return 'data:' + type + ';base64;content-disposition=attachment,' + strData;
	}


	/**
	 * create bitmap image
	 * 按照规则生成图片响应头和响应体
	 */
	var genBitmapImage = function (data) {
		var imgHeader = [],
			imgInfoHeader = [];
		
		var width = data.width,
			height = data.height;

		imgHeader.push(0x42); // 66 -> B
		imgHeader.push(0x4d); // 77 -> M

		var fsize = width * height * 3 + 54; // header size:54 bytes
		imgHeader.push(fsize % 256); // r
		fsize = Math.floor(fsize / 256);
		imgHeader.push(fsize % 256); // g
		fsize = Math.floor(fsize / 256);
		imgHeader.push(fsize % 256); // b
		fsize = Math.floor(fsize / 256);
		imgHeader.push(fsize % 256); // a

		imgHeader.push(0);
		imgHeader.push(0);
		imgHeader.push(0);
		imgHeader.push(0);

		imgHeader.push(54); // offset -> 6
		imgHeader.push(0);
		imgHeader.push(0);
		imgHeader.push(0);

		// info header
		imgInfoHeader.push(40); // info header size
		imgInfoHeader.push(0);
		imgInfoHeader.push(0);
		imgInfoHeader.push(0);

		// 横向info
		var _width = width;
		imgInfoHeader.push(_width % 256);
		_width = Math.floor(_width / 256);
		imgInfoHeader.push(_width % 256);
		_width = Math.floor(_width / 256);
		imgInfoHeader.push(_width % 256);
		_width = Math.floor(_width / 256);
		imgInfoHeader.push(_width % 256);

		// 纵向info
		var _height = height;
		imgInfoHeader.push(_height % 256);
		_height = Math.floor(_height / 256);
		imgInfoHeader.push(_height % 256);
		_height = Math.floor(_height / 256);
		imgInfoHeader.push(_height % 256);
		_height = Math.floor(_height / 256);
		imgInfoHeader.push(_height % 256);

		imgInfoHeader.push(1);
		imgInfoHeader.push(0);
		imgInfoHeader.push(24); // 24位bitmap
		imgInfoHeader.push(0);

		// no compression
		imgInfoHeader.push(0);
		imgInfoHeader.push(0);
		imgInfoHeader.push(0);
		imgInfoHeader.push(0);

		// pixel data
		var dataSize = width * height * 3;
		imgInfoHeader.push(dataSize % 256);
		dataSize = Math.floor(dataSize / 256);
		imgInfoHeader.push(dataSize % 256);
		dataSize = Math.floor(dataSize / 256);
		imgInfoHeader.push(dataSize % 256);
		dataSize = Math.floor(dataSize / 256);
		imgInfoHeader.push(dataSize % 256);

		// blank space
		for (var i = 0; i < 16; i ++) {
			imgInfoHeader.push(0);
		}

		var padding = (4 - ((width * 3) % 4)) % 4;
		var imgData = data.data;
		var strPixelData = '';
		var y = height;
		do {
			var offsetY = width * (y - 1) * 4;
			var strPixelRow = '';
			for (var x = 0; x < width; x ++) {
				var offsetX = 4 * x;
				strPixelRow += String.fromCharCode(imgData[offsetY + offsetX + 2]);
				strPixelRow += String.fromCharCode(imgData[offsetY + offsetX + 1]);
				strPixelRow += String.fromCharCode(imgData[offsetY + offsetX]);
			}
			for (var n = 0; n < padding; n ++) {
				strPixelRow += String.fromCharCode(0);
			}

			strPixelData += strPixelRow;
		} while(-- y);

		return (encodeData(imgHeader.concat(imgInfoHeader)) + encodeData(strPixelData));

	};

	/**
	 * saveAsImage
	 * @param canvasElement
	 * @param {String} image type
	 * @param {Number} [optional] png width
	 * @param {Number} [optional] png height
	 * @param {String} [optional] base filename without extension
	 * @param {Boolean} [optional] if canvas(saved image) should have white background
	 */
	var saveAsImage = function (canvas, width, height, type, filename, background) {
		if ($support.canvas && $support.dataURL) {
			if (type == undefined) { type = 'png'; }
			if (filename == undefined) {
				filename = 'default.' + type;
			} else {
				filename += '.' + type;
			}
			type = fixType(type);
			if (/bmp/.test(type)) {
				var data = getImageData(scaleCanvas(canvas, width, height, background));
				var strData = genBitmapImage(data);
				saveFile(makeURI(strData, downloadMime), filename);
			} else {
				var strData = getDataURL(canvas, type, width, height, background);
				saveFile(strData.replace(type, downloadMime), filename);
			}
		
		}
	}

	var convertToImage = function (canvas, width, height, type) {
		if ($support.canvas && $support.dataURL) {
			if (type == undefined) { type = 'png'; }
			type = fixType(type);

			if (/bmp/.test(type)) {
				var data = getImageData(scaleCanvas(canvas, width, height));
				var strData = genBitmapImage(data);
				return genImage(makeURI(strData, 'image/bmp'));
			} else {
				var strData = getDataURL(canvas, type, width, height);
				return genImage(strData);
			}
		}
	}



	return {
		saveAsImage: saveAsImage,
		saveAsPNG: function (canvas, width, height, filename, background) {
			return saveAsImage(canvas, width, height, 'png', filename, background);
		},
		saveAsJPEG: function (canvas, width, height, filename, background) {
			return saveAsImage(canvas, width, height, 'jpeg', filename, background);
		},
		saveAsGIF: function (canvas, width, height, filename, background) {
			return saveAsImage(canvas, width, height, 'gif', filename, background);
		},
		saveAsBMP: function (canvas, width, height, filename, background) {
			return saveAsImage(canvas, width, height, 'bmp', filename, background);
		},
		
		convertToImage: convertToImage,
		convertToPNG: function (canvas, width, height) {
			return convertToImage(canvas, width, height, 'png');
		},
		convertToJPEG: function (canvas, width, height) {
			return convertToImage(canvas, width, height, 'jpeg');			   
		},
		convertToGIF: function (canvas, width, height) {
			return convertToImage(canvas, width, height, 'gif');			  
		},
		convertToBMP: function (canvas, width, height) {
			return convertToImage(canvas, width, height, 'bmp');			  
		}
	};

}();
