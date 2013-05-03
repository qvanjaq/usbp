$(function() {
	function initImagesUI() {
		$('#preview,#submit,#typePopup,#typeUpload').button();
		$('#filesProgress').progressbar({
			value: 0
		 }).hide();
		 $('#quality').slider({'min' : 1, 'max' : 95, 'value' : 85});
	}

	function initImagesBehaviour() {
		$('#typePopup').click(showUploadPopup);
		$('#preview').click(imagesPreviw);
		$('#submit').click(imagesProcess);
	}

	function imagesPreviw() {

	}

	function imagesProcess() {
		var filesForm = $('#files')[0].files;
		var convertTo = $('#convertTo').val();
		var width = $('#width').val();
		var height = $('#height').val();
		var quality = $('#quality').slider('value');

		imagesFromFiles(filesForm, function(images) {
			var dataUrl = processImages(images, convertTo, width, height, quality);
			zipFiles(dataUrl);
		});

	}

	function imagesFromFiles(files,callback) {
		var images = [];
		var dataUri = null;
		//var image = null;
		var reader = null;

		for(var i = 0; i < files.length; i++) {
			reader = new FileReader();
			reader.onload = (function(f) {
				return function(event) {
					var image = new Image();
					image.setAttribute('data-filename', f.name)
					image.setAttribute('data-type', f.type)
					image.onload = function(){
						images.push(image);
						if(files.length === images.length) {
							callback(images);
						}
					};
					image.src = event.target.result;
				};
			})(files[i]);
			reader.onerror = function(event) {
				console.error("File could not be read! Code " + event.target.error.code);
			};
			reader.readAsDataURL(files[i]);
		}
	}

	function processImages(images, convertTo, width, height, quality) {
		var dataUrl = [];
		var canvas = document.createElement('canvas');
		canvas.style.display = 'none';
		canvas.width = width;
		canvas.height = height;
		document.body.appendChild(canvas);
		for(var i = 0; i < images.length; i++) {
			if(width === '' && height !== '') {
				canvas.width = images[i].width / images[i].height * height;
			}

			if(height === '' && width !== '') {
				canvas.height = images[i].height / images[i].width * width;
			}

			if(width === '' && height === '') {
				canvas.width = images[i].width;
				canvas.height = images[i].height;
			}
			var ctx = canvas.getContext('2d');
			//ctx.imageSmoothingEnabled = false;
			//ctx.webkitImageSmoothingEnabled  = false;
			ctx.drawImage(images[i], 0, 0, canvas.width, canvas.height);

			dataUrl[i] = {};
			var fileType = images[i].getAttribute('data-type');
			if(convertTo === 'jpg' || (fileType === 'image/jpeg' && convertTo === 'not')) {
				var myEncoder = new JPEGEncoder(quality);
				var canvasPixelArray = canvas.getContext('2d').
						getImageData(0, 0, canvas.width, canvas.height);
				var JPEGImage = myEncoder.encode(canvasPixelArray);
				dataUrl[i]['data'] = JPEGImage;
			} else {
				dataUrl[i]['data'] = canvas.toDataURL('type/' + 'png');
			}

			var prevFilename = images[i].getAttribute('data-filename');
			var extPos = prevFilename.lastIndexOf(".");
			if(extPos === -1)
				extPos = prevFilename.length;

			var extension = null;
			if(convertTo === 'jpg' || convertTo === 'png') {
				extension = convertTo;
			} else if(fileType === 'image/jpeg') {
				extension = 'jpg';
			} else {
				extension = 'png';
			}

			var newFileName = prevFilename.substr(0, extPos) + '.' + extension;
			dataUrl[i]['filename'] = newFileName;
		}
		document.body.removeChild(canvas);
		return dataUrl;
	}

	function zipFiles(dataUrl) {
		var zip = new JSZip();
		var blob = null;
		for(var i = 0; i < dataUrl.length; i++) {
			blob = dataURItoBlob(dataUrl[i]['data']);
			zip.file(dataUrl[i]['filename'], blob, {base64: true});
		}

		var content = zip.generate({'type': 'blob'});
		saveAs(content, 'result.zip');
	}

	initImagesUI();
	initImagesBehaviour();
});