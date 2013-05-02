$(function() {
	function initImagesUI() {
		$('#preview,#submit,#typePopup,#typeUpload').button();
		$('#filesProgress').progressbar({
			value: 0
		 }).hide();
		 $('#quality').slider({'min' : 1, 'max' : 100, 'value' : 100});
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
		imagesFromFiles(filesForm, function(images) {
			var dataUrl = processImages(images, convertTo);
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

	function processImages(images, convertTo) {
		var dataUrl = [];
		var canvas = document.createElement('canvas');
		canvas.style.display = 'none';
		document.body.appendChild(canvas);
		for(var i = 0; i < images.length; i++) {
			canvas.width = images[i].width;
			canvas.height = images[i].height;
			canvas.getContext('2d').drawImage(images[i], 0, 0);

			dataUrl[i] = {};
			if(convertTo === 'jpeg') {
				var myEncoder = new JPEGEncoder(85);
				var canvasPixelArray = canvas.getContext('2d').
						getImageData(0, 0, canvas.width, canvas.height);
				var JPEGImage = myEncoder.encode(canvasPixelArray);
				dataUrl[i]['data'] = JPEGImage;
			} else {
				dataUrl[i]['data'] = canvas.toDataURL('type/' + convertTo, 0.1);
			}

			var prevFilename = images[i].getAttribute('data-filename');
			var extPos = prevFilename.lastIndexOf(".");
			if(extPos === -1)
				extPos = prevFilename.length;

			var newFileName = prevFilename.substr(0, extPos) + '.' + convertTo;
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