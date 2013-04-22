$(function(){
	function initTextFilesUI() {
		$('#typeUpload').buttonset();
		$('#preview,#submit,#typePopup').button();
		$( "#filesProgress" ).progressbar({
			value: false
		 });
	}
	function initTextFilesBehaviour() {
		$('#typePopup').click(showUploadPopup);
		$('#preview').click(textFilesPreviw);
		$('#submit').click(textFilesProcess);
	}

	function progressHandler() {

	}

	function textFilesPreviw() {

	}

	function textFilesProcess() {
		var idUpload = getRandom(1, Number.MAX_VALUE);
		var filesForm = $('#files')[0].files;
		var options = {idUpload: idUpload,
						size : packetSize,
			progressHandler : progressHandler,
					destination : URL_UPLOAD_FILE,
					reconnectionTimeout : reconnectionTimeout};

		var uploader = new Uploader(filesForm, finUploadCallback, options);
		uploader.upload();
	}

	function finUploadCallback() {
		// send data to server
		alert('send data');
	}

	initTextFilesUI();
	initTextFilesBehaviour();
});